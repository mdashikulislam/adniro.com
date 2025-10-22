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

namespace App\Helpers\Services;

use App\Models\Country;
use App\Services\CountryService;
use App\Services\GenderService;
use App\Services\PostTypeService;
use App\Services\ReportTypeService;
use App\Services\UserTypeService;

class Referrer
{
	/**
	 * @return array
	 */
	public static function getGenders(): array
	{
		$data = getServiceData((new GenderService())->getEntries());
		$genders = data_get($data, 'result');
		
		return is_array($genders) ? $genders : [];
	}
	
	/**
	 * @return array
	 */
	public static function getUserTypes(): array
	{
		// Get user types
		$data = getServiceData((new UserTypeService())->getEntries());
		$postTypes = data_get($data, 'result');
		
		return is_array($postTypes) ? $postTypes : [];
	}
	
	/**
	 * @return array
	 */
	public static function getPostTypes(): array
	{
		// Get post types
		$data = getServiceData((new PostTypeService())->getEntries());
		$postTypes = data_get($data, 'result');
		
		return is_array($postTypes) ? $postTypes : [];
	}
	
	/**
	 * @return array
	 */
	public static function getReportTypes(): array
	{
		// Get report types
		$queryParams = ['sort' => '-name'];
		$data = getServiceData((new ReportTypeService())->getEntries($queryParams));
		
		$apiResult = data_get($data, 'result');
		$postTypes = data_get($apiResult, 'data');
		
		return is_array($postTypes) ? $postTypes : [];
	}
	
	/**
	 * Get the 'Intl Tel Input' parameter data
	 *
	 * Note: $iti accept only the values:
	 * - "i18n" for the current language
	 * - "onlyCountries" for accepted country list codes
	 *
	 * @param string $param
	 * @return array
	 */
	public static function getItiParameterData(string $param): array
	{
		$phoneOfCountries = config('settings.sms.phone_of_countries', 'local');
		$countryCode = config('country.code', 'US');
		
		$cacheParams = [
			'action'           => 'get.countries.iti',
			'params'           => $param,
			'isFromAdminPanel' => isFromAdminPanel(),
			'country'          => $phoneOfCountries,
			'countryCode'      => $countryCode,
			'locale'           => app()->getLocale(),
		];
		
		$result = caching()->remember(Country::class, $cacheParams, function () use ($param) {
			return self::getItiParameterDataWithoutCache($param);
		});
		
		return is_array($result) ? $result : [];
	}
	
	/**
	 * Get the 'Intl Tel Input' parameter data (Without Cache)
	 *
	 * Note: $param accept only the values:
	 * - "i18n" for the current language
	 * - "onlyCountries" for accepted country list codes
	 *
	 * @param string $param
	 * @return array
	 */
	public static function getItiParameterDataWithoutCache(string $param): array
	{
		// Get countries
		$queryParams = [
			'iti'              => $param,
			'isFromAdminPanel' => isFromAdminPanel(),
		];
		$data = getServiceData((new CountryService())->getEntries($queryParams));
		$result = data_get($data, 'result');
		
		return is_array($result) ? $result : [];
	}
}
