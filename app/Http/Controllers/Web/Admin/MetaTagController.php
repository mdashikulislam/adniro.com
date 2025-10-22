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
use App\Http\Requests\Admin\MetaTagRequest as StoreRequest;
use App\Http\Requests\Admin\MetaTagRequest as UpdateRequest;
use App\Models\MetaTag;
use Illuminate\Http\RedirectResponse;

class MetaTagController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(MetaTag::class);
		$this->xPanel->setRoute(urlGen()->adminUri('meta_tags'));
		$this->xPanel->setEntityNameStrings(trans('admin.meta tag'), trans('admin.meta tags'));
		
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
				filterLogic: function ($value) {
					$this->xPanel->addClause('where', function ($query) use ($value) {
						$query->where('page', 'LIKE', "%$value%")
							->orWhere('title', 'LIKE', "%$value%")
							->orWhere('description', 'LIKE', "%$value%");
					});
				}
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
				'name'          => 'page',
				'label'         => trans('admin.Page'),
				'type'          => 'model_function',
				'function_name' => 'crudPageColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'title',
				'label' => mb_ucfirst(trans('admin.title')),
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'description',
				'label' => trans('admin.Description'),
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
			$this->xPanel->addField([
				'name'        => 'page',
				'label'       => trans('admin.Page'),
				'type'        => 'select2_from_array',
				'options'     => MetaTag::getDefaultPages(),
				'allows_null' => false,
			], 'create');
			
			$this->xPanel->addField([
				'name'        => 'page',
				'label'       => trans('admin.Page'),
				'type'        => 'select2_from_array',
				'options'     => MetaTag::getDefaultPages(),
				'allows_null' => false,
				'attributes'  => [
					'disabled' => true,
				],
			], 'update');
			
			$this->xPanel->addField([
				'name'  => 'dynamic_variables_full_hint',
				'type'  => 'custom_html',
				'value' => trans('admin.dynamic_variables_full_hint'),
			]);
			
			$this->xPanel->addField([
				'name'       => 'title',
				'label'      => mb_ucfirst(trans('admin.title')),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => mb_ucfirst(trans('admin.title')),
				],
				'hint'       => trans('admin.seo_title_hint'),
			]);
			
			$this->xPanel->addField([
				'name'       => 'description',
				'label'      => trans('admin.Description'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Description'),
				],
				'hint'       => trans('admin.seo_description_hint'),
			]);
			
			$this->xPanel->addField([
				'name'       => 'keywords',
				'label'      => trans('admin.Keywords'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Keywords'),
				],
				'hint'       => trans('admin.comma_separated_hint') . ' ' . trans('admin.seo_keywords_hint'),
			]);
			
			$defaultActiveValue = $this->onCreatePage ? '1' : '0';
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('admin.Active'),
				'type'    => 'checkbox_switch',
				'default' => $defaultActiveValue,
			]);
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud();
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		return parent::updateCrud();
	}
}
