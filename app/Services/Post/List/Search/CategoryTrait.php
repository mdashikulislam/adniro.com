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

use App\Models\Category;
use App\Services\Category\CategoryBy;

trait CategoryTrait
{
	use CategoryBy;
	
	/**
	 * Get Category (Auto-detecting ID or Slug)
	 *
	 * @param array $params
	 * @return mixed|null
	 */
	protected function getCategory(array $params = []): mixed
	{
		// Get the Category's right arguments
		$catParentId = data_get($params, 'c');
		$catId = data_get($params, 'sc');
		
		// Get the $catParentId as $catId when $catId is empty
		// And empty the $carParentId when that have the same value as $catId
		$catId = empty($catId) ? $catParentId : $catId;
		$catParentId = ($catParentId == $catId) ? null : $catParentId;
		
		// Validate parameters values
		$catParentId = isStringable($catParentId) ? $catParentId : null;
		$catId = isStringable($catId) ? $catId : null;
		
		// Get the Category
		$cat = null;
		if (!empty($catId)) {
			if (is_numeric($catId)) {
				$cat = $this->getCategoryById($catId);
			} else {
				$isCatIdString = is_string($catId);
				$isCatParentIdStringOrEmpty = (is_string($catParentId) || empty($catParentId));
				
				if ($isCatIdString && $isCatParentIdStringOrEmpty) {
					$cat = $this->getCategoryBySlug($catId, $catParentId);
				}
			}
			
			if (empty($cat)) {
				abort(404, t('category_not_found'));
			}
		}
		
		return $cat;
	}
	
	/**
	 * Get Root Categories
	 *
	 * @return mixed
	 */
	protected function getRootCategories(): mixed
	{
		$locale = config('app.locale');
		$limit = getNumberOfItemsToTake('categories');
		
		// Cache Parameters
		$cacheParams = [
			'action'  => 'get.root.categories',
			'locale'  => $locale,
			'orderBy' => 'lft',
			'limit'   => $limit,
		];
		
		$cats = caching()->remember(Category::class, $cacheParams, function () use ($limit) {
			return Category::roots()->orderBy('lft')->take($limit)->get();
		});
		
		if ($cats->count() > 0) {
			$cats = $cats->keyBy('id');
		}
		
		return $cats;
	}
}
