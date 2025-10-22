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

namespace App\Services;

use App\Helpers\Common\PaginationHelper;
use App\Http\Resources\CityResource;
use App\Http\Resources\EntityCollection;
use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class CityService extends BaseService
{
	/**
	 * List cities
	 *
	 * @param string $countryCode
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(string $countryCode, array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$perPage = getNumberOfItemsPerPage('cities', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$admin1Code = $params['admin1Code'] ?? '';
		$admin2Code = $params['admin2Code'] ?? '';
		$keyword = $params['keyword'] ?? '';
		$autocomplete = castIntToBool($params['autocomplete'] ?? 0);
		$sort = $params['sort'] ?? [];
		
		$firstOrderByPopulation = $params['firstOrderByPopulation'] ?? null;
		$firstOrderByPopulation = (!empty($firstOrderByPopulation) && in_array($firstOrderByPopulation, ['desc', 'asc']))
			? $firstOrderByPopulation
			: null;
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'  => 'get.cities',
			'country' => $countryCode,
			'locale'  => $locale,
		]);
		
		// Cached Query
		$cities = caching()->remember(City::class, $cacheParams, function () use (
			$perPage, $embed, $countryCode, $admin1Code, $admin2Code, $keyword, $autocomplete, $firstOrderByPopulation, $sort
		) {
			$cities = City::query();
			
			if (in_array('country', $embed)) {
				$cities->with('country');
			}
			if (in_array('subAdmin1', $embed)) {
				$cities->with('subAdmin1');
			}
			if (in_array('subAdmin2', $embed)) {
				$cities->with('subAdmin2');
			}
			if (in_array('countPosts', $embed)) {
				$cities->withCount(['posts' => function (Builder $query) {
					$query->verified()->reviewed()->unarchived();
				}]);
			}
			
			$cities->where('country_code', '=', $countryCode);
			if (!empty($admin1Code)) {
				$cities->where('subadmin1_code', '=', $admin1Code);
			}
			if (!empty($admin2Code)) {
				$cities->where('subadmin2_code', '=', $admin2Code);
			}
			if (!empty($keyword)) {
				if ($autocomplete) {
					$cities->where('name', 'LIKE', $keyword . '%');
				} else {
					$cities->where('name', 'LIKE', '%' . $keyword . '%');
				}
			}
			
			// Get the most or least populated city
			// Example: This is called from the contact form page
			if (!empty($firstOrderByPopulation)) {
				return $cities->orderBy('population', $firstOrderByPopulation)->first();
			}
			
			// Sorting
			$cities = $this->applySorting($cities, ['name', 'population'], $sort);
			
			$cities = $cities->paginate($perPage);
			
			return PaginationHelper::adjustSides($cities);
		});
		
		// If an object is returned (instead of a Collection), then return it with its Resource
		if ($cities instanceof City) {
			return $this->returnResource($cities, $params);
		}
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$cities = setPaginationBaseUrl($cities);
		
		$resourceCollection = new EntityCollection(CityResource::class, $cities, $params);
		
		$message = ($cities->count() <= 0) ? t('no_cities_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get city
	 *
	 * @param int $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(int $id, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.city',
			'id'     => $id,
		]);
		
		// Cached Query
		$city = caching()->remember(City::class, $cacheParams, function () use ($id, $embed) {
			$city = City::query()->where('id', $id);
			
			if (in_array('country', $embed)) {
				$city->with('country');
			}
			if (in_array('subAdmin1', $embed)) {
				$city->with('subAdmin1');
			}
			if (in_array('subAdmin2', $embed)) {
				$city->with('subAdmin2');
			}
			
			return $city->first();
		});
		
		return $this->returnResource($city, $params);
	}
	
	/**
	 * @param $city
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	private function returnResource($city, array $params = []): JsonResponse
	{
		abort_if(empty($city), 404, t('city_not_found'));
		
		$resource = new CityResource($city, $params);
		
		return apiResponse()->withResource($resource);
	}
}
