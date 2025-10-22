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
use App\Http\Requests\Admin\SubAdmin2Request as StoreRequest;
use App\Http\Requests\Admin\SubAdmin2Request as UpdateRequest;
use App\Models\Country;
use App\Models\SubAdmin1;
use App\Models\SubAdmin2;
use Illuminate\Http\RedirectResponse;

class SubAdmin2Controller extends PanelController
{
	use SubAdminTrait;
	
	protected float|int|string|null $countryCode = null;
	protected float|int|string|null $admin1Code = null;
	
	public function setup()
	{
		// Set the xPanel Model
		$this->xPanel->setModel(SubAdmin2::class);
		
		// Init. each parent entry
		$country = null; // * required
		$admin1 = null;
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		// Find each parent entity key
		$this->countryCode = request()->route()->parameter('countryCode');
		$this->admin1Code = request()->route()->parameter('admin1Code');
		
		if (empty($this->countryCode)) {
			$parentUrl = urlGen()->adminUrl('countries');
			if (!request()->ajax()) {
				redirectUrl($parentUrl, 301, config('larapen.core.noCacheHeaders'));
			}
		}
		
		// Retrieve each parent entry
		if (!empty($this->admin1Code)) {
			$admin1 = SubAdmin1::find($this->admin1Code);
			abort_if(empty($admin1), 404, t('admin_division_not_found'));
			
			$this->countryCode = $admin1->country_code ?? $this->countryCode;
		}
		if (!empty($this->countryCode)) {
			$country = Country::find($this->countryCode);
		}
		
		abort_if(empty($country), 404, t('country_not_found'));
		
		// CRUD Panel Configuration
		$singularTxt = trans('admin.admin division 2');
		$pluralTxt = trans('admin.admin divisions 2');
		
		$this->xPanel->addClause('where', 'country_code', '=', $this->countryCode);
		if (!empty($admin1)) {
			$this->xPanel->addClause('where', 'subadmin1_code', '=', $this->admin1Code);
			$this->xPanel->setParentKeyColumn('subadmin1_code');
			
			$uri = "countries/{$this->countryCode}/admins1/{$this->admin1Code}/admins2";
			$label = "{$admin1->name}, {$country->name}";
			$singularTxt = "{$singularTxt} &rarr; {$label}";
			$pluralTxt = "{$pluralTxt} &rarr; {$label}";
			
			$parentUri = dirname($uri, 2);
			$parentLabel = $admin1->name;
			$parentSingularTxt = trans('admin.admin division 1') . " &rarr; {$parentLabel}";
			$parentPluralTxt = trans('admin.admin divisions 1') . " &rarr; {$parentLabel}";
		} else {
			$this->xPanel->setParentKeyColumn('country_code');
			
			$uri = "countries/{$this->countryCode}/admins2";
			$label = $country->name;
			$singularTxt = "{$singularTxt} &rarr; {$label}";
			$pluralTxt = "{$pluralTxt} &rarr; {$label}";
			
			$parentUri = dirname($uri, 2);
			$parentSingularTxt = trans('admin.Country');
			$parentPluralTxt = trans('admin.countries');
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
			$codePrefix = $this->admin1Code ?? $this->countryCode;
			$this->xPanel->addField([
				'name'    => 'code',
				'type'    => 'hidden',
				'default' => $this->autoIncrementCode($codePrefix . '.'),
			], 'create');
			
			$this->xPanel->addField([
				'name'  => 'country_code',
				'type'  => 'hidden',
				'value' => $this->countryCode,
			], 'create');
			
			if (!empty($this->admin1Code)) {
				$this->xPanel->addField([
					'name'  => 'subadmin1_code',
					'type'  => 'hidden',
					'value' => $this->admin1Code,
				], 'create');
			} else {
				$this->xPanel->addField([
					'name'        => 'subadmin1_code',
					'label'       => trans('admin.Admin1 Code'),
					'type'        => 'select2_from_array',
					'options'     => $this->subAdmin1s(),
					'allows_null' => true,
				]);
			}
			
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
	
	private function subAdmin1s(): array
	{
		// Get the Administrative Divisions
		$admins = SubAdmin1::query()->inCountry($this->countryCode)->get();
		
		$tab = [];
		if ($admins->count() > 0) {
			foreach ($admins as $admin) {
				$tab[$admin->code] = $admin->name . ' (' . $admin->code . ')';
			}
		}
		
		return $tab;
	}
}
