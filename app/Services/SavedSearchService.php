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
use App\Helpers\Services\Search\PostQueries;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use App\Services\Post\List\Search\CategoryTrait;
use App\Services\Post\List\Search\LocationTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedSearchService extends BaseService
{
	use CategoryTrait, LocationTrait;
	
	/**
	 * List saved searches
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		$perPage = getNumberOfItemsPerPage('saved_search', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$countryCode = $params['countryCode'] ?? config('country.code');
		$orderBy = $params['orderBy'] ?? [];
		$sort = $params['sort'] ?? $orderBy;
		
		// Get Saved Searches
		$savedSearches = SavedSearch::inCountry($countryCode)
			->where('user_id', $authUser->getAuthIdentifier());
		
		if (in_array('user', $embed)) {
			$savedSearches->with('user');
		}
		
		if (in_array('country', $embed)) {
			$savedSearches->with('country');
		}
		
		// Sorting
		$savedSearches = $this->applySorting($savedSearches, ['created_at'], $sort);
		
		$savedSearches = $savedSearches->paginate($perPage);
		$savedSearches = PaginationHelper::adjustSides($savedSearches);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$savedSearches = setPaginationBaseUrl($savedSearches);
		
		$message = ($savedSearches->count() <= 0) ? t('no_saved_searches_found') : null;
		
		$resourceCollection = new EntityCollection(SavedSearchResource::class, $savedSearches, $params);
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get saved search
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id, array $params = []): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$countryCode = $params['countryCode'] ?? config('country.code');
		
		// Get Saved Searches
		$savedSearch = SavedSearch::inCountry($countryCode)
			->where('user_id', $authUser->getAuthIdentifier())
			->where('id', $id);
		
		if (in_array('user', $embed)) {
			$savedSearch->with('user');
		}
		
		if (in_array('country', $embed)) {
			$savedSearch->with('country');
		}
		
		$savedSearch = $savedSearch->first();
		
		abort_if(empty($savedSearch), 404, t('saved_search_not_found'));
		
		$resource = new SavedSearchResource($savedSearch, $params);
		$resource = $resource->toArray(request());
		
		// ...
		
		// Parse saved query string to array
		parse_str($savedSearch->query, $query);
		
		// Add query to request
		$params['q'] = $query['q'] ?? null;
		$params['c'] = $query['c'] ?? null;
		$params['sc'] = $query['sc'] ?? null;
		$params['l'] = $query['l'] ?? null;
		$params['location'] = $query['location'] ?? null;
		$params['r'] = $query['r'] ?? null;
		
		// Remove empty parameters
		$params = collect($params)->reject(fn ($item) => is_null($item))->toArray();
		
		// Get the listings type parameter
		$allowedFilters = ['search', 'premium'];
		$filterBy = $query['filterBy'] ?? null;
		$filterBy = in_array($filterBy, $allowedFilters) ? $filterBy : 'search';
		
		// Get the items per page number
		$perPage = getNumberOfItemsPerPage('posts', $params['perPage'] ?? null, $this->perPage);
		
		// Get the saved search order
		$orderBy = $query['orderBy'] ?? null;
		$orderBy = ($orderBy != 'random') ? $orderBy : null;
		
		$input = [
			'op'      => $filterBy,
			'perPage' => $perPage,
			'orderBy' => $orderBy,
		];
		$input = array_merge($params, $input);
		
		// PreSearch
		$location = $this->getLocation($input);
		$preSearch = [
			'cat'   => $this->getCategory($input),
			'city'  => $location['city'] ?? null,
			'admin' => $location['admin'] ?? null,
		];
		
		// Search
		$searchData = (new PostQueries($input, $preSearch))->fetch();
		
		$preSearch = $searchData['preSearch'] ?? [];
		$preSearch['query'] = $query;
		
		$posts = [
			'success' => true,
			'message' => $searchData['message'] ?? null,
			'result'  => $searchData['posts'] ?? [],
			'extra'   => [
				'count'     => $searchData['count'] ?? [],
				'preSearch' => $preSearch,
				'sidebar'   => [],
				'tags'      => $searchData['tags'] ?? [],
			],
		];
		
		$resource['posts'] = $posts;
		
		// Result
		$data = [
			'success' => true,
			'message' => null,
			'result'  => $resource,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Store/Delete saved search
	 *
	 * Save a search result in favorite, or remove it from favorite.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(Request $request): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$data = [
			'success' => false,
			'result'  => null,
		];
		
		// Get the 'url' field
		$queryUrl = $request->input('search_url');
		if (empty($queryUrl)) {
			$data['message'] = 'The "url" field need to be filled.';
			
			return apiResponse()->json($data, 400);
		}
		
		// Extract the keyword by extracting the 'q' parameter of the filled 'url'
		$tmp = parse_url($queryUrl);
		$query = $tmp['query'];
		parse_str($query, $tab);
		$keyword = $tab['q'];
		
		// Get the 'results_count' field
		$resultsCount = $request->input('results_count');
		if ($keyword == '') {
			$data['message'] = 'The "results_count" field need to be filled.';
			
			return apiResponse()->json($data, 400);
		}
		
		$data['success'] = true;
		
		$savedSearches = SavedSearch::where('user_id', $authUser->getAuthIdentifier())
			->where('keyword', $keyword)
			->where('query', $query);
		
		if ($savedSearches->count() > 0) {
			$savedSearches = $savedSearches->get();
			
			// Delete SavedSearch
			foreach ($savedSearches as $savedSearch) {
				$savedSearch->delete();
			}
			
			$data['message'] = t('Search deleted successfully');
		} else {
			// Store SavedSearch
			$savedSearchArray = [
				'country_code' => config('country.code'),
				'user_id'      => $authUser->getAuthIdentifier(),
				'keyword'      => $keyword,
				'query'        => $query,
				'count'        => $resultsCount,
			];
			$savedSearch = new SavedSearch($savedSearchArray);
			$savedSearch->save();
			
			$resource = new SavedSearchResource($savedSearch);
			
			$data['message'] = t('Search saved successfully');
			$data['result'] = $resource;
		}
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete saved search(es)
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$data = [
			'success' => false,
			'message' => t('no_deletion_is_done'),
			'result'  => null,
		];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $id) {
			$savedSearch = SavedSearch::query()
				->where('user_id', $authUser->getAuthIdentifier())
				->where('id', $id)
				->first();
			
			if (!empty($savedSearch)) {
				$res = $savedSearch->delete();
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			
			$count = count($ids);
			if ($count > 1) {
				$data['message'] = t('x entities have been deleted successfully', ['entities' => t('saved searches'), 'count' => $count]);
			} else {
				$data['message'] = t('1 entity has been deleted successfully', ['entity' => t('saved search')]);
			}
		}
		
		return apiResponse()->json($data);
	}
}
