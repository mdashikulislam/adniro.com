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

namespace App\Http\Controllers\Api;

use App\Services\SavedSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Saved Searches
 */
class SavedSearchController extends BaseController
{
	protected SavedSearchService $savedSearchService;
	
	/**
	 * @param \App\Services\SavedSearchService $savedSearchService
	 */
	public function __construct(SavedSearchService $savedSearchService)
	{
		parent::__construct();
		
		$this->savedSearchService = $savedSearchService;
	}
	
	/**
	 * List saved searches
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: user,country. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'perPage'     => request()->integer('perPage'),
			'embed'       => request()->input('embed'),
			'countryCode' => request()->input('countryCode'),
			'orderBy'     => request()->input('orderBy'),
		];
		
		return $this->savedSearchService->getEntries($params);
	}
	
	/**
	 * Get saved search
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: user,country,pictures,postType,category,city,country. Example: null
	 *
	 * @urlParam id int required The ID of the saved search. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		$params = [
			'embed'       => request()->input('embed'),
			'countryCode' => request()->input('countryCode'),
		];
		
		return $this->savedSearchService->getEntry($id, $params);
	}
	
	/**
	 * Store/Delete saved search
	 *
	 * Save a search result in favorite, or remove it from favorite.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam url string required Search URL to save. Example: https://demo.laraclassifier.com/search/?q=test&l=
	 * @bodyParam count_posts int required The number of posts found for the URL. Example: 29
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(Request $request): JsonResponse
	{
		return $this->savedSearchService->store($request);
	}
	
	/**
	 * Delete saved search(es)
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam ids string required The ID or comma-separated IDs list of saved search(es). Example: 1,2,3
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		return $this->savedSearchService->destroy($ids);
	}
}
