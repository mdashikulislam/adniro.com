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

use App\Services\PageService;

trait PageBySlug
{
	/**
	 * Get Page by Slug
	 * NOTE: Slug must be unique
	 *
	 * @param $slugOrId
	 * @param null $languageCode
	 * @return array|null
	 */
	private function getPageBySlugOrId($slugOrId, $languageCode = null): ?array
	{
		$languageCode = $languageCode ?? config('app.locale');
		
		// Get category
		$queryParams = [
			'languageCode'    => $languageCode,
			'cacheExpiration' => getGlobalCacheTtl(),
		];
		$data = getServiceData((new PageService())->getEntry($slugOrId, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		return is_array($apiResult) ? $apiResult : null;
	}
}
