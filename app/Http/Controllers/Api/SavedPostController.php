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

use App\Services\SavedPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Saved Posts
 */
class SavedPostController extends BaseController
{
	protected SavedPostService $savedPostService;
	
	/**
	 * @param \App\Services\SavedPostService $savedPostService
	 */
	public function __construct(SavedPostService $savedPostService)
	{
		parent::__construct();
		
		$this->savedPostService = $savedPostService;
	}
	
	/**
	 * List saved listings
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam country_code string required The code of the user's country. Example: US
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: post,city,pictures,user. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'perPage'     => request()->integer('perPage'),
			'countryCode' => request()->input('countryCode'),
		];
		
		return $this->savedPostService->getEntries($params);
	}
	
	/**
	 * Store/Delete saved listing
	 *
	 * Save a post/listing in favorite, or remove it from favorite.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam post_id int required The post/listing's ID. Example: 2
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(Request $request): JsonResponse
	{
		return $this->savedPostService->store($request);
	}
	
	/**
	 * Delete saved listing(s)
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam ids string required The ID or comma-separated IDs list of saved post/listing(s). Example: 1,2,3
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		return $this->savedPostService->destroy($ids);
	}
}
