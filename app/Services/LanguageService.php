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

use App\Http\Resources\EntityCollection;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Models\Scopes\ActiveScope;
use Illuminate\Http\JsonResponse;

class LanguageService extends BaseService
{
	/**
	 * List languages
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$isNonActiveIncluded = castIntToBool($params['includeNonActive'] ?? 0);
		$sort = $params['sort'] ?? [];
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.languages',
		]);
		
		// Cached Query
		$languages = caching()->remember(Language::class, $cacheParams, function () use ($isNonActiveIncluded, $sort) {
			$languages = Language::query();
			
			if ($isNonActiveIncluded) {
				$languages->withoutGlobalScopes([ActiveScope::class]);
			} else {
				$languages->active();
			}
			
			// Sorting
			$languages = $this->applySorting($languages, ['lft'], $sort);
			
			return $languages->get();
		});
		
		$resourceCollection = new EntityCollection(LanguageResource::class, $languages, $params);
		
		$message = ($languages->count() <= 0) ? t('no_languages_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get language
	 *
	 * @param string $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(string $code): JsonResponse
	{
		// Cache Parameters
		$cacheParams = [
			'action' => 'get.language',
			'code'   => $code,
		];
		
		$language = caching()->remember(Language::class, $cacheParams, function () use ($code) {
			$language = Language::query()->where('code', '=', $code);
			
			return $language->first();
		});
		
		abort_if(empty($language), 404, t('language_not_found'));
		
		$resource = new LanguageResource($language);
		
		return apiResponse()->withResource($resource);
	}
}
