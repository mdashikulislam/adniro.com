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

use App\Services\ReportTypeService;
use Illuminate\Http\JsonResponse;

/**
 * @group Listings
 */
class ReportTypeController extends BaseController
{
	protected ReportTypeService $reportTypeService;
	
	/**
	 * @param \App\Services\ReportTypeService $reportTypeService
	 */
	public function __construct(ReportTypeService $reportTypeService)
	{
		parent::__construct();
		
		$this->reportTypeService = $reportTypeService;
	}
	
	/**
	 * List report types
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		return $this->reportTypeService->getEntries();
	}
	
	/**
	 * Get report type
	 *
	 * @urlParam id int required The report type's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		return $this->reportTypeService->getEntry($id);
	}
}
