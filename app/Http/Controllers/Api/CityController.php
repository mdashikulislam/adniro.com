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

use App\Services\CityService;
use Illuminate\Http\JsonResponse;

/**
 * @group Countries
 */
class CityController extends BaseController
{
	protected CityService $cityService;
	
	/**
	 * @param \App\Services\CityService $cityService
	 */
	public function __construct(CityService $cityService)
	{
		parent::__construct();
		
		$this->cityService = $cityService;
	}
	
	/**
	 * List cities
	 *
	 * @queryParam embed string Comma-separated list of the city relationships for Eager Loading - Possible values: country,subAdmin1,subAdmin2. Example: null
	 * @queryParam admin1Code string Get the city list related to the administrative division 1 code. Example: null
	 * @queryParam admin2Code string Get the city list related to the administrative division 2 code. Example: null
	 * @queryParam q string Get the city list related to the entered keyword. Example: null
	 * @queryParam autocomplete boolean Allow getting the city list in the autocomplete data format. Possible value: 0 or 1. Example: 0
	 * @queryParam sort string|array The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: name,population. Example: -name
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
		$firstOrderByPopulation = request()->input('firstOrderByPopulation');
		$firstOrderByPopulation = in_array($firstOrderByPopulation, ['desc', 'asc']) ? $firstOrderByPopulation : null;
		
		$params = [
			'perPage'                => request()->integer('perPage'),
			'page'                   => request()->integer('page'),
			'embed'                  => request()->input('embed'),
			'admin1Code'             => request()->input('admin1Code'),
			'admin2Code'             => request()->input('admin2Code'),
			'keyword'                => request()->input('q', request()->input('keyword')),
			'autocomplete'           => (request()->input('autocomplete') == 1),
			'firstOrderByPopulation' => $firstOrderByPopulation,
		];
		
		return $this->cityService->getEntries($countryCode, $params);
	}
	
	/**
	 * Get city
	 *
	 * @queryParam embed string Comma-separated list of the city relationships for Eager Loading - Possible values: country,subAdmin1,subAdmin2. Example: country
	 *
	 * @urlParam id int required The city's ID. Example: 12544
	 *
	 * @param int $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(int $id): JsonResponse
	{
		$params = [
			'embed' => request()->input('embed'),
		];
		
		return $this->cityService->getEntry($id, $params);
	}
}
