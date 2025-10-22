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

use App\Services\GenderService;
use Illuminate\Http\JsonResponse;

/**
 * @group Users
 */
class GenderController extends BaseController
{
	protected GenderService $genderService;
	
	/**
	 * @param \App\Services\GenderService $genderService
	 */
	public function __construct(GenderService $genderService)
	{
		parent::__construct();
		
		$this->genderService = $genderService;
	}
	
	/**
	 * List genders
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		return $this->genderService->getEntries();
	}
	
	/**
	 * Get gender
	 *
	 * @urlParam id int required The gender's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		return $this->genderService->getEntry($id);
	}
}
