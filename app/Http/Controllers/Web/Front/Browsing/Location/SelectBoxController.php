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
use App\Services\SubAdmin1Service;
use App\Services\SubAdmin2Service;
use Illuminate\Http\JsonResponse;

class SelectBoxController extends FrontController
{
	/**
	 * Form Select Box
	 * Get Countries
	 *
	 * @return string
	 */
	public function getCountries(): string
	{
		if (is_null($this->countries)) {
			return collect()->toJson();
		}
		
		return $this->countries->toJson();
	}
	
	/**
	 * Form Select Box
	 * Get country Locations (admin1 OR admin2)
	 *
	 * @param $countryCode
	 * @param $adminType
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getAdmins($countryCode, $adminType): JsonResponse
	{
		$languageCode = request()->input('languageCode', config('app.locale'));
		
		$adminServices = [
			'1' => SubAdmin1Service::class,
			'2' => SubAdmin2Service::class,
		];
		
		// If an admin type does not exist, set the default type
		if (!isset($adminServices[$adminType])) {
			$adminType = 1;
		}
		
		// Get the number of items to take
		$entity = ($adminType == 2) ? 'subadmin2' : 'subadmin1';
		$limit = getNumberOfItemsToTake($entity . '_select');
		$page = request()->integer('page', 1);
		
		/**
		 * Get the entity service
		 *
		 * @var SubAdmin1Service|SubAdmin2Service $adminService
		 */
		$adminService = $adminServices[$adminType];
		
		// Get country's admin. divisions
		$queryParams = [
			'sort'         => '-name',
			'languageCode' => $languageCode,
			'perPage'      => $limit,
		];
		if ($adminType == 2) {
			$queryParams['embed'] = 'subAdmin1';
		}
		if (!empty($page)) {
			$queryParams['page'] = $page;
		}
		$data = getServiceData((new $adminService())->getEntries($countryCode, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		$admins = data_get($apiResult, 'data');
		
		// No admin. division found. Display error.
		if (empty($admins)) {
			$message = $apiMessage ?? t('admin_division_does_not_exists', [], 'global', $languageCode);
			$result = ['message' => $message];
			
			return ajaxResponse()->json($result, 404);
		}
		
		// Get & formats the admin. divisions
		$adminsArr = [];
		foreach ($admins as $admin) {
			$code = data_get($admin, 'code');
			$name = data_get($admin, 'name');
			
			// Change the name for admin. division 2
			if ($adminType == 2) {
				$admin1Name = data_get($admin, 'subAdmin1.name');
				$name = !empty($admin1Name) ? $name . ', ' . $admin1Name : $name;
			}
			
			$adminsArr[] = [
				'code' => $code,
				'name' => $name,
			];
		}
		
		return ajaxResponse()->json(['data' => $adminsArr]);
	}
	
	/**
	 * Form Select Box
	 * Get Admin1 or Admin2's Cities
	 *
	 * @param $countryCode
	 * @param $adminType
	 * @param $adminCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCities($countryCode, $adminType, $adminCode): JsonResponse
	{
		$languageCode = request()->input('languageCode', config('app.locale'));
		$query = request()->input('q', request()->input('keyword'));
		$limit = getNumberOfItemsToTake('cities_select');
		$page = request()->integer('page', 1);
		$adminTypes = ['1', '2'];
		
		$queryParams = [
			'perPage'      => $limit,
			'keyword'      => $query,
			'autocomplete' => true,
			'sort'         => 'population',
			'languageCode' => $languageCode,
		];
		if (!empty($page)) {
			$queryParams['page'] = $page;
		}
		
		if (!in_array($adminType, $adminTypes) || $adminCode == '0') {
			$queryParams['embed'] = 'subAdmin1,subAdmin2';
			
			// Get country's cities
			$apiResult = $this->getCityList($countryCode, $queryParams);
			$cities = data_get($apiResult, 'data');
		} else {
			$embedQsValue = 'subAdmin' . $adminType;
			$adminCodeQs = 'admin' . $adminType . 'Code';
			$queryParams['embed'] = $embedQsValue;
			$queryParams[$adminCodeQs] = $adminCode;
			
			// Get country's cities - Call API endpoint
			$apiResult = $this->getCityList($countryCode, $queryParams);
			$cities = data_get($apiResult, 'data');
			
			// If the admin. division's type is 2 and If no cities are found...
			// then, get cities from their admin. division 1
			if ($adminType == 2) {
				if (isset($queryParams[$adminCodeQs])) {
					unset($queryParams[$adminCodeQs]);
				}
				$queryParams['embed'] = 'subAdmin1';
				if (empty($cities)) {
					$queryParams['admin1Code'] = $adminCode;
					
					// Get country's cities
					$apiResult = $this->getCityList($countryCode, $queryParams);
					$cities = data_get($apiResult, 'data');
				}
			}
		}
		$totalEntries = (int)data_get($apiResult, 'meta.total', 0);
		
		// Get Cities Array
		$items = [];
		if (!empty($cities)) {
			foreach ($cities as $city) {
				$cityName = data_get($city, 'name');
				$admin2Name = data_get($city, 'subAdmin2.name');
				$admin1Name = data_get($city, 'subAdmin1.name');
				
				$fullCityName = !empty($admin2Name)
					? $cityName . ', ' . $admin2Name
					: (!empty($admin1Name) ? $cityName . ', ' . $admin1Name : $cityName);
				
				$items[] = [
					'id'   => data_get($city, 'id'),
					'text' => $fullCityName,
				];
			}
		}
		
		return ajaxResponse()->json(['items' => $items, 'totalEntries' => $totalEntries]);
	}
	
	/**
	 * Form Select Box
	 * Get the selected City
	 *
	 * @param $countryCode
	 * @param $cityId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSelectedCity($countryCode, $cityId): JsonResponse
	{
		$languageCode = request()->input('languageCode', config('app.locale'));
		
		// Get the City by its ID
		$queryParams = [
			'embed'        => 'subAdmin1,subAdmin2',
			'languageCode' => $languageCode,
		];
		$data = getServiceData((new CityService())->getEntry($cityId, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$city = data_get($data, 'result');
		
		if (empty($city)) {
			$item = [
				'id'   => 0,
				'text' => t('select_a_city', [], 'global', $languageCode),
			];
			
			return ajaxResponse()->json($item);
		}
		
		$cityName = data_get($city, 'name');
		$admin2Name = data_get($city, 'subAdmin2.name');
		$admin1Name = data_get($city, 'subAdmin1.name');
		
		$fullCityName = !empty($admin2Name)
			? $cityName . ', ' . $admin2Name
			: (!empty($admin1Name) ? $cityName . ', ' . $admin1Name : $cityName);
		
		$item = [
			'id'   => data_get($city, 'id'),
			'text' => $fullCityName,
		];
		
		return ajaxResponse()->json($item);
	}
	
	/**
	 * @param string $countryCode
	 * @param array $queryParams
	 * @return array
	 */
	private function getCityList(string $countryCode, array $queryParams = []): array
	{
		// Get cities
		$data = getServiceData((new CityService())->getEntries($countryCode, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		return is_array($apiResult) ? $apiResult : [];
	}
}
