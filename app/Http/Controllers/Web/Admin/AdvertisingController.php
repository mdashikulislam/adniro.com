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
use App\Http\Requests\Admin\Request as StoreRequest;
use App\Http\Requests\Admin\Request as UpdateRequest;
use App\Models\Advertising;
use Illuminate\Http\RedirectResponse;

class AdvertisingController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Advertising::class);
		$this->xPanel->setRoute(urlGen()->adminUri('advertisings'));
		$this->xPanel->setEntityNameStrings(trans('admin.advertising'), trans('admin.advertisings'));
		$this->xPanel->denyAccess(['create', 'delete']);
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// COLUMNS
			$this->xPanel->addColumn([
				'name'  => 'id',
				'label' => "ID",
			]);
			$this->xPanel->addColumn([
				'name'  => 'slug',
				'label' => trans('admin.Slug'),
			]);
			$this->xPanel->addColumn([
				'name'  => 'provider_name',
				'label' => trans('admin.Provider Name'),
			]);
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('admin.Active'),
				'type'          => 'model_function',
				'function_name' => 'crudActiveColumn',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$advertising = null;
			
			if ($this->onEditPage) {
				$currentResourceId = request()->route()->parameter('advertising');
				$advertising = Advertising::query()->find($currentResourceId);
			}
			
			// FIELDS
			$this->xPanel->addField([
				'name'       => 'provider_name',
				'label'      => trans('admin.Provider Name'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Provider Name'),
				],
			]);
			
			if (!empty($advertising)) {
				$this->xPanel->addField([
					'name'  => 'description',
					'type'  => 'custom_html',
					'value' => trans('admin.' . $advertising->description, ['slug' => $advertising->slug]),
				], 'update');
			}
			
			$this->xPanel->addField([
				'name'       => 'tracking_code_large',
				'label'      => trans('admin.tracking_code_label'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Enter the advertising code here'),
					'rows'        => 10,
				],
			]);
			
			if (!empty($advertising)) {
				if ($advertising->integration != 'autoFit') {
					$this->xPanel->addField([
						'name'    => 'is_responsive',
						'label'   => trans('admin.is_responsive_label'),
						'type'    => 'checkbox_switch',
						'hint'    => trans('admin.is_responsive_hint'),
						'wrapper' => [
							'class' => 'col-md-6',
						],
					]);
					
					$this->xPanel->addField([
						'name'    => 'separator_1',
						'type'    => 'custom_html',
						'value'   => '<hr>',
						'wrapper' => [
							'class' => 'col-md-12',
						],
					]);
					
					$this->xPanel->addField([
						'name'       => 'tracking_code_medium',
						'label'      => trans('admin.tracking_code_label') . " (" . trans('admin.Tablet Format') . ")",
						'type'       => 'textarea',
						'attributes' => [
							'placeholder' => trans('admin.Enter the advertising code here'),
							'rows'        => 10,
						],
						'hint'       => trans('admin.tracking_code_medium_hint') . ' ' . trans('admin.tracking_code_responsive_note'),
						'wrapper'    => [
							'class' => 'col-md-12',
						],
					]);
					
					$this->xPanel->addField([
						'name'       => 'tracking_code_small',
						'label'      => trans('admin.tracking_code_label') . " (" . trans('admin.Phone Format') . ")",
						'type'       => 'textarea',
						'attributes' => [
							'placeholder' => trans('admin.Enter the advertising code here'),
							'rows'        => 10,
						],
						'hint'       => trans('admin.tracking_code_small_hint') . ' ' . trans('admin.tracking_code_responsive_note'),
						'wrapper'    => [
							'class' => 'col-md-12',
						],
					]);
				}
			}
			
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
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		return parent::updateCrud($request);
	}
}
