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

use App\Services\CountryService;
use Illuminate\Http\JsonResponse;

/**
 * @group Countries
 */
class CountryController extends BaseController
{
	protected CountryService $countryService;
	
	/**
	 * @param \App\Services\CountryService $countryService
	 */
	public function __construct(CountryService $countryService)
	{
		parent::__construct();
		
		$this->countryService = $countryService;
	}
	
	/**
	 * List countries
	 *
	 * @header Content-Language {local-code}
	 *
	 * @queryParam embed string Comma-separated list of the country relationships for Eager Loading - Possible values: currency,continent. Example: null
	 * @queryParam includeNonActive boolean Allow including the non-activated countries in the list. Example: false
	 * @queryParam iti string Allow getting option data for the phone number input. Possible value: 'i18n' or 'onlyCountries'. Example: 'onlyCountries'
	 * @queryParam countryCode string The code of the current country (Only when the 'iti' parameter is filled to true). Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: name. Example: -name
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'perPage'          => request()->integer('perPage'),
			'page'             => request()->integer('page'),
			'embed'            => request()->input('embed'),
			'keyword'          => request()->input('q', request()->input('keyword')),
			'includeNonActive' => (request()->input('includeNonActive') == 1),
			'iti'              => request()->input('iti'),
			'sort'             => request()->input('sort'),
		];
		
		return $this->countryService->getEntries($params);
	}
	
	/**
	 * Get country
	 *
	 * @queryParam embed string Comma-separated list of the country relationships for Eager Loading - Possible values: currency. Example: currency
	 *
	 * @urlParam code string required The country's ISO 3166-1 code. Example: DE
	 *
	 * @param $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($code): JsonResponse
	{
		$params = [
			'embed' => request()->input('embed'),
		];
		
		return $this->countryService->getEntry($code, $params);
	}
}
