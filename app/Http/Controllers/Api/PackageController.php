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

use App\Services\PackageService;
use Illuminate\Http\JsonResponse;

/**
 * @group Packages
 */
class PackageController extends BaseController
{
	protected PackageService $packageService;
	
	/**
	 * @param \App\Services\PackageService $packageService
	 */
	public function __construct(PackageService $packageService)
	{
		parent::__construct();
		
		$this->packageService = $packageService;
	}
	
	/**
	 * List packages
	 *
	 * @queryParam embed string Comma-separated list of the package relationships for Eager Loading - Possible values: currency. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: lft. Example: -lft
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'page'        => request()->integer('page'),
			'embed'       => request()->input('embed'),
			'packageType' => request()->segment(3),
		];
		
		return $this->packageService->getEntries($params);
	}
	
	/**
	 * Get package
	 *
	 * @queryParam embed string Comma-separated list of the package relationships for Eager Loading - Possible values: currency. Example: currency
	 *
	 * @urlParam id int required The package's ID. Example: 2
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		$params = [
			'embed' => request()->input('embed'),
		];
		
		return $this->packageService->getEntry($id, $params);
	}
}
