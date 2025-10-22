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
use App\Http\Requests\Admin\FieldRequest as StoreRequest;
use App\Http\Requests\Admin\FieldRequest as UpdateRequest;
use App\Models\Field;
use Illuminate\Http\RedirectResponse;

class FieldController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Field::class);
		
		$this->xPanel->setRoute(urlGen()->adminUri('custom_fields'));
		$this->xPanel->setEntityNameStrings(trans('admin.custom field'), trans('admin.custom fields'));
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('line', 'add_to_category', 'addToCategoryInLineButton', 'end');
		$this->xPanel->addButtonFromModelFunction('line', 'options', 'optionsInLineButton', 'end');
		
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
				'name'          => 'type',
				'label'         => mb_ucfirst(trans('admin.type')),
				'type'          => 'model_function',
				'function_name' => 'crudTypeColumn',
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
				'name'  => 'type',
				'type'  => 'hidden',
				'value' => 'post',
			]);
			
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
				'name'        => 'type',
				'label'       => trans('admin.type'),
				'type'        => 'select_from_array',
				'options'     => Field::fieldTypes(),
				'allows_null' => false,
				'wrapper'     => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'max',
				'label'      => trans('admin.Field Length'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Field Length'),
				],
				'hint'       => trans('admin.field_length_hint'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'default_value',
				'label'      => trans('admin.Default value'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Default value'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'required',
				'label'   => trans('admin.Required'),
				'type'    => 'checkbox_switch',
				'wrapper' => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'help',
				'label'      => trans('admin.Help'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Help'),
				],
				'hint'       => trans('admin.cf_help_hint'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'use_as_filter',
				'label'   => trans('admin.cf_use_as_filter_label'),
				'type'    => 'checkbox_switch',
				'hint'    => trans('admin.cf_use_as_filter_hint'),
				'wrapper' => [
					'class' => 'col-md-6',
				],
			]);
			
			$defaultActiveValue = $this->onCreatePage ? '1' : '0';
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('admin.Active'),
				'type'    => 'checkbox_switch',
				'default' => $defaultActiveValue,
				'wrapper' => [
					'class' => 'col-md-6',
				],
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
}
