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

namespace App\Observers\Traits\Setting;

use App\Helpers\Common\Cookie;
use App\Helpers\Common\DBUtils\DBEncoding;
use Illuminate\Support\Facades\DB;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;
use Throwable;

trait ListingsListTrait
{
	/**
	 * Saved
	 *
	 * @param $setting
	 */
	public function listingsListSaved($setting)
	{
		$this->saveTheDisplayModeInCookie($setting);
		$this->updateDbConnectionCharsetAndCollation($setting);
		$this->applyDistanceCalculationFunctionOperation($setting);
	}
	
	/**
	 * @param $setting
	 * @return void
	 */
	private function updateDbConnectionCharsetAndCollation($setting): void
	{
		$enableDiacritics = $setting->field_values['enable_diacritics'] ?? null;
		if ($enableDiacritics == '1') {
			DBEncoding::tryToFixConnectionCharsetAndCollation();
		}
	}
	
	/**
	 * @param $setting
	 */
	private function applyDistanceCalculationFunctionOperation($setting): void
	{
		// If the 'distance_calculation_formula' has been changed
		if (array_key_exists('distance_calculation_formula', $setting->field_values)) {
			$this->removeDistanceCalculationFunctionsCache();
			$this->createDistanceCalculationFunction($setting);
		}
	}
	
	/**
	 * If the 'distance_calculation_formula' has been changed,
	 * Remove Distance Calculation Functions from Cache
	 */
	private function removeDistanceCalculationFunctionsCache(): void
	{
		try {
			$customFunctions = ['haversine', 'orthodromy'];
			foreach ($customFunctions as $function) {
				// Drop the function, If exists
				$sql = 'DROP FUNCTION IF EXISTS ' . $function . ';';
				DB::statement($sql);
				
				// Remove the corresponding cache (@todo: remove it)
				$cacheId = 'checkIfMySQLFunctionExists.' . $function;
				if (cache()->has($cacheId)) {
					cache()->forget($cacheId);
				}
			}
		} catch (Throwable $e) {
		}
	}
	
	/**
	 * If the 'distance_calculation_formula' has been changed,
	 * If the selected Distance Calculation Function doesn't exist, then create it
	 *
	 * @param $setting
	 */
	private function createDistanceCalculationFunction($setting): void
	{
		// Create the MySQL Distance Calculation function, If it doesn't exist.
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists($setting->field_values['distance_calculation_formula'])) {
			$res = DistanceHelper::createDistanceCalculationFunction($setting->field_values['distance_calculation_formula']);
		}
	}
	
	/**
	 * Save the new Display Mode in cookie
	 *
	 * @param $setting
	 */
	public function saveTheDisplayModeInCookie($setting): void
	{
		// If the Default List Mode is changed, then clear the 'list_display_mode' from the cookies
		// NOTE: The cookie has been set from JavaScript, so we have to provide the good path (maybe the good expire time)
		$displayMode = $setting->field_values['display_mode'] ?? null;
		if (!empty($displayMode)) {
			Cookie::forget('display_mode');
			
			$expire = 60 * 24 * 7; // 7 days
			Cookie::set('display_mode', $displayMode, $expire);
		}
	}
}
