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

use App\Helpers\Common\Files\Upload;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\CategoryRequest as StoreRequest;
use App\Http\Requests\Admin\CategoryRequest as UpdateRequest;
use App\Http\Requests\Admin\Request;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Throwable;

class CategoryController extends PanelController
{
	protected float|int|string|null $categoryId = null;
	protected float|int|string|null $fieldId = null;
	
	public function setup()
	{
		// Set the xPanel Model
		$this->xPanel->setModel(Category::class);
		
		// Init. each parent entry
		$category = null;
		
		// Find each parent entity key
		$this->categoryId = request()->route()->parameter('parentIdentifier');
		$this->fieldId = request()->route()->parameter('fieldId');
		
		// Retrieve each parent entry
		if (!empty($this->categoryId)) {
			$category = Category::find($this->categoryId);
		}
		
		// CRUD Panel Configuration
		$singularTxt = trans('admin.category');
		$pluralTxt = trans('admin.categories');
		$allowAccess = ['reorder', 'details_row'];
		
		if (!empty($category)) {
			$this->xPanel->addClause('where', 'parent_id', '=', $category->id);
			$this->xPanel->setParentKeyColumn('parent_id');
			
			$uri = "categories/{$category->id}/subcategories";
			$label = $category->name;
			$singularTxt = "{$singularTxt} &rarr; {$label}";
			$pluralTxt = "{$pluralTxt} &rarr; {$label}";
			
			if (!empty($category->parent)) {
				$parentUri = "categories/{$category->parent->id}/subcategories";
				$parentLabel = $category->parent->name ?? '';
				$parentLabel = !empty($parentLabel) ? " &rarr; {$parentLabel}" : '';
				$parentSingularTxt = trans('admin.subcategory');
				$parentPluralTxt = trans('admin.subcategories');
				$parentSingularTxt = $parentSingularTxt . $parentLabel;
				$parentPluralTxt = $parentPluralTxt . $parentLabel;
			} else {
				$parentUri = dirname($uri, 2);
				$parentSingularTxt = trans('admin.category');
				$parentPluralTxt = trans('admin.categories');
			}
			
			$allowAccess[] = 'parent';
		} else {
			$this->xPanel->addClause('where', fn ($query) => $query->roots());
			
			$uri = "categories";
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->with(['children', 'fields']);
		$this->xPanel->withoutAppends();
		$this->xPanel->setRoute(urlGen()->adminUri($uri));
		$this->xPanel->setEntityNameStrings($singularTxt, $pluralTxt);
		$this->xPanel->allowAccess($allowAccess);
		$this->xPanel->enableReorder('name', 1);
		$this->xPanel->enableDetailsRow();
		$this->xPanel->setDetailsRowView('admin.panel.details_row.categories');
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		// Has Parent
		if (
			!empty($parentUri)
			&& !empty($parentSingularTxt)
			&& !empty($parentPluralTxt)
		) {
			$this->xPanel->enableParentEntity();
			$this->xPanel->setParentRoute(urlGen()->adminUri($parentUri));
			$this->xPanel->setParentEntityNameStrings($parentSingularTxt, $parentPluralTxt);
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'rebuild_nested_set_nodes_button', 'rebuildNestedSetNodesTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// FILTERS
			$this->xPanel->disableSearchBar();
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'name',
					'type'  => 'text',
					'label' => mb_ucfirst(trans('admin.Name')),
				],
				filterLogic: fn ($value) => $this->xPanel->addClause('where', 'name', 'LIKE', "%$value%")
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'catType',
					'type'  => 'dropdown',
					'label' => mb_ucfirst(trans('admin.type')),
				],
				values: [
					'classified'  => 'Classified',
					'job-offer'   => 'Job Offer',
					'job-search'  => 'Job Search',
					'not-salable' => 'Not-Salable',
				],
				filterLogic: fn ($value) => $this->xPanel->addClause('where', 'type', '=', $value)
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'status',
					'type'  => 'dropdown',
					'label' => trans('admin.Status'),
				],
				values: [
					1 => trans('admin.Activated'),
					2 => trans('admin.Unactivated'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('where', 'active', '=', 1);
					}
					if ($value == 2) {
						$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
					}
				}
			);
			
			// COLUMNS
			$this->xPanel->addColumn([
				'name'      => 'id',
				'label'     => '',
				'type'      => 'checkbox',
				'orderable' => false,
			]);
			$this->xPanel->addColumn([
				'name'          => 'name',
				'label'         => trans('admin.Name'),
				'type'          => 'model_function',
				'function_name' => 'crudNameColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'subcategories',
				'label'         => mb_ucfirst(trans('admin.subcategories')),
				'type'          => 'model_function',
				'function_name' => 'crudSubCategoriesColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'fields',
				'label'         => mb_ucfirst(trans('admin.custom fields')),
				'type'          => 'model_function',
				'function_name' => 'crudCustomFieldsColumn',
			]);
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('admin.Active'),
				'type'          => 'model_function',
				'function_name' => 'crudActiveColumn',
				'on_display'    => 'checkbox',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$currentResourceId = request()->route()->parameter('subcategory');
			$currentResourceId = request()->route()->parameter('category', $currentResourceId);
			
			if ($this->onCreatePage) {
				$this->xPanel->addField([
					'name'        => 'parent_id',
					'label'       => 'Parent',
					'type'        => 'select2_from_array',
					'options'     => Category::getNestedSelectOptions(),
					'allows_null' => false,
					'default'     => $this->categoryId,
					'wrapper'     => [
						'class' => 'col-md-12',
					],
				], 'create');
			}
			
			if ($this->onEditPage) {
				$this->xPanel->addField([
					'name'        => 'parent_id',
					'label'       => 'Parent',
					'type'        => 'select2_from_array',
					'options'     => Category::getNestedSelectOptions(excludeId: $currentResourceId),
					'allows_null' => false,
					'default'     => $this->categoryId,
					'wrapper'     => [
						'class' => 'col-md-12',
					],
				], 'update');
			}
			
			$this->xPanel->addField([
				'name'       => 'name',
				'label'      => trans('admin.Name'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Name'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'slug',
				'label'      => trans('admin.Slug'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Will be automatically generated from your name, if left empty'),
				],
				'hint'       => trans('admin.Will be automatically generated from your name, if left empty'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$defaultFontIconSet = config('larapen.core.defaultFontIconSet', 'bootstrap');
			$this->xPanel->addField([
				'name'        => 'icon_class',
				'label'       => trans('admin.Icon'),
				'type'        => 'icon_picker',
				'iconSet'     => config("larapen.core.fontIconSet.{$defaultFontIconSet}.key"),
				'iconVersion' => config("larapen.core.fontIconSet.{$defaultFontIconSet}.version"),
				'hint'        => trans('admin.Used in the categories area on the home and sitemap pages'),
			]);
			
			$this->xPanel->addField([
				'name'   => 'image_path',
				'label'  => trans('admin.Picture'),
				'type'   => 'image',
				'upload' => true,
				'disk'   => 'public',
				'hint'   => trans('admin.category_picture_icon_hint'),
			]);
			
			$wysiwygEditor = config('settings.other.wysiwyg_editor');
			$wysiwygEditorViewPath = "/views/admin/panel/fields/{$wysiwygEditor}.blade.php";
			$wysiwygEditorViewFullPath = resource_path($wysiwygEditorViewPath);
			$this->xPanel->addField([
				'name'       => 'description',
				'label'      => trans('admin.Description'),
				'type'       => ($wysiwygEditor != 'none' && file_exists($wysiwygEditorViewFullPath))
					? $wysiwygEditor
					: 'textarea',
				'attributes' => [
					'id'   => 'description',
					'rows' => 5,
				],
				'hint'       => trans('admin.cat_description_hint'),
				'wrapper'    => [
					'class' => 'col-md-12',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'hide_description',
				'label'   => trans('admin.hide_cat_description_label'),
				'type'    => 'checkbox_switch',
				'hint'    => trans('admin.hide_cat_description_hint'),
				'wrapper' => [
					'class' => 'col-md-12 mb-4 mt-n3',
				],
			]);
			
			$this->xPanel->addField([
				'name'  => 'type',
				'label' => mb_ucfirst(trans('admin.type')),
				'type'  => 'enum',
				'hint'  => trans('admin.category_types_info'),
			]);
			
			$this->xPanel->addField([
				'name'  => 'is_for_permanent',
				'label' => trans('admin.for_permanent_listings'),
				'type'  => 'checkbox_switch',
				'hint'  => trans('admin.for_permanent_listings_hint'),
			]);
			
			$this->xPanel->addField([
				'name'  => 'seo_tags',
				'type'  => 'custom_html',
				'value' => '<br><h4 style="margin-bottom: 0;">' . trans('admin.seo_tags') . '</h4>',
			]);
			
			$this->xPanel->addField([
				'name'  => 'seo_start',
				'type'  => 'custom_html',
				'value' => '<hr style="border: 1px dashed #EFEFEF;" class="mt-0 mb-1">',
			]);
			
			$this->xPanel->addField([
				'name'  => 'dynamic_variables_hint',
				'type'  => 'custom_html',
				'value' => trans('admin.dynamic_variables_hint'),
			]);
			
			$this->xPanel->addField([
				'name'       => 'seo_title',
				'label'      => trans('admin.Title'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Title'),
				],
				'hint'       => trans('admin.seo_title_hint'),
			]);
			
			$this->xPanel->addField([
				'name'       => 'seo_description',
				'label'      => trans('admin.Description'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Description'),
				],
				'hint'       => trans('admin.seo_description_hint'),
			]);
			
			$this->xPanel->addField([
				'name'       => 'seo_keywords',
				'label'      => trans('admin.Keywords'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Keywords'),
				],
				'hint'       => trans('admin.comma_separated_hint') . ' ' . trans('admin.seo_keywords_hint'),
			]);
			
			$this->xPanel->addField([
				'name'  => 'seo_end',
				'type'  => 'custom_html',
				'value' => '<hr style="border: 1px dashed #EFEFEF;">',
			]);
			
			$defaultActiveValue = $this->onCreatePage ? '1' : '0';
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('admin.Active'),
				'type'    => 'checkbox_switch',
				'default' => $defaultActiveValue,
			]);
			
			$this->xPanel->addField([
				'name'  => 'activateChildren',
				'label' => trans('admin.activate_children'),
				'type'  => 'checkbox_switch',
			], 'update');
		}
	}
	
	/**
	 * @param \App\Http\Requests\Admin\CategoryRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(StoreRequest $request): RedirectResponse
	{
		try {
			$request = $this->uploadFile($request);
		} catch (Throwable $e) {
		}
		
		return parent::storeCrud($request);
	}
	
	/**
	 * @param \App\Http\Requests\Admin\CategoryRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(UpdateRequest $request): RedirectResponse
	{
		try {
			$request = $this->uploadFile($request);
		} catch (Throwable $e) {
		}
		
		return parent::updateCrud($request);
	}
	
	/**
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	private function uploadFile(Request $request): Request
	{
		$attribute = 'image_path';
		$destPath = 'app/categories/custom';
		
		// Get uploaded image file (should return an UploadedFile object)
		$file = $request->file($attribute, $request->input($attribute));
		
		// Upload the image & get its local path
		$imagePath = Upload::image($file, $destPath, 'cat');
		
		// Set the local path in the input
		$request->merge([$attribute => $imagePath]);
		
		return $request;
	}
	
	/**
	 * Convert Adjacent List to Nested Set
	 *
	 * NOTE:
	 * - The items' order is reset, using the adjacent list's primary key order
	 * - Need to use the adjacent list model to add, update or delete nodes
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function rebuildNestedSetNodes(): RedirectResponse
	{
		return $this->xPanel->rebuildNestedSetNodes();
	}
}
