<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Observers;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Post;
use App\Models\Scopes\ActiveScope;
use App\Observers\Traits\CategoryTrait;
use App\Observers\Traits\HasNestedColumns;

class CategoryObserver extends BaseObserver
{
	use HasNestedColumns, CategoryTrait;
	
	/**
	 * Listen to the Entry creating event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function creating(Category $category)
	{
		// Fix required columns
		$category = $this->fixRequiredColumns($category);
		
		// Apply the nested created actions
		return $this->creatingNestedItem($category);
	}
	
	/**
	 * Listen to the Entry updating event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function updating(Category $category)
	{
		// Fix required columns
		$category = $this->fixRequiredColumns($category);
		
		// Apply the nested updating actions
		return $this->updatingNestedItem($category);
	}
	
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function deleting($category)
	{
		// Apply the nested deleting actions
		$this->deletingNestedItem($category);
		
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		// Delete all the Category's Custom Fields
		$catFields = CategoryField::where('category_id', $category->id)->get();
		if ($catFields->count() > 0) {
			foreach ($catFields as $catField) {
				$catField->delete();
			}
		}
		
		// Delete all the Category's Posts
		$posts = Post::where('category_id', $category->id);
		if ($posts->count() > 0) {
			foreach ($posts->cursor() as $post) {
				$post->delete();
			}
		}
		
		// Don't delete the default pictures
		$defaultPicture = 'app/default/categories/fa-folder-default.png';
		$skin = config('settings.style.skin', 'default');
		$skinPicture = 'app/default/categories/fa-folder-' . $skin . '.png';
		$skinDirectory = 'app/categories/' . $skin . '/';
		if (
			!empty($category->image_path)
			&& !str_contains($category->image_path, $defaultPicture)
			&& !str_contains($category->image_path, $skinPicture)
			&& !str_contains($category->image_path, $skinDirectory)
			&& $disk->exists($category->image_path)
		) {
			$disk->delete($category->image_path);
		}
		
		// Delete the category's children recursively
		$this->deleteChildrenRecursively($category);
	}
	
	/**
	 * Listen to the Entry updated event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function updated(Category $category)
	{
		// Activate|Deactivate category with its children or parent (if they exist)
		
		/*
		 * If the category is activated, check if it has a parent;
		 * If yes, active the parent also *ONLY* if it was disabled.
		 * NOTE: The *ONLY* means to prevent any infinite recursion.
		 */
		if ($category->active == 1) {
			if (!empty($category->parent_id)) {
				$parentCat = Category::find($category->parent_id);
				if ($parentCat->active != 1) {
					$parentCat->active = 1;
					$parentCat->save();
				}
			}
			
			// If the "activateChildren" field is checked,
			// then activate all the category's children.
			if (request()->has('activateChildren') && (bool)request()->input('activateChildren')) {
				$subCats = Category::childrenOf($category->id)->get();
				if ($subCats->count() > 0) {
					foreach ($subCats as $subCat) {
						if ($subCat->active != 1) {
							$subCat->active = 1;
							$subCat->save();
						}
					}
				}
			}
		} else {
			/*
			 * If the category is disabled, check if it has children;
			 * If yes, browses each child and disables it *ONLY* if it's not disabled.
			 * NOTE: The *ONLY* means to prevent any infinite recursion.
			 */
			$subCats = Category::childrenOf($category->id)->get();
			if ($subCats->count() > 0) {
				foreach ($subCats as $subCat) {
					if ($subCat->active != 0) {
						$subCat->active = 0;
						$subCat->save();
					}
				}
			}
		}
		
		// Update menu items when category slug or name changes
		if ($category->isDirty(['slug', 'name', 'active'])) {
			$this->updateRelatedMenuItems($category);
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function saved(Category $category)
	{
		// Convert Adjacent List to Nested Set
		// $this->adjacentToNestedByItem($category);
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function deleted(Category $category)
	{
		// Convert Adjacent List to Nested Set
		// $this->adjacentToNestedByItem($category);
		
		// Remove menu items when category is deleted
		$this->removeRelatedMenuItems($category);
	}
	
	// PRIVATE
	
	/**
	 * @param \App\Models\Category $category
	 * @return void
	 */
	private function updateRelatedMenuItems(Category $category): void
	{
		$prefix = 'category.';
		$routeName = "{$prefix}{$category->getOriginal('slug')}";
		
		// Find menu items that reference this category
		$menuItems = MenuItem::query()
			->with(['menu'])
			->where('url_type', 'route')
			->where('route_name', $routeName)
			->get();
		
		foreach ($menuItems as $menuItem) {
			if (!$category->active) {
				// Deactivate menu item if category is deactivated
				$menuItem->update(['active' => 0]);
			} else {
				// Update route name if slug changed
				if ($category->isDirty('slug')) {
					$menuItem->update(['route_name' => "{$prefix}{$category->slug}"]);
				}
				
				// Update label/title if name changed
				if ($category->isDirty('name')) {
					$currentLabel = method_exists($menuItem, 'getLabelTranslations') ? $menuItem->getLabelTranslations() : [];
					
					$languages = Language::query()->withoutGlobalScopes([ActiveScope::class])->get(['code']);
					if ($languages->count() > 0) {
						foreach ($languages as $language) {
							$currentLabel[$language->code] = $category->getTranslation('name', $language->code);
						}
					}
					
					if (!empty($currentLabel)) {
						$menuItem->setTranslations('label', $currentLabel);
						$menuItem->save();
					}
				}
			}
		}
	}
	
	/**
	 * @param \App\Models\Category $category
	 * @return void
	 */
	private function removeRelatedMenuItems(Category $category): void
	{
		$prefix = 'category.';
		$routeName = "{$prefix}{$category->slug}";
		
		$menuItems = MenuItem::query()
			->with(['menu'])
			->where('url_type', 'route')
			->where('route_name', $routeName)
			->get();
		
		foreach ($menuItems as $menuItem) {
			$menuItem->delete();
		}
	}
}
