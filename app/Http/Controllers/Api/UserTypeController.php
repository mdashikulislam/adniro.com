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

use App\Services\UserTypeService;
use Illuminate\Http\JsonResponse;

/**
 * @group Users
 */
class UserTypeController extends BaseController
{
	protected UserTypeService $userTypeService;
	
	/**
	 * @param \App\Services\UserTypeService $userTypeService
	 */
	public function __construct(UserTypeService $userTypeService)
	{
		parent::__construct();
		
		$this->userTypeService = $userTypeService;
	}
	
	/**
	 * List user types
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		return $this->userTypeService->getEntries();
	}
	
	/**
	 * Get user type
	 *
	 * @urlParam id int required The user type's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		return $this->userTypeService->getEntry($id);
	}
}
