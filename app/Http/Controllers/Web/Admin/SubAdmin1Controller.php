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
use App\Http\Controllers\Web\Admin\Traits\SubAdminTrait;
use App\Http\Requests\Admin\SubAdmin1Request as StoreRequest;
use App\Http\Requests\Admin\SubAdmin1Request as UpdateRequest;
use App\Models\Country;
use App\Models\SubAdmin1;
use Illuminate\Http\RedirectResponse;

class SubAdmin1Controller extends PanelController
{
	use SubAdminTrait;
	
	protected float|int|string|null $countryCode = null;
	
	public function setup()
	{
		// Set the xPanel Model
		$this->xPanel->setModel(SubAdmin1::class);
		
		// Init. each parent entry
		$country = null; // * required
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		// Find each parent entity key
		$this->countryCode = request()->route()->parameter('countryCode');
		
		if (empty($this->countryCode)) {
			$parentUrl = urlGen()->adminUrl('countries');
			if (!request()->ajax()) {
				redirectUrl($parentUrl, 301, config('larapen.core.noCacheHeaders'));
			}
		}
		
		// Retrieve each parent entry
		if (!empty($this->countryCode)) {
			$country = Country::find($this->countryCode);
		}
		
		abort_if(empty($country), 404, t('country_not_found'));
		
		// CRUD Panel Configuration
		$singularTxt = trans('admin.admin division 1');
		$pluralTxt = trans('admin.admin divisions 1');
		
		if (!empty($country)) {
			$this->xPanel->addClause('where', 'country_code', '=', $this->countryCode);
			$this->xPanel->setParentKeyColumn('country_code');
			
			$uri = "countries/{$this->countryCode}/admins1";
			$singularTxt = "{$singularTxt} &rarr; {$country->name}";
			$pluralTxt = "{$pluralTxt} &rarr; {$country->name}";
			
			$parentUri = dirname($uri, 2);
			$parentSingularTxt = trans('admin.country');
			$parentPluralTxt = trans('admin.countries');
		} else {
			$uri = "admins1";
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setRoute(urlGen()->adminUri($uri));
		$this->xPanel->setEntityNameStrings($singularTxt, $pluralTxt);
		
		// Has Parent
		if (
			!empty($parentUri)
			&& !empty($parentSingularTxt)
			&& !empty($parentPluralTxt)
		) {
			$this->xPanel->enableParentEntity();
			$this->xPanel->allowAccess(['parent']);
			$this->xPanel->setParentRoute(urlGen()->adminUri($parentUri));
			$this->xPanel->setParentEntityNameStrings($parentSingularTxt, $parentPluralTxt);
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		
		$this->xPanel->addButtonFromModelFunction('line', 'cities', 'citiesInLineButton', 'beginning');
		$this->xPanel->addButtonFromModelFunction('line', 'admin_divisions2', 'adminDivisions2InLineButton', 'beginning');
		
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
			
			$this->xPanel->addColumn([
				'name'  => 'code',
				'label' => trans('admin.code'),
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'name',
				'label'         => trans('admin.Name'),
				'type'          => 'model_function',
				'function_name' => 'crudNameColumn',
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
			$this->xPanel->addField([
				'name'    => 'code',
				'type'    => 'hidden',
				'default' => $this->autoIncrementCode($this->countryCode . '.'),
			], 'create');
			
			$this->xPanel->addField([
				'name'  => 'country_code',
				'type'  => 'hidden',
				'value' => $this->countryCode,
			], 'create');
			
			$this->xPanel->addField([
				'name'       => 'name',
				'label'      => trans('admin.Name'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Enter the name'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
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
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		return parent::updateCrud($request);
	}
}
