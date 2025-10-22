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

use App\Helpers\Common\Date\TimeZoneManager;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\CityRequest as StoreRequest;
use App\Http\Requests\Admin\CityRequest as UpdateRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Scopes\ActiveScope;
use App\Models\SubAdmin1;
use App\Models\SubAdmin2;
use Illuminate\Http\RedirectResponse;

class CityController extends PanelController
{
	protected float|int|string|null $countryCode = null;
	protected float|int|string|null $admin1Code = null;
	protected float|int|string|null $admin2Code = null;
	
	public function setup()
	{
		// Set the xPanel Model
		$this->xPanel->setModel(City::class);
		
		// Init. each parent entry
		$country = null; // * required
		$admin1 = null;
		$admin2 = null;
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		// Find each parent entity key
		$this->countryCode = request()->route()->parameter('countryCode');
		$this->admin1Code = request()->route()->parameter('admin1Code');
		$this->admin2Code = request()->route()->parameter('admin2Code');
		
		if (empty($this->countryCode)) {
			$parentUrl = urlGen()->adminUrl('countries');
			if (!request()->ajax()) {
				redirectUrl($parentUrl, 301, config('larapen.core.noCacheHeaders'));
			}
		}
		
		// Retrieve each parent entry
		if (!empty($this->admin2Code)) {
			$admin2 = SubAdmin2::find($this->admin2Code);
			abort_if(empty($admin2), 404, t('admin_division_not_found'));
			
			// $this->admin1Code = $admin2->subadmin1_code ?? $this->admin1Code;
			$this->countryCode = $admin2->country_code ?? $this->countryCode;
		}
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
		$singularTxt = trans('admin.city');
		$pluralTxt = trans('admin.cities');
		
		if (!empty($admin2) && !empty($country)) {
			$this->xPanel->addClause('where', 'country_code', '=', $this->countryCode);
			$this->xPanel->addClause('where', 'subadmin2_code', '=', $this->admin2Code);
			$this->xPanel->setParentKeyColumn('subadmin2_code');
			
			$uri = !empty($admin1)
				? "countries/{$this->countryCode}/admins1/{$this->admin1Code}/admins2/{$this->admin2Code}/cities"
				: "countries/{$this->countryCode}/admins2/{$this->admin2Code}/cities";
			$label = !empty($admin1) ? "{$admin1->name}, {$admin2->name}" : $admin2->name;
			$label = "{$label}, {$country->name}";
			$singularTxt = "{$singularTxt} &rarr; {$label}";
			$pluralTxt = "{$pluralTxt} &rarr; {$label}";
			
			$parentUri = dirname($uri, 2);
			$parentLabel = !empty($admin1) ? $admin2->name : $country->name;
			$parentSingularTxt = trans('admin.admin division 2') . " &rarr; {$parentLabel}";
			$parentPluralTxt = trans('admin.admin divisions 2') . " &rarr; {$parentLabel}";
		} else {
			if (!empty($admin1) && !empty($country)) {
				$this->xPanel->addClause('where', 'country_code', '=', $this->countryCode);
				$this->xPanel->addClause('where', 'subadmin1_code', '=', $this->admin1Code);
				$this->xPanel->setParentKeyColumn('subadmin1_code');
				
				$uri = "countries/{$this->countryCode}/admins1/{$this->admin1Code}/cities";
				$label = "{$admin1->name}, {$country->name}";
				$singularTxt = "{$singularTxt} &rarr; {$label}";
				$pluralTxt = "{$pluralTxt} &rarr; {$label}";
				
				$parentUri = dirname($uri, 2);
				$parentLabel = $country->name;
				$parentSingularTxt = trans('admin.admin division 1') . " &rarr; {$parentLabel}";
				$parentPluralTxt = trans('admin.admin divisions 1') . " &rarr; {$parentLabel}";
			} else {
				if (!empty($country)) {
					$this->xPanel->addClause('where', 'country_code', '=', $this->countryCode);
					$this->xPanel->setParentKeyColumn('country_code');
					
					$uri = "countries/{$this->countryCode}/cities";
					$singularTxt = "{$singularTxt} &rarr; {$country->name}";
					$pluralTxt = "{$pluralTxt} &rarr; {$country->name}";
					
					$parentUri = dirname($uri, 2);
					$parentSingularTxt = trans('admin.country');
					$parentPluralTxt = trans('admin.countries');
				} else {
					$uri = "cities";
				}
			}
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->with(['country', 'subAdmin1', 'subAdmin2']);
		$this->xPanel->setRoute(urlGen()->adminUri($uri));
		$this->xPanel->setEntityNameStrings($singularTxt, $pluralTxt);
		
		// Has Parent
		if (
			(!empty($admin2) || !empty($admin1) || !empty($country))
			&& !empty($parentUri)
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
				'name'  => 'country_code',
				'label' => trans('admin.Country Code'),
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'name',
				'label' => trans('admin.Name'),
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'subadmin2_code',
				'label'         => trans('admin.Admin2 Code'),
				'type'          => 'model_function',
				'function_name' => 'crudAdmin2Column',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'subadmin1_code',
				'label'         => trans('admin.Admin1 Code'),
				'type'          => 'model_function',
				'function_name' => 'crudAdmin1Column',
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
				'name'    => 'id',
				'type'    => 'hidden',
				'default' => $this->autoIncrementId(),
			], 'create');
			
			if (!empty($this->countryCode)) {
				$this->xPanel->addField([
					'name'  => 'country_code',
					'type'  => 'hidden',
					'value' => $this->countryCode,
				], 'create');
			} else {
				$this->xPanel->addField([
					'name'       => 'country_code',
					'label'      => trans('admin.Country Code'),
					'type'       => 'select2',
					'attribute'  => 'name',
					'model'      => Country::class,
					'attributes' => [
						'placeholder' => trans('admin.Enter the country code'),
					],
				]);
			}
			
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
			
			if (!empty($this->admin2Code)) {
				$this->xPanel->addField([
					'name'  => 'subadmin2_code',
					'type'  => 'hidden',
					'value' => $this->admin2Code,
				], 'create');
			} else {
				$this->xPanel->addField([
					'name'        => 'subadmin2_code',
					'label'       => trans('admin.Admin2 Code'),
					'type'        => 'select2_from_array',
					'options'     => $this->subAdmin2s(),
					'allows_null' => true,
				]);
			}
			
			$this->xPanel->addField([
				'name'       => 'name',
				'label'      => trans('admin.Name'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Enter the country name'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'latitude',
				'label'      => trans('admin.Latitude'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Latitude'),
				],
				'hint'       => trans('admin.In decimal degrees'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'longitude',
				'label'      => trans('admin.Longitude'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Longitude'),
				],
				'hint'       => trans('admin.In decimal degrees'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'       => 'population',
				'label'      => trans('admin.Population'),
				'type'       => 'text',
				'attributes' => [
					'placeholder' => trans('admin.Population'),
				],
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'        => 'time_zone',
				'label'       => trans('admin.time_zone_label'),
				'type'        => 'select2_from_array',
				'options'     => TimeZoneManager::getTimeZones(),
				'allows_null' => true,
				'hint'        => trans('admin.time_zone_hint'),
				'wrapper'     => [
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
		return parent::storeCrud();
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		return parent::updateCrud();
	}
	
	/**
	 * Increment new cities IDs
	 * NOTE: Obsolete if the ID column is auto-incremented on the MySQL side
	 *
	 * @return int
	 */
	public function autoIncrementId(): int
	{
		// Note: 10793747 is the higher ID found in Geonames cities database
		// To guard against any MySQL error we will increment new IDs from 14999999
		$startId = 14999999;
		
		// Count all non-Geonames entries
		$lastAddedEntry = City::query()
			->withoutGlobalScope(ActiveScope::class)
			->where('id', '>=', $startId)
			->orderByDesc('id')
			->first();
		$lastAddedId = (!empty($lastAddedEntry)) ? (int)$lastAddedEntry->id : $startId;
		$lastAddedId = ($lastAddedId >= $startId) ? $lastAddedId : $startId;
		
		// Set new ID
		return $lastAddedId + 1;
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
	
	private function subAdmin2s(): array
	{
		// Get the Admin1 Code
		if (empty($this->admin1Code)) {
			$cityId = request()->segment(5);
			if (!empty($cityId)) {
				$city = $this->xPanel->model->query()->find($cityId);
				if (!empty($city)) {
					$this->admin1Code = $city->subadmin1_code ?? null;
				}
			}
		}
		
		// Get the Administrative Divisions
		$admins = SubAdmin2::query()
			->inCountry($this->countryCode)
			->where('subadmin1_code', $this->admin1Code)
			->get();
		
		$tab = [];
		if ($admins->count() > 0) {
			foreach ($admins as $admin) {
				$tab[$admin->code] = $admin->name . ' (' . $admin->code . ')';
			}
		}
		
		return $tab;
	}
}
