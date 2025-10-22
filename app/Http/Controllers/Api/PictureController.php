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

use App\Http\Requests\Front\PhotoRequest;
use App\Services\PictureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Pictures
 */
class PictureController extends BaseController
{
	protected PictureService $pictureService;
	
	/**
	 * @param \App\Services\PictureService $pictureService
	 */
	public function __construct(PictureService $pictureService)
	{
		parent::__construct();
		
		$this->pictureService = $pictureService;
	}
	
	/**
	 * List pictures
	 *
	 * @queryParam embed string The list of the picture relationships separated by comma for Eager Loading. Possible values: post. Example: null
	 * @queryParam postId int List of pictures related to a listing (using the listing ID). Example: 1
	 * @queryParam latest boolean Get only the first picture after ordering (as object instead of collection). Possible value: 0 or 1. Example: 0
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: position, created_at.Example: -position
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'embed'  => request()->input('embed'),
			'latest' => (request()->input('latest') == 1),
			'postId' => request()->input('postId'),
		];
		
		return $this->pictureService->getEntries($params);
	}
	
	/**
	 * Get picture
	 *
	 * @queryParam embed string The list of the picture relationships separated by comma for Eager Loading. Example: null
	 *
	 * @urlParam id int required The picture's ID. Example: 298
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		$params = [
			'embed' => request()->input('embed'),
		];
		
		return $this->pictureService->getEntry($id, $params);
	}
	
	/**
	 * Store picture
	 *
	 * Note: This endpoint is only available for the multi steps post edition.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam country_code string required The code of the user's country. Example: US
	 * @bodyParam count_packages int required The number of available packages. Example: 3
	 * @bodyParam count_payment_methods int required The number of available payment methods. Example: 1
	 * @bodyParam post_id int required The post's ID. Example: 2
	 *
	 * @bodyParam pictures file[] The files to upload.
	 *
	 * @param \App\Http\Requests\Front\PhotoRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(PhotoRequest $request): JsonResponse
	{
		return $this->pictureService->store($request);
	}
	
	/**
	 * Reorder pictures
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 * @header X-Action bulk
	 *
	 * @bodyParam post_id int required The post's ID. Example: 2
	 * @bodyParam body string required Encoded json of the new pictures' positions array [['id' => 2, 'position' => 1], ['id' => 1, 'position' => 2], ...]
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reorder(Request $request): JsonResponse
	{
		$params = [
			'post_id' => $request->input('post_id'),
			'body'    => $request->input('body'),
		];
		
		return $this->pictureService->reorder($params);
	}
	
	/**
	 * Delete picture
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 * For newly created listings, the post's ID needs to be added in the request input with the key 'new_post_id'.
	 * The 'new_post_id' and 'new_post_tmp_token' fields need to be removed or unset during the listing edition steps.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam post_id int required The post's ID. Example: 2
	 *
	 * @urlParam id int required The picture's ID. Example: 999999999
	 *
	 * @param $id
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy($id, Request $request): JsonResponse
	{
		$params = [
			'post_id' => $request->input('post_id'),
		];
		
		return $this->pictureService->destroy($id, $params);
	}
}
