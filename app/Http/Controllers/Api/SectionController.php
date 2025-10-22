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

use App\Services\SectionService;
use Illuminate\Http\JsonResponse;

/**
 * @group Home
 */
class SectionController extends BaseController
{
	protected SectionService $sectionService;
	
	/**
	 * @param \App\Services\SectionService $sectionService
	 */
	public function __construct(SectionService $sectionService)
	{
		parent::__construct();
		
		$this->sectionService = $sectionService;
	}
	
	/**
	 * List sections
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		return $this->sectionService->getSections();
	}
	
	/**
	 * Get section
	 *
	 * Get section by its name.
	 *
	 * @urlParam name string required The key/method of the section. Example: getCategories
	 *
	 * @param $name
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($name): JsonResponse
	{
		$params = [
			'unactivatedIncluded' => (request()->integer('unactivatedIncluded') == 1),
			'fetchData'           => (request()->integer('fetchData') == 1),
		];
		
		return $this->sectionService->getSectionByKey($name, $params);
	}
}
