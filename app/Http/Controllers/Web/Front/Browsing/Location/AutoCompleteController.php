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

namespace App\Http\Controllers\Web\Front\Browsing\Location;

use App\Http\Controllers\Web\Front\FrontController;
use App\Services\CityService;
use Illuminate\Http\JsonResponse;

class AutoCompleteController extends FrontController
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
	 * Autocomplete Cities
	 *
	 * @param $countryCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function __invoke($countryCode): JsonResponse
	{
		$languageCode = request()->input('languageCode', config('app.locale'));
		$query = request()->input('query');
		$limit = getNumberOfItemsToTake('auto_complete_cities');
		$page = request()->integer('page', 1);
		
		$citiesList = [];
		$result = [
			'query'       => $query,
			'suggestions' => $citiesList,
		];
		
		if (mb_strlen($query) <= 0) {
			return ajaxResponse()->json($result);
		}
		
		// Get country's cities
		$queryParams = [
			'embed'         => 'subAdmin1,subAdmin2',
			'keyword'       => $query,
			'autocomplete'  => true,
			'sort'          => '-name',
			'language_code' => $languageCode,
			'perPage'       => $limit,
		];
		if (!empty($page)) {
			$queryParams['page'] = $page;
		}
		$data = getServiceData($this->cityService->getEntries($countryCode, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		$cities = data_get($apiResult, 'data');
		
		// No cities found
		if (empty($cities)) {
			$status = (int)data_get($data, 'status', 200);
			$status = isValidHttpStatus($status) ? $status : 200;
			$result['message'] = $apiMessage;
			
			return ajaxResponse()->json($result, $status);
		}
		
		// Get & formats cities
		foreach ($cities as $city) {
			$cityName = data_get($city, 'name');
			$admin2Name = data_get($city, 'subAdmin2.name');
			$admin1Name = data_get($city, 'subAdmin1.name');
			
			$adminName = !empty($admin2Name) ? $admin2Name : (!empty($admin1Name) ? $admin1Name : '');
			// $cityNameDetailed = !empty($adminName) ? $cityName . ', ' . $adminName : $cityName;
			
			$citiesList[] = [
				'id'    => data_get($city, 'id'),
				'name'  => $cityName,
				'admin' => $adminName,
			];
		}
		
		// XHR Data
		$result['query'] = $query;
		$result['suggestions'] = $citiesList;
		
		return ajaxResponse()->json($result);
	}
}
