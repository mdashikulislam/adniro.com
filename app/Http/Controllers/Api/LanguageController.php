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

use App\Services\LanguageService;
use Illuminate\Http\JsonResponse;

/**
 * @group Languages
 */
class LanguageController extends BaseController
{
	protected LanguageService $languageService;
	
	/**
	 * @param \App\Services\LanguageService $languageService
	 */
	public function __construct(LanguageService $languageService)
	{
		parent::__construct();
		
		$this->languageService = $languageService;
	}
	
	/**
	 * List languages
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'includeNonActive' => (request()->integer('includeNonActive') == 1),
		];
		
		return $this->languageService->getEntries($params);
	}
	
	/**
	 * Get language
	 *
	 * @urlParam code string required The language's code. Example: en
	 *
	 * @param $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($code): JsonResponse
	{
		return $this->languageService->getEntry($code);
	}
}
