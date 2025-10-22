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

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Services\Auth\SocialService;
use Illuminate\Http\JsonResponse;

/**
 * @group Social Auth
 */
class SocialController extends BaseController
{
	protected SocialService $socialService;
	
	/**
	 * @param \App\Services\Auth\SocialService $socialService
	 */
	public function __construct(SocialService $socialService)
	{
		parent::__construct();
		
		$this->socialService = $socialService;
	}
	
	/**
	 * Get target URL
	 *
	 * @urlParam provider string required The provider's name - Possible values: facebook, linkedin, or google. Example: null
	 *
	 * @param string $provider
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getProviderTargetUrl(string $provider): JsonResponse
	{
		return $this->socialService->getProviderTargetUrl($provider);
	}
	
	/**
	 * Get user info
	 *
	 * @urlParam provider string required The provider's name - Possible values: facebook, linkedin, or google. Example: null
	 *
	 * @param string $provider
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function handleProviderCallback(string $provider): JsonResponse
	{
		return $this->socialService->handleProviderCallback($provider);
	}
}
