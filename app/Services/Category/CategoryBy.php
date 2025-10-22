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

namespace App\Services\Category;

use App\Models\Category;
use Illuminate\Contracts\Database\Eloquent\Builder;

trait CategoryBy
{
	/**
	 * Get Category by Slug
	 * NOTE: Slug must be unique
	 *
	 * @param $catSlug
	 * @param null $parentCatSlug
	 * @param array $params
	 * @return mixed
	 */
	public function getCategoryBySlug($catSlug, $parentCatSlug = null, array $params = []): mixed
	{
		$limit = getNumberOfItemsToTake('categories');
		$locale = $params['languageCode'] ?? config('app.locale');
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'     => 'get.category',
			'slug'       => $catSlug,
			'parentSlug' => $parentCatSlug,
			'locale'     => $locale,
		]);
		
		// Cached Query
		return caching()->remember(Category::class, $cacheParams, function () use (
			$embed, $parentCatSlug, $catSlug, $locale, $limit
		) {
			$cat = Category::query();
			
			if (!empty($embed)) {
				if (in_array('children', $embed)) {
					$cat->with(['children' => fn (Builder $query) => $query->limit($limit)]);
				}
				if (in_array('parent', $embed)) {
					$cat->with('parent');
				}
			} else {
				$cat->with($this->getRelations());
			}
			
			if (!empty($parentCatSlug)) {
				$cat->whereHas('parent', fn ($query) => $query->where('slug', $parentCatSlug));
			}
			
			$cat = $cat->where('slug', $catSlug)->first();
			
			if (!empty($cat)) {
				$cat->setLocale($locale);
			}
			
			return $cat;
		});
	}
	
	/**
	 * Get Category by ID
	 *
	 * @param $catId
	 * @param array $params
	 * @return mixed
	 */
	public function getCategoryById($catId, array $params = []): mixed
	{
		$limit = getNumberOfItemsToTake('categories');
		$locale = $params['languageCode'] ?? config('app.locale');
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.category',
			'id'     => $catId,
			'locale' => $locale,
		]);
		
		return caching()->remember(Category::class, $cacheParams, function () use ($embed, $catId, $locale, $limit) {
			$cat = Category::query();
			
			if (!empty($embed)) {
				if (in_array('children', $embed)) {
					$cat->with(['children' => fn (Builder $query) => $query->limit($limit)]);
				}
				if (in_array('parent', $embed)) {
					$cat->with('parent');
				}
			} else {
				$cat->with($this->getRelations());
			}
			
			$cat = $cat->where('id', $catId)->first();
			
			if (!empty($cat)) {
				$cat->setLocale($locale);
			}
			
			return $cat;
		});
	}
	
	/**
	 * @return array
	 */
	private function getRelations(): array
	{
		$limit = getNumberOfItemsToTake('categories');
		
		return [
			'parent',
			'parent.children' => fn (Builder $query) => $query->limit($limit),
			'parent.children.parent',
			'children'        => fn (Builder $query) => $query->limit($limit),
			'children.parent',
		];
	}
}
