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

namespace App\Http\Controllers\Web\Front\Traits\Sluggable;

use App\Services\CategoryService;

trait CategoryBySlug
{
	/**
	 * @param $slug
	 * @param null $parentSlug
	 * @param string|null $languageCode
	 * @return array|null
	 */
	private function getCategoryBySlug($slug, $parentSlug = null, ?string $languageCode = null): ?array
	{
		if (empty($slug)) return null;
		
		$languageCode = $languageCode ?? config('app.locale');
		
		// Get category
		$queryParams = [
			'embed'           => 'children,parent',
			'languageCode'    => $languageCode,
			'cacheExpiration' => getGlobalCacheTtl(),
		];
		$data = getServiceData((new CategoryService())->getEntry($slug, $parentSlug, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		return is_array($apiResult) ? $apiResult : null;
	}
	
	/**
	 * @param int|null $catId
	 * @param string|null $languageCode
	 * @return array|null
	 */
	private function getCategoryById(?int $catId, ?string $languageCode = null): ?array
	{
		if (empty($catId)) return null;
		
		$languageCode = $languageCode ?? config('app.locale');
		
		// Get category
		$queryParams = [
			'embed'           => 'children,parent',
			'languageCode'    => $languageCode,
			'cacheExpiration' => getGlobalCacheTtl(),
		];
		$data = getServiceData((new CategoryService())->getEntry($catId, null, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		return is_array($apiResult) ? $apiResult : null;
	}
}
