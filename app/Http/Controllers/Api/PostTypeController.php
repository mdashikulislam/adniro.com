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

use App\Services\PostTypeService;
use Illuminate\Http\JsonResponse;

/**
 * @group Listings
 */
class PostTypeController extends BaseController
{
	protected PostTypeService $postTypeService;
	
	/**
	 * @param \App\Services\PostTypeService $postTypeService
	 */
	public function __construct(PostTypeService $postTypeService)
	{
		parent::__construct();
		
		$this->postTypeService = $postTypeService;
	}
	
	/**
	 * List listing types
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		return $this->postTypeService->getEntries();
	}
	
	/**
	 * Get listing type
	 *
	 * @urlParam id int required The listing type's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		return $this->postTypeService->getEntry($id);
	}
}
