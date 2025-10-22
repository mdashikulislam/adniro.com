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
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Scopes\ActiveScope;

class PageObserver extends BaseObserver
{
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param Page $page
	 * @return void
	 */
	public function deleting(Page $page)
	{
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		// Delete the page picture
		if (!empty($page->image_path)) {
			if ($disk->exists($page->image_path)) {
				$disk->delete($page->image_path);
			}
		}
	}
	
	/**
	 * Listen to the Entry updated event.
	 *
	 * @param Page $page
	 * @return void
	 */
	public function updated(Page $page): void
	{
		// Update menu items when page slug or name changes
		if ($page->isDirty(['slug', 'name', 'active'])) {
			$this->updateRelatedMenuItems($page);
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Page $page
	 * @return void
	 */
	public function saved(Page $page)
	{
		// ...
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param Page $page
	 * @return void
	 */
	public function deleted(Page $page)
	{
		// Remove menu items when page is deleted
		$this->removeRelatedMenuItems($page);
	}
	
	// PRIVATE
	
	/**
	 * @param \App\Models\Page $page
	 * @return void
	 */
	private function updateRelatedMenuItems(Page $page): void
	{
		$prefix = 'page.';
		$routeName = "{$prefix}{$page->getOriginal('slug')}";
		
		// Find menu items that reference this page
		$menuItems = MenuItem::query()
			->with(['menu'])
			->where('url_type', 'route')
			->where('route_name', $routeName)
			->get();
		
		foreach ($menuItems as $menuItem) {
			if (!$page->active) {
				// Deactivate menu item if page is deactivated
				$menuItem->update(['active' => 0]);
			} else {
				// Update route name if slug changed
				if ($page->isDirty('slug')) {
					$menuItem->update(['route_name' => "{$prefix}{$page->slug}"]);
				}
				
				// Update label/title if name changed
				if ($page->isDirty('name')) {
					$currentLabel = method_exists($menuItem, 'getLabelTranslations') ? $menuItem->getLabelTranslations() : [];
					
					$languages = Language::query()->withoutGlobalScopes([ActiveScope::class])->get(['code']);
					if ($languages->count() > 0) {
						foreach ($languages as $language) {
							$currentLabel[$language->code] = $page->getTranslation('name', $language->code);
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
	 * @param \App\Models\Page $page
	 * @return void
	 */
	private function removeRelatedMenuItems(Page $page): void
	{
		$prefix = 'page.';
		$routeName = "{$prefix}{$page->slug}";
		
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
