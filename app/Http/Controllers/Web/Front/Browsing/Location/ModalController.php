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
use App\Services\CountryService;
use App\Services\SubAdmin1Service;
use App\Services\SubAdmin2Service;
use Illuminate\Http\JsonResponse;

class ModalController extends FrontController
{
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
		$countryChanged = request()->input('countryChanged', 0);
		$currSearch = unserialize(base64_decode(request()->input('currSearch')));
		$page = request()->integer('page', 1);
		$_token = request()->input('_token');
		$query = request()->input('query');
		
		// If the country is changed, Get the selected country's name
		$country = $this->getCountry($countryCode, ($countryChanged == 1));
		
		$adminServices = [
			'1' => SubAdmin1Service::class,
			'2' => SubAdmin2Service::class,
		];
		
		// If an admin type does not exist, set the default type
		if (!isset($adminServices[$adminType])) {
			$adminType = 1;
		}
		
		// Get the items per page number
		$entity = ($adminType == 2) ? 'subadmin2' : 'subadmin1';
		$perPage = getNumberOfItemsPerPage($entity);
		
		// XHR data
		$result = [];
		
		/**
		 * Get the entity service
		 *
		 * @var SubAdmin1Service|SubAdmin2Service $adminService
		 */
		$adminService = $adminServices[$adminType];
		
		// Get country's admin. divisions
		$queryParams = [
			// 'perPage'    => ($adminType == 2) ? 38 : 39,
			'perPage'      => $perPage,
			'keyword'      => $query,
			'sort'         => '-name',
			'languageCode' => $languageCode,
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
		
		// Variables for location's cities view
		$data = [
			'countryCode'  => $countryCode,
			'adminType'    => $adminType,
			'languageCode' => $languageCode,
			'apiResult'    => $apiResult ?? [],
			'apiMessage'   => $apiMessage,
			'currSearch'   => $currSearch,
			'_token'       => $_token,
		];
		
		// Get admin. division list HTML & the country's name
		$content = getViewContent('front.layouts.partials.modal.location.admins', $data);
		$countryName = data_get($country, 'name', config('country.name'));
		
		// XHR data
		$result['isCity'] = false;
		$result['admin'] = null;
		$result['locationsTitle'] = t('locations_in_country', ['country' => $countryName]);
		$result['locationsContent'] = $content;
		
		return ajaxResponse()->json($result);
	}
	
	/**
	 * Get cities by a given admin. division's code (in Modal)
	 * Note: Administrative divisions list is prepended
	 *
	 * @param $countryCode
	 * @param $adminType
	 * @param $adminCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCities($countryCode, $adminType = null, $adminCode = null): JsonResponse
	{
		$languageCode = request()->input('languageCode', config('app.locale'));
		$countryChanged = request()->input('countryChanged', 0);
		$currSearch = unserialize(base64_decode(request()->input('currSearch')));
		$perPage = getNumberOfItemsPerPage('cities');
		$page = request()->integer('page', 1);
		$_token = request()->input('_token');
		$query = request()->input('query');
		$cityId = request()->input('cityId'); // The selected city from select box
		
		// If the country is changed, Get the selected country's name
		$country = $this->getCountry($countryCode, ($countryChanged == 1));
		
		// XHR data
		$result = [];
		
		$admin = null;
		if (!is_null($adminType) && !is_null($adminCode)) {
			$adminServices = [
				'1' => SubAdmin1Service::class,
				'2' => SubAdmin2Service::class,
			];
			
			// If an admin type does not exist, set the default type
			if (!isset($adminServices[$adminType])) {
				$adminType = 1;
			}
			
			/**
			 * Get the entity service
			 *
			 * @var SubAdmin1Service|SubAdmin2Service $adminService
			 */
			$adminService = $adminServices[$adminType];
			
			// Get the Administrative Division Info
			$queryParams = [];
			if ($adminType == 2) {
				$queryParams['embed'] = 'subAdmin1';
			}
			$data = getServiceData((new $adminService())->getEntry($adminCode, $queryParams));
			
			$apiMessage = data_get($data, 'message');
			$admin = data_get($data, 'result');
		}
		
		// Get the Administrative Division's Cities
		$queryParams = [
			'embed'        => 'subAdmin1,subAdmin2',
			'keyword'      => $query,
			'sort'         => [
				0 => 'population',
				1 => '-name',
			],
			'languageCode' => $languageCode,
			'perPage'      => $perPage,
		];
		if (!empty($adminCode)) {
			$adminCodeQs = 'admin' . $adminType . 'Code';
			$queryParams['adminType'] = $adminType;
			$queryParams[$adminCodeQs] = $adminCode;
		}
		if (!empty($page)) {
			$queryParams['page'] = $page;
		}
		$data = getServiceData((new CityService())->getEntries($countryCode, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		// Get current city ID (If exists) - From a link
		if (!empty($currSearch['l'])) {
			$cityId = $currSearch['l'];
		}
		
		// Variables for location's cities view
		$data = [
			'countryCode'  => $countryCode,
			'adminType'    => $adminType,
			'adminCode'    => $adminCode,
			'languageCode' => $languageCode,
			'admin'        => $admin,
			'apiResult'    => $apiResult ?? [],
			'apiMessage'   => $apiMessage,
			'currSearch'   => $currSearch,
			'cityId'       => $cityId,
			'_token'       => $_token,
		];
		
		// Get cities' list HTML & the country's name
		$content = getViewContent('front.layouts.partials.modal.location.cities', $data);
		$countryName = data_get($country, 'name', config('country.name'));
		
		// Get locations base (regions) URL
		$baseUrl = url('browsing/locations/' . $countryCode . '/admins/' . $adminType);
		
		// Get subtitle
		if (!empty($adminCode)) {
			if (!empty($admin)) {
				$adminName = data_get($admin, 'name');
				if ($adminType == 2) {
					$admin1Name = data_get($admin, 'subAdmin1.name');
					$adminName = !empty($admin1Name) ? $adminName . ', ' . $admin1Name : $adminName;
				}
				
				$title = '<a href="" data-url="' . $baseUrl . '" class="btn btn-sm btn-primary is-admin go-base-url" data-ignore-guard="true">';
				$title .= '<i class="fa-solid fa-reply"></i> ' . t('all_regions', [], 'global', $languageCode);
				$title .= '</a>&nbsp;';
				$title .= t('popular_cities_in_location', ['location' => $adminName]);
			} else {
				$title = t('locations_in_country', ['country' => $countryName]);
			}
		} else {
			$countryAdminType = !empty($adminType) ? $adminType : config('country.admin_type', 0);
			
			$title = '';
			if (in_array($countryAdminType, ['1', '2'])) {
				$goBaseUrl = url('browsing/locations/' . $countryCode . '/admins/' . $countryAdminType);
				
				$title .= '<a href="" data-url="' . $goBaseUrl . '" class="btn btn-sm btn-primary is-admin go-base-url" data-ignore-guard="true">';
				$title .= '<i class="fa-solid fa-reply"></i> ' . t('cities_per_region', [], 'global', $languageCode);
				$title .= '</a>&nbsp;';
			}
			$title .= t('cities_in_location', ['location' => $countryName]);
		}
		
		// XHR data
		$result['isCity'] = true;
		$result['admin'] = $admin;
		$result['locationsTitle'] = $title;
		$result['locationsContent'] = $content;
		
		return ajaxResponse()->json($result);
	}
	
	/**
	 * If the country is changed, Get the selected country's name
	 *
	 * @param string|null $countryCode
	 * @param bool $countryChanged
	 * @return array
	 */
	private function getCountry(?string $countryCode, bool $countryChanged = false): array
	{
		$country = null;
		if ($countryChanged) {
			// Get the new country's info
			$data = getServiceData((new CountryService())->getEntry($countryCode));
			
			$apiMessage = data_get($data, 'message');
			$country = data_get($data, 'result');
		}
		
		return is_array($country) ? $country : [];
	}
}
