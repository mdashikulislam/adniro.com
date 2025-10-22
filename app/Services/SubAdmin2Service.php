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
use App\Http\Resources\EntityCollection;
use App\Http\Resources\SubAdmin2Resource;
use App\Models\SubAdmin2;
use Illuminate\Http\JsonResponse;

class SubAdmin2Service extends BaseService
{
	/**
	 * List admin. divisions (2)
	 *
	 * @param string $countryCode
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(string $countryCode, array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$perPage = getNumberOfItemsPerPage('subadmin2', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$admin1Code = $params['admin1Code'] ?? null;
		$keyword = $params['keyword'] ?? null;
		$sort = $params['sort'] ?? [];
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'  => 'get.subAdmins2',
			'country' => $countryCode,
			'locale'  => $locale,
		]);
		
		// Cached Query
		$admins2 = caching()->remember(SubAdmin2::class, $cacheParams, function () use (
			$perPage, $embed, $countryCode, $admin1Code, $keyword, $sort
		) {
			$admins2 = SubAdmin2::query();
			
			if (in_array('country', $embed)) {
				$admins2->with('country');
			}
			if (in_array('subAdmin1', $embed)) {
				$admins2->with('subAdmin1');
			}
			
			$admins2->where('country_code', '=', $countryCode);
			if (!empty($admin1Code)) {
				$admins2->where('subadmin1_code', '=', $admin1Code);
			}
			if (!empty($keyword)) {
				$admins2->where('name', 'LIKE', '%' . $keyword . '%');
			}
			
			// Sorting
			$admins2 = $this->applySorting($admins2, ['name'], $sort);
			
			$admins2 = $admins2->paginate($perPage);
			
			return PaginationHelper::adjustSides($admins2);
		});
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$admins2 = setPaginationBaseUrl($admins2);
		
		$resourceCollection = new EntityCollection(SubAdmin2Resource::class, $admins2, $params);
		
		$message = ($admins2->count() <= 0) ? t('no_admin_divisions_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get admin. division (2)
	 *
	 * @param string $code
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(string $code, array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.subAdmin2',
			'code'   => $code,
			'locale' => $locale,
		]);
		
		// Cached Query
		$admin2 = caching()->remember(SubAdmin2::class, $cacheParams, function () use ($code, $embed) {
			$admin2 = SubAdmin2::query()->where('code', '=', $code);
			
			if (in_array('country', $embed)) {
				$admin2->with('country');
			}
			if (in_array('subAdmin1', $embed)) {
				$admin2->with('subAdmin1');
			}
			
			return $admin2->first();
		});
		
		abort_if(empty($admin2), 404, t('admin_division_not_found'));
		
		$resource = new SubAdmin2Resource($admin2, $params);
		
		return apiResponse()->withResource($resource);
	}
}
