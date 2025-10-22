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

use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

/**
 * @group Settings
 */
class SettingController extends BaseController
{
	protected SettingService $settingService;
	
	/**
	 * @param \App\Services\SettingService $settingService
	 */
	public function __construct(SettingService $settingService)
	{
		parent::__construct();
		
		$this->settingService = $settingService;
	}
	
	/**
	 * List settings
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		return $this->settingService->getEntries();
	}
	
	/**
	 * Get setting
	 *
	 * @urlParam key string required The setting's key. Example: app
	 *
	 * @param string $key
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(string $key): JsonResponse
	{
		return $this->settingService->getEntry($key);
	}
}
