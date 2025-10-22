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

namespace App\Services\Post\List\Search;

use App\Models\City;
use App\Models\SubAdmin1;
use App\Models\SubAdmin2;
use Illuminate\Database\Eloquent\Collection;

trait LocationTrait
{
	/**
	 * @param array $params
	 * @return array
	 */
	protected function getLocation(array $params = []): array
	{
		// Get the Location's right arguments
		$cityId = data_get($params, 'l');
		$cityName = data_get($params, 'location');
		$regionName = data_get($params, 'r'); // From SVG maps
		
		// Validate parameters values
		$cityId = is_numeric($cityId) ? (int)$cityId : null;
		$cityName = is_string($cityName) ? trim($cityName) : null;
		$regionName = is_string($regionName) ? trim($regionName) : null;
		
		$city = null;
		$admin = null;
		
		// City ID provided (or an auto-completed city selected)
		if (!empty($cityId)) {
			// Get City
			$city = $this->getCityById($cityId);
			abort_if(empty($city), 404, t('city_not_found'));
			
			return [
				'city'  => $city,
				'admin' => null,
			];
		}
		
		// From form input (no auto-completed city selected)
		if (!empty($cityName)) {
			// Is administrative division searched?
			// i.e. Does $cityName begin with magic word "area:" - Example: "area:New York"
			if ($this->isAdminDivisionSearched($cityName)) {
				$adminName = $this->extractSearchedAdminDivisionName($cityName);
				
				data_forget($params, 'l');
				data_forget($params, 'location');
				data_forget($params, 'distance');
				
				data_set($params, 'country', config('country.code'));
				data_set($params, 'r', $adminName);
				
				$admin = $this->getAdmin($adminName, $params);
				$this->permuteVarsValuesByTypeOfModel($city, $admin);
				
				if (!empty($admin)) {
					return [
						'city'  => $city,
						'admin' => $admin,
					];
				}
			} else {
				// Find all cities whose names contain "$cityName", then get their IDs as array
				$citiesIds = $this->getCitiesIds($cityName);
				if (count($citiesIds) > 0) {
					// Is one city found?
					if (count($citiesIds) === 1) {
						$firstCityId = array_values($citiesIds)[0];
						$city = $this->getCityById($firstCityId);
						
						return [
							'city'  => $city,
							'admin' => null,
						];
					}
					
					// Many cities are found
					return [
						'city'      => null,
						'admin'     => null,
						'citiesIds' => $citiesIds,
					];
				}
			}
			
			// Location not found. Apply fallback rules.
			if (empty($city)) {
				if (!in_array(config('settings.listings_list.fake_locations_results'), [1, 2])) {
					abort(404, t('city_not_found'));
				} else {
					data_forget($params, 'r');
					data_forget($params, 'l');
					data_forget($params, 'location');
					
					if (config('settings.listings_list.fake_locations_results') == 1) {
						$city = $this->getPopularCity();
						if (!empty($city)) {
							data_set($params, 'l', $city->id);
							data_set($params, 'location', $city->name);
						}
					}
				}
			}
		}
		
		// From SVG maps
		if (!empty($regionName)) {
			$admin = $this->getAdmin($regionName);
			
			if (empty($admin)) {
				if (!in_array(config('settings.listings_list.fake_locations_results'), [1, 2])) {
					abort(404, t('admin_division_not_found'));
				} else {
					data_forget($params, 'r');
					data_forget($params, 'l');
					data_forget($params, 'location');
					
					if (config('settings.listings_list.fake_locations_results') == 1) {
						$city = $this->getPopularCity();
						if (!empty($city)) {
							data_set($params, 'l', $city->id);
							data_set($params, 'location', $city->name);
						}
					}
				}
			}
		}
		
		$this->permuteVarsValuesByTypeOfModel($city, $admin);
		
		return [
			'city'  => $city,
			'admin' => $admin,
		];
	}
	
	/**
	 * Get City by ID
	 *
	 * @param null $cityId
	 * @return \App\Models\City|null
	 */
	private function getCityById($cityId = null): ?City
	{
		if (empty($cityId)) return null;
		
		$cityId = (int)$cityId;
		
		// Cache Parameters
		$cacheParams = [
			'action' => 'get.city',
			'find'   => $cityId,
		];
		
		return caching()->remember(City::class, $cacheParams, function () use ($cityId) {
			return City::find($cityId);
		});
	}
	
	/**
	 * Get all cities whose names contain "$cityName", then get their IDs as array
	 *
	 * @param string|null $cityName
	 * @return array
	 */
	private function getCitiesIds(?string $cityName): array
	{
		if (empty($cityName)) return [];
		if ($this->isAdminDivisionSearched($cityName)) return [];
		
		$relations = ['posts', 'subAdmin2', 'subAdmin1'];
		$cityName = rawurldecode($cityName);
		
		// Cache Parameters
		$cacheParams = [
			'action'    => 'get.cities.ids',
			'relations' => implode(',', $relations),
			'name'      => $cityName,
		];
		
		$cities = caching()->remember(City::class, $cacheParams, function () use ($relations, $cityName) {
			$cities = City::without($relations)->inCountry()->where('name', 'LIKE', '%' . $cityName . '%');
			
			return $cities->get(['id']);
		});
		
		return ($cities->count() > 0) ? collect($cities)->keyBy('id')->keys()->toArray() : [];
	}
	
	/**
	 * Get Administrative Division
	 *
	 * @param string|null $adminName
	 * @param array $params
	 * @return \App\Models\SubAdmin1|\App\Models\SubAdmin2|\App\Models\City|null
	 */
	private function getAdmin(string $adminName = null, array $params = []): SubAdmin2|SubAdmin1|City|null
	{
		$cityId = data_get($params, 'l');
		if (empty($adminName) || !empty($cityId)) {
			return null;
		}
		
		$isAdminCode = $this->isAdminCode($adminName);
		
		$adminType = config('country.admin_type', 0);
		if (in_array($adminType, ['1', '2'])) {
			if (!$isAdminCode) {
				$adminName = rawurldecode($adminName);
			}
			
			$adminModel = '\App\Models\SubAdmin' . $adminType;
			
			// Cache Parameters
			$cacheParams = [
				'action'      => 'get.subAdmin',
				'isAdminCode' => $isAdminCode,
				'name'        => $adminName,
			];
			
			return caching()->remember($adminModel, $cacheParams, function () use ($adminModel, $adminName, $isAdminCode) {
				/**
				 * @var \App\Models\SubAdmin1|\App\Models\SubAdmin2 $adminModel
				 */
				$admin = $adminModel::inCountry();
				if ($isAdminCode) {
					$admin = $admin->where('code', '=', $adminName);
				} else {
					$admin = $admin->where('name', 'LIKE', $adminName);
				}
				$admin = $admin->first();
				if (empty($admin)) {
					$admin = $adminModel::inCountry()->where('name', 'LIKE', $adminName . '%')->first();
					if (empty($admin)) {
						$admin = $adminModel::inCountry()->where('name', 'LIKE', '%' . $adminName)->first();
						if (empty($admin)) {
							$admin = $adminModel::inCountry()->where('name', 'LIKE', '%' . $adminName . '%')->first();
							if (empty($admin)) {
								$admin = $this->getSimilarAdminByName($adminModel, $adminName);
							}
						}
					}
				}
				
				return $admin;
			});
		}
		
		// Cache Parameters
		$cacheParams = [
			'action'       => 'get.subAdminPopularCity',
			'country'      => config('country.code'),
			'subAdminName' => $adminName,
		];
		
		// Get the Popular City in the Admin. Division (And set it as filter)
		$city = caching()->remember(City::class, $cacheParams, function () use ($adminName) {
			return $this->getAdminDivisionByNameAndGetItsPopularCity($adminName, false);
		});
		
		if (!empty($city)) {
			data_forget($params, 'r');
			
			data_set($params, 'l', $city->id);
			data_set($params, 'location', $adminName);
		}
		
		return $city;
	}
	
	/**
	 * Get the Popular City in the Administrative Division
	 *
	 * @param $adminName
	 * @param bool $countryPopularCityAsFallback
	 * @return \App\Models\City|null
	 */
	private function getAdminDivisionByNameAndGetItsPopularCity($adminName, bool $countryPopularCityAsFallback = true): ?City
	{
		if (trim($adminName) == '') {
			return ($countryPopularCityAsFallback) ? $this->getPopularCity() : null;
		}
		
		$isAdminCode = $this->isAdminCode($adminName);
		
		// Init.
		if (!$isAdminCode) {
			$adminName = rawurldecode($adminName);
		}
		
		// Get Admin 1
		$admin1 = SubAdmin1::inCountry();
		if ($isAdminCode) {
			$admin1 = $admin1->where('code', '=', $adminName);
		} else {
			$admin1 = $admin1->where('name', 'LIKE', '%' . $adminName . '%')->orderBy('name');
		}
		$admin1 = $admin1->first();
		if (empty($admin1)) {
			$admin1 = $this->getSimilarAdminByName('SubAdmin1', $adminName);
		}
		
		// Get Admins 2
		if (!empty($admin1)) {
			$admins2 = SubAdmin2::inCountry()
				->where('subadmin1_code', $admin1->code)
				->orderBy('name')
				->get(['code']);
		} else {
			$admins2 = SubAdmin2::inCountry();
			if ($isAdminCode) {
				$admins2 = $admins2->where('code', 'LIKE', $adminName . '%');
			} else {
				$admins2 = $admins2->where('name', 'LIKE', '%' . $adminName . '%');
			}
			$admins2 = $admins2->orderBy('name')->get(['code']);
			if ($admins2->count() <= 0) {
				$admins2 = $this->getSimilarAdminByName('SubAdmin2', $adminName, true);
			}
		}
		
		// Split the Admin Name value, ...
		// If $admin1 and $admins2 are not found
		if (empty($admin1) && ($admins2 instanceof Collection && $admins2->count() <= 0)) {
			$tmp = preg_split('#(-|\s)+#', $adminName);
			
			// Sort by length DESC
			usort($tmp, fn ($a, $b) => strlen($b) - strlen($a));
			
			if (count($tmp) > 0) {
				foreach ($tmp as $partOfAdminName) {
					// Get Admin 1
					$admin1 = SubAdmin1::inCountry()
						->where('name', 'LIKE', '%' . $partOfAdminName . '%')
						->orderBy('name')
						->first();
					
					// Get Admins 2
					if (!empty($admin)) {
						$admins2 = SubAdmin2::inCountry()->where('subadmin1_code', $admin1->code)
							->orderBy('name')
							->get(['code']);
						
						// If $admin1 is found, $admins2 is optional
						break;
					} else {
						$admins2 = SubAdmin2::inCountry()
							->where('name', 'LIKE', '%' . $partOfAdminName . '%')
							->orderBy('name')
							->get(['code']);
						
						// If $admin1 is null, $admins2 is required
						if ($admins2->count() > 0) {
							break;
						}
					}
				}
			}
		}
		
		// Get City
		$city = null;
		if (!empty($admin1)) {
			if (!is_null($admins2) && $admins2->count() > 0) {
				$city = City::inCountry()
					->where('subadmin1_code', $admin1->code)
					->whereIn('subadmin2_code', $admins2->pluck('code')->toArray())
					->orderByDesc('population')
					->first();
				if (empty($city)) {
					$city = City::inCountry()
						->where('subadmin1_code', $admin1->code)
						->orderByDesc('population')
						->first();
				}
			} else {
				$city = City::inCountry()
					->where('subadmin1_code', $admin1->code)
					->orderByDesc('population')
					->first();
			}
		} else {
			if (!is_null($admins2) && $admins2->count() > 0) {
				$city = City::inCountry()
					->whereIn('subadmin2_code', $admins2->pluck('code')->toArray())
					->orderByDesc('population')
					->first();
			} else {
				if ($countryPopularCityAsFallback) {
					// If the Popular City in the Administrative Division is not found,
					// Get the Popular City in the Country.
					$city = $this->getPopularCity();
				}
			}
		}
		
		if ($countryPopularCityAsFallback) {
			// If no city is found, Get the Country's popular City
			if (empty($city)) {
				$city = $this->getPopularCity();
			}
		}
		
		return $city;
	}
	
	/**
	 * Get the Popular City in the Country
	 *
	 * @return \App\Models\City|null
	 */
	private function getPopularCity(): ?City
	{
		return City::inCountry()->orderByDesc('population')->first();
	}
	
	/**
	 * @param string $adminModel
	 * @param string|null $adminName
	 * @param bool $asCollection
	 * @return \App\Models\SubAdmin1|\App\Models\SubAdmin2|\Illuminate\Database\Eloquent\Collection|null
	 */
	private function getSimilarAdminByName(
		string  $adminModel,
		?string $adminName,
		bool    $asCollection = false
	): SubAdmin1|SubAdmin2|Collection|null
	{
		if (empty($adminName)) {
			return $asCollection ? (new SubAdmin1)->newCollection() : null;
		}
		
		$modelsPath = '\App\Models\\';
		if (!str_starts_with($adminModel, $modelsPath)) {
			$adminModel = $modelsPath . $adminModel;
		}
		
		$adminNameSpace = str_replace('-', ' ', $adminName);
		
		/**
		 * @var \App\Models\SubAdmin1|\App\Models\SubAdmin2 $adminModel
		 */
		$admin = $adminModel::inCountry()->where('name', 'LIKE', '%' . $adminNameSpace . '%');
		if ($admin->count() <= 0) {
			$adminNameDash = str_replace(' ', '-', $adminName);
			$admin = $adminModel::inCountry()->where('name', 'LIKE', '%' . $adminNameDash . '%');
		}
		
		return $asCollection ? $admin->get(['code']) : $admin->first();
	}
	
	/**
	 * Check if the admin name starts by two letters following by a dot (.) and with other characters
	 *
	 * @param string|null $adminName
	 * @return bool
	 */
	private function isAdminCode(?string $adminName): bool
	{
		// Admin. division custom prefix
		// $customPrefix = config('larapen.core.locationCodePrefix', 'Z');
		
		return (bool)preg_match('#^[a-z]{2}\.(.+)$#i', $adminName);
	}
	
	/**
	 * @param string|null $location
	 * @return bool
	 */
	private function isAdminDivisionSearched(?string $location): bool
	{
		return !empty($this->extractSearchedAdminDivisionName($location));
	}
	
	/**
	 * Search by administrative division name with magic word "area:" - Example: "area:New York"
	 *
	 * @param string|null $location
	 * @return string|null
	 */
	private function extractSearchedAdminDivisionName(?string $location): ?string
	{
		if (empty($location)) return null;
		
		$adminName = null;
		
		$location = preg_replace('/\s+:/', ':', trim($location));
		
		// Current Local
		$areaText = t('area');
		if (str_starts_with($location, $areaText)) {
			$adminName = last(explode($areaText, $location));
			$adminName = trim($adminName);
		}
		
		// Main Local
		$areaText = t('area', [], 'global', config('appLang.code'));
		if (str_starts_with($location, $areaText)) {
			$adminName = last(explode($areaText, $location));
			$adminName = trim($adminName);
		}
		
		return $adminName;
	}
	
	/**
	 * Set the right entity to the right variable
	 *
	 * @param \App\Models\City|\App\Models\SubAdmin1|\App\Models\SubAdmin2|null $city
	 * @param \App\Models\SubAdmin1|\App\Models\SubAdmin2|\App\Models\City|null $admin
	 * @return void
	 */
	private function permuteVarsValuesByTypeOfModel(City|null|SubAdmin1|SubAdmin2 &$city, SubAdmin1|SubAdmin2|null|City &$admin): void
	{
		if ($city instanceof SubAdmin1) {
			$admin = $city;
			$city = null;
		}
		if ($city instanceof SubAdmin2) {
			$admin = $city;
			$city = null;
		}
		if ($admin instanceof City) {
			$city = $admin;
			$admin = null;
		}
	}
}
