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

use App\Services\SubAdmin1Service;
use Illuminate\Http\JsonResponse;

/**
 * @group Countries
 */
class SubAdmin1Controller extends BaseController
{
	protected SubAdmin1Service $subAdmin1Service;
	
	/**
	 * @param \App\Services\SubAdmin1Service $subAdmin1Service
	 */
	public function __construct(SubAdmin1Service $subAdmin1Service)
	{
		parent::__construct();
		
		$this->subAdmin1Service = $subAdmin1Service;
	}
	
	/**
	 * List admin. divisions (1)
	 *
	 * @queryParam embed string Comma-separated list of the administrative division (1) relationships for Eager Loading - Possible values: country. Example: null
	 * @queryParam q string Get the administrative division list related to the entered keyword. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: name. Example: -name
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 * @queryParam page int Items page number. From 1 to ("total items" divided by "items per page value - perPage"). Example: 1
	 *
	 * @urlParam countryCode string The country code of the country of the cities to retrieve. Example: US
	 *
	 * @param string $countryCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(string $countryCode): JsonResponse
	{
		$params = [
			'perPage' => request()->integer('perPage'),
			'page'    => request()->integer('page'),
			'embed'   => request()->input('embed'),
			'keyword' => request()->input('q', request()->input('keyword')),
		];
		
		return $this->subAdmin1Service->getEntries($countryCode, $params);
	}
	
	/**
	 * Get admin. division (1)
	 *
	 * @queryParam embed string Comma-separated list of the administrative division (1) relationships for Eager Loading - Possible values: country. Example: null
	 *
	 * @urlParam code string required The administrative division (1)'s code. Example: CH.VD
	 *
	 * @param string $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(string $code): JsonResponse
	{
		$params = [
			'embed' => request()->input('embed'),
		];
		
		return $this->subAdmin1Service->getEntries($code, $params);
	}
}
