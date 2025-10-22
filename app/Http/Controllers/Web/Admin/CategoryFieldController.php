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

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\CategoryFieldRequest as StoreRequest;
use App\Http\Requests\Admin\CategoryFieldRequest as UpdateRequest;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Field;
use Illuminate\Http\RedirectResponse;

class CategoryFieldController extends PanelController
{
	private int|string|null $categoryId = 0;
	private int|string|null $fieldId = 0;
	
	public function setup()
	{
		// Parents Entities
		$parentEntities = ['categories', 'custom_fields'];
		
		// Get the parent Entity slug
		$parentEntity = request()->segment(2);
		if (!in_array($parentEntity, $parentEntities)) {
			abort(404);
		}
		
		// Category => CategoryField
		if ($parentEntity == 'categories') {
			$this->categoryId = request()->segment(3);
			
			// Get Parent Category's name
			$category = Category::findOrFail($this->categoryId);
		}
		
		// Field => CategoryField
		if ($parentEntity == 'custom_fields') {
			$this->fieldId = request()->segment(3);
			
			// Get Field's name
			$field = Field::findOrFail($this->fieldId);
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(CategoryField::class);
		$this->xPanel->with(['category', 'field']);
		$this->xPanel->enableParentEntity();
		
		// Category => CategoryField
		if ($parentEntity == 'categories') {
			$this->xPanel->setRoute(urlGen()->adminUri('categories/' . $category->id . '/custom_fields'));
			$this->xPanel->setEntityNameStrings(
				trans('admin.custom field') . ' &rarr; ' . $category->name,
				trans('admin.custom fields') . ' &rarr; ' . $category->name
			);
			$this->xPanel->enableReorder('field.name', 1);
			if (!request()->input('order')) {
				$this->xPanel->orderBy('lft');
			}
			$this->xPanel->setParentKeyColumn('category_id');
			$this->xPanel->addClause('where', 'category_id', '=', $category->id);
			$this->xPanel->setParentRoute(urlGen()->adminUri('categories'));
			$this->xPanel->setParentEntityNameStrings(trans('admin.category'), trans('admin.categories'));
			$this->xPanel->allowAccess(['reorder', 'parent']);
		}
		
		// Field => CategoryField
		if ($parentEntity == 'custom_fields') {
			$this->xPanel->setRoute(urlGen()->adminUri('custom_fields/' . $field->id . '/categories'));
			$this->xPanel->setEntityNameStrings(
				$field->name . ' ' . trans('admin.custom field') . ' &rarr; ' . trans('admin.category'),
				$field->name . ' ' . trans('admin.custom fields') . ' &rarr; ' . trans('admin.categories')
			);
			$this->xPanel->setParentKeyColumn('field_id');
			$this->xPanel->addClause('where', 'field_id', '=', $field->id);
			$this->xPanel->setParentRoute(urlGen()->adminUri('custom_fields'));
			$this->xPanel->setParentEntityNameStrings(trans('admin.custom field'), trans('admin.custom fields'));
			$this->xPanel->allowAccess(['parent']);
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// COLUMNS
			$this->xPanel->addColumn([
				'name'      => 'id',
				'label'     => '',
				'type'      => 'checkbox',
				'orderable' => false,
			]);
			
			// Category => CategoryField
			if ($parentEntity == 'categories') {
				$this->xPanel->addColumn([
					'name'          => 'field_id',
					'label'         => mb_ucfirst(trans('admin.custom field')),
					'type'          => 'model_function',
					'function_name' => 'crudFieldColumn',
				]);
			}
			
			// Field => CategoryField
			if ($parentEntity == 'custom_fields') {
				$this->xPanel->addColumn([
					'name'          => 'category_id',
					'label'         => trans('admin.Category'),
					'type'          => 'model_function',
					'function_name' => 'crudCategoryColumn',
				]);
			}
			
			$this->xPanel->addColumn([
				'name'          => 'disabled_in_subcategories',
				'label'         => trans('admin.Disabled in subcategories'),
				'type'          => 'model_function',
				'function_name' => 'crudDisabledInSubCategoriesColumn',
				'on_display'    => 'checkbox',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			// Category => CategoryField
			if ($parentEntity == 'categories') {
				$this->xPanel->addField([
					'name'  => 'category_id',
					'type'  => 'hidden',
					'value' => $this->categoryId,
				], 'create');
				$this->xPanel->addField([
					'name'        => 'field_id',
					'label'       => mb_ucfirst(trans('admin.Select a Custom field')),
					'type'        => 'select2_from_array',
					'options'     => $this->fields($this->fieldId),
					'allows_null' => false,
				]);
			}
			
			// Field => CategoryField
			if ($parentEntity == 'custom_fields') {
				$this->xPanel->addField([
					'name'  => 'field_id',
					'type'  => 'hidden',
					'value' => $this->fieldId,
				], 'create');
				$this->xPanel->addField([
					'name'        => 'category_id',
					'label'       => trans('admin.Select a Category'),
					'type'        => 'select2_from_array',
					'options'     => $this->categories($this->categoryId),
					'allows_null' => false,
				]);
			}
			
			$this->xPanel->addField([
				'name'  => 'disabled_in_subcategories',
				'label' => trans('admin.Disabled in subcategories'),
				'type'  => 'checkbox_switch',
			]);
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		return parent::updateCrud($request);
	}
	
	private function fields($selectedEntryId): array
	{
		$fields = Field::query()->orderBy('name')->get();
		
		return $fields->pluck('name', 'id')->toArray();
	}
	
	private function categories($selectedEntryId): array
	{
		// Single query to get ALL categories with their relationships
		$allCategories = Category::query()->with('parent')->get();
		
		if ($allCategories->count() <= 0) {
			return [];
		}
		
		// Normalize parent_id values: convert 0 to null for consistency
		$allCategories = $allCategories->map(function ($category) {
			if ($category->parent_id === 0 || $category->parent_id === '') {
				$category->parent_id = null;
			}
			
			return $category;
		});
		
		// Group categories by parent_id for O(1) lookup
		$categoriesByParent = $allCategories->groupBy('parent_id');
		
		$tab = [];
		
		// Get root categories (where parent_id is null OR 0 OR empty string)
		// After normalization, all roots will have null parent_id
		$rootCategories = $categoriesByParent->get(null, collect());
		
		foreach ($rootCategories as $category) {
			$this->buildCategoryTree($category, $tab, $categoriesByParent, 0);
		}
		
		return $tab;
	}
	
	private function buildCategoryTree($category, &$tab, $categoriesByParent, $depth): void
	{
		// Add current category with proper indentation
		$indent = str_repeat('---| ', $depth);
		$tab[$category->id] = $indent . $category->name;
		
		// Get children from preloaded collection (no additional queries)
		$children = $categoriesByParent->get($category->id, collect());
		
		// Sort children by name or any other field you prefer
		$children = $children->sortBy('name');
		
		foreach ($children as $child) {
			$this->buildCategoryTree($child, $tab, $categoriesByParent, $depth + 1);
		}
	}
	
	private function categoriesOld($selectedEntryId): array
	{
		$entries = Category::roots()->orderBy('lft')->get();
		
		if ($entries->count() <= 0) {
			return [];
		}
		
		$tab = [];
		foreach ($entries as $entry) {
			$tab[$entry->id] = $entry->name;
			
			$subEntries = Category::childrenOf($entry->id)->orderBy('lft')->get();
			if ($subEntries->count() > 0) {
				foreach ($subEntries as $subEntry) {
					$tab[$subEntry->id] = "---| " . $subEntry->name;
				}
			}
		}
		
		return $tab;
	}
}
