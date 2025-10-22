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
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\Page\PageBy;
use Illuminate\Http\JsonResponse;

class PageService extends BaseService
{
	use PageBy;
	
	/**
	 * List pages
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$perPage = getNumberOfItemsPerPage('pages', $params['perPage'] ?? null, $this->perPage);
		$sort = $params['sort'] ?? [];
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.pages',
			'locale' => $locale,
		]);
		
		// Cached Query
		$pages = caching()->remember(Page::class, $cacheParams, function () use ($perPage, $sort) {
			$pages = Page::query();
			
			// Sorting
			$pages = $this->applySorting($pages, ['lft', 'created_at'], $sort);
			
			$pages = $pages->paginate($perPage);
			
			return PaginationHelper::adjustSides($pages);
		});
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$pages = setPaginationBaseUrl($pages);
		
		$resourceCollection = new EntityCollection(PageResource::class, $pages, $params);
		
		$message = ($pages->count() <= 0) ? t('no_pages_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get page
	 *
	 * @param $slugOrId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($slugOrId, array $params = []): JsonResponse
	{
		$page = is_numeric($slugOrId) ? $this->getPageById($slugOrId) : $this->getPageBySlug($slugOrId);
		
		abort_if(empty($page), 404, t('page_not_found'));
		
		$resource = new PageResource($page, $params);
		
		return apiResponse()->withResource($resource);
	}
}
