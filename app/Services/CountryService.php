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
use App\Http\Resources\CountryResource;
use App\Http\Resources\EntityCollection;
use App\Models\Country;
use App\Models\Scopes\ActiveScope;
use App\Services\Country\itiTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

class CountryService extends BaseService
{
	use itiTrait;
	
	/**
	 * List countries
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$perPage = getNumberOfItemsPerPage('countries', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$keyword = $params['keyword'] ?? null;
		$isNonActiveIncluded = castIntToBool($params['includeNonActive'] ?? 0);
		$iti = $params['iti'] ?? null;
		$sort = $params['sort'] ?? [];
		
		// 'Intl Tel Input' options data
		if (!empty($iti)) {
			try {
				if ($iti == 'i18n') {
					return $this->i18n();
				}
				if ($iti == 'onlyCountries') {
					return $this->onlyCountries();
				}
				$message = 'No data available. Only "i18n" and "onlyCountries" are accepted for the "iti" parameter.';
				
				return apiResponse()->error($message);
			} catch (Throwable $e) {
				return apiResponse()->error($e->getMessage());
			}
		}
		
		// Normal countries list
		// ---
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.countries',
		]);
		
		// Cached Query
		$countries = caching()->remember(Country::class, $cacheParams, function () use (
			$perPage, $embed, $keyword, $isNonActiveIncluded, $sort
		) {
			$countries = Country::query();
			
			if (in_array('currency', $embed)) {
				$countries->with('currency');
			}
			
			if (!empty($keyword)) {
				$countries->where('name', 'LIKE', '%' . $keyword . '%');
			}
			if ($isNonActiveIncluded) {
				$countries->withoutGlobalScopes([ActiveScope::class]);
			} else {
				$countries->active();
			}
			
			// Sorting
			$countries = $this->applySorting($countries, ['name', 'code'], $sort);
			
			$countries = $countries->paginate($perPage);
			
			return PaginationHelper::adjustSides($countries);
		});
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$countries = setPaginationBaseUrl($countries);
		
		$resourceCollection = new EntityCollection(CountryResource::class, $countries, $params);
		
		$message = ($countries->count() <= 0) ? t('no_countries_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get country
	 *
	 * @param string $code
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(string $code, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.country',
		]);
		
		// Cached Query
		$country = caching()->remember(Country::class, $cacheParams, function () use ($code, $embed) {
			$country = Country::query()->where('code', '=', $code);
			
			if (in_array('currency', $embed)) {
				$country->with('currency');
			}
			
			return $country->first();
		});
		
		abort_if(empty($country), 404, t('country_not_found'));
		
		$resource = new CountryResource($country, $params);
		
		return apiResponse()->withResource($resource);
	}
}
