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

namespace App\Services\Post\List;

use App\Helpers\Services\Search\PostQueries;
use App\Models\CategoryField;
use App\Models\Post;
use App\Services\Post\List\Search\CategoryTrait;
use App\Services\Post\List\Search\LocationTrait;
use App\Services\Post\List\Search\SidebarTrait;
use Illuminate\Http\JsonResponse;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

trait SearchTrait
{
	use CategoryTrait, LocationTrait, SidebarTrait;
	
	/**
	 * @param string $op
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function getPostsBySearch(string $op, array $params = []): JsonResponse
	{
		// Create the MySQL Distance Calculation function If it doesn't exist
		$distanceCalculationFormula = config('settings.listings_list.distance_calculation_formula', 'haversine');
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists($distanceCalculationFormula)) {
			DistanceHelper::createDistanceCalculationFunction($distanceCalculationFormula);
		}
		
		$preSearch = [];
		$fields = collect();
		
		$perPage = getNumberOfItemsPerPage('posts', $params['perPage'] ?? null, $this->perPage);
		
		$embed = ['user', 'savedByLoggedUser', 'picture', 'pictures', 'payment', 'package'];
		if (!config('settings.listings_list.hide_post_type')) {
			$embed[] = 'postType';
		}
		if (!config('settings.listings_list.hide_category')) {
			$embed[] = 'category';
			$embed[] = 'parent';
		}
		if (!config('settings.listings_list.hide_location')) {
			$embed[] = 'city';
		}
		$params['embed'] = $embed;
		
		$orderBy = $params['orderBy'] ?? null;
		$orderBy = ($orderBy != 'random') ? $orderBy : null;
		
		$input = [
			'op'      => $op,
			'perPage' => $perPage,
			'orderBy' => $orderBy,
		];
		$input = array_merge($params, $input);
		
		$searchData = $this->searchPosts($input, $preSearch, $fields);
		$preSearch = $searchData['preSearch'] ?? $preSearch;
		
		$data = [
			'success' => true,
			'message' => $searchData['message'] ?? null,
			'result'  => $searchData['posts'],
			'extra'   => [
				'count'     => $searchData['count'] ?? [],
				'preSearch' => $preSearch,
				'sidebar'   => $this->getSidebar($preSearch, $fields->toArray(), $params),
				'tags'      => $searchData['tags'] ?? [],
			],
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * @param $input
	 * @param $preSearch
	 * @param $fields
	 * @return array
	 */
	private function searchPosts($input, &$preSearch, &$fields): array
	{
		$location = $this->getLocation($input);
		
		$preSearch = [
			'cat'       => $this->getCategory($input),
			'city'      => $location['city'] ?? null,
			'citiesIds' => $location['citiesIds'] ?? [],
			'admin'     => $location['admin'] ?? null,
		];
		
		if (!empty($preSearch['cat'])) {
			$categoryId = $preSearch['cat']->id;
			$fields = CategoryField::getFields($categoryId);
		}
		
		// Cache Parameters
		$cacheParams = array_merge($input, $preSearch, [
			'action' => 'get.listings',
		]);
		
		// Cached Query
		return caching()->remember(Post::class, $cacheParams, function () use ($input, $preSearch) {
			return (new PostQueries($input, $preSearch))->fetch();
		});
	}
}
