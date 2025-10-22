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

use App\Services\PageService;
use Illuminate\Http\JsonResponse;

/**
 * @group Pages
 */
class PageController extends BaseController
{
	protected PageService $pageService;
	
	/**
	 * @param \App\Services\PageService $pageService
	 */
	public function __construct(PageService $pageService)
	{
		parent::__construct();
		
		$this->pageService = $pageService;
	}
	
	/**
	 * List pages
	 *
	 * @queryParam excludedFromFooter boolean Select or unselect pages that can list in footer. Example: 0
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: lft, created_at. Example: -lft
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'perPage'            => request()->integer('perPage'),
			'page'               => request()->integer('page'),
			'embed'              => request()->input('embed'),
			'excludedFromFooter' => (request()->integer('excludedFromFooter') == 1),
		];
		
		return $this->pageService->getEntries($params);
	}
	
	/**
	 * Get page
	 *
	 * @urlParam slugOrId string required The slug or ID of the page. Example: terms
	 *
	 * @param $slugOrId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($slugOrId): JsonResponse
	{
		return $this->pageService->getEntry($slugOrId);
	}
}
