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
use App\Http\Requests\Admin\PaymentMethodRequest as StoreRequest;
use App\Http\Requests\Admin\PaymentMethodRequest as UpdateRequest;
use App\Models\Country;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;

class PaymentMethodController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(PaymentMethod::class);
		$this->xPanel->setRoute(urlGen()->adminUri('payment_methods'));
		$this->xPanel->setEntityNameStrings(trans('admin.payment method'), trans('admin.payment methods'));
		$this->xPanel->enableReorder('display_name', 1);
		$this->xPanel->allowAccess(['reorder']);
		$this->xPanel->denyAccess(['create', 'delete']);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// COLUMNS
			$this->xPanel->addColumn([
				'name'          => 'display_name',
				'label'         => trans('admin.Name'),
				'type'          => 'model_function',
				'function_name' => 'crudDisplayNameColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'description',
				'label' => trans('admin.Description'),
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'countries',
				'label'         => mb_ucfirst(trans('admin.countries')),
				'type'          => 'model_function',
				'function_name' => 'crudCountriesColumn',
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
			// Get Countries codes
			$countryCodes = '';
			$countries = Country::query()->get(['code']);
			if ($countries->count() > 0) {
				$countryCodes = collect($countries)->keyBy('code')->keys()->join(', ');
			}
			
			// Get the current Entry
			$paymentMethod = null;
			if ($this->onEditPage) {
				$currentResourceId = request()->route()->parameter('payment_method');
				$paymentMethod = PaymentMethod::query()->find($currentResourceId);
			}
			
			$this->xPanel->addField([
				'name'       => 'display_name',
				'label'      => trans('admin.Name'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Name'),
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'description',
				'label'      => trans('admin.Description'),
				'type'       => 'textarea',
				'attributes' => [
					'placeholder' => trans('admin.Description'),
				],
				'hint'       => trans('admin.HTML tags are supported'),
			]);
			
			$countriesFieldParams = [
				'name'       => 'countries',
				'label'      => trans('admin.Countries Codes'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Countries Codes') . ' (' . trans('admin.separated by commas') . ')',
				],
				'hint'       => '<strong>' . trans('admin.countries_codes_list_hint') . ':</strong><br>' . $countryCodes,
			];
			if (!empty($paymentMethod)) {
				$countriesFieldParams['value'] = $paymentMethod->countries;
			}
			$this->xPanel->addField($countriesFieldParams);
			
			$this->xPanel->addField([
				'name'  => 'active',
				'label' => trans('admin.Active'),
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
}
