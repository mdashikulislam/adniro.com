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
use App\Http\Resources\CategoryResource;
use App\Http\Resources\EntityCollection;
use App\Models\Category;
use App\Services\Category\CategoryBy;
use Illuminate\Http\JsonResponse;

class CategoryService extends BaseService
{
	use CategoryBy;
	
	/**
	 * List categories
	 *
	 * @param int|null $parentId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(?int $parentId = null, array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$perPage = getNumberOfItemsPerPage('categories', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$areNestedEntriesIncluded = castIntToBool($params['nestedIncluded'] ?? 0);
		$parentId = !empty($parentId) ? $parentId : ($params['parentId'] ?? null);
		$sort = $params['sort'] ?? [];
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'   => 'get.categories',
			'parentId' => $parentId,
			'locale'   => $locale,
		]);
		
		// Cached Query
		$categories = caching()->remember(Category::class, $cacheParams, function () use (
			$perPage, $parentId, $embed, $areNestedEntriesIncluded, $sort
		) {
			$categories = Category::query();
			
			if (!empty($parentId)) {
				$categories->childrenOf($parentId);
			} else {
				if (!$areNestedEntriesIncluded) {
					$categories->roots();
				}
			}
			
			if (in_array('parent', $embed)) {
				$categories->with('parent');
			} else {
				$categories->with('parentClosure');
			}
			if (in_array('children', $embed)) {
				$categories->with('children');
			}
			
			// Sorting
			$categories = $this->applySorting($categories, ['lft'], $sort);
			
			if ($areNestedEntriesIncluded) {
				$categories = $categories->get();
				if ($categories->count() > 0) {
					$categories = $categories->keyBy('id');
				}
				
				return $categories;
			}
			
			$categories = $categories->paginate($perPage);
			$categories = PaginationHelper::adjustSides($categories);
			
			// Adding the withQueryString() to apply filters for AJAX request
			return $categories->withQueryString();
		});
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		if (!$areNestedEntriesIncluded) {
			$categories = setPaginationBaseUrl($categories);
		}
		
		$resourceCollection = new EntityCollection(CategoryResource::class, $categories, $params);
		
		$message = ($categories->count() <= 0) ? t('no_categories_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get category
	 *
	 * Get category by its unique slug or ID.
	 *
	 * @param int|string $slugOrId
	 * @param string|null $parentSlug
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(int|string $slugOrId, ?string $parentSlug = null, array $params = []): JsonResponse
	{
		$category = is_numeric($slugOrId)
			? $this->getCategoryById($slugOrId, $params)
			: $this->getCategoryBySlug($slugOrId, $parentSlug, $params);
		
		abort_if(empty($category), 404, t('category_not_found'));
		
		$resource = new CategoryResource($category, $params);
		
		return apiResponse()->withResource($resource);
	}
}
