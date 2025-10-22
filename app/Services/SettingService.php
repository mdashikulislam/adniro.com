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

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingService extends BaseService
{
	/**
	 * List settings
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(): JsonResponse
	{
		$settings = config('settings');
		
		// Remove the 'purchase_code' value
		if (isset($settings['app'])) {
			$app = $settings['app'];
			if (isset($app['purchase_code'])) {
				unset($app['purchase_code']);
				$settings['app'] = $app;
			}
		}
		
		// Remove settings hidden values
		$settings = collect($settings)
			->mapWithKeys(function ($value, $key) {
				$value = collect($value)
					->reject(function ($v, $k) {
						return in_array($k, Setting::optionsThatNeedToBeHidden());
					});
				
				return [$key => $value];
			})->reject(function ($v) {
				return (empty($v) || ($v->count() <= 0));
			})->toArray();
		
		$data = [
			'success' => true,
			'result'  => $settings,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Get setting
	 *
	 * @param string $key
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(string $key): JsonResponse
	{
		$settingKey = 'settings.' . $key;
		
		if (!config()->has($settingKey)) {
			return apiResponse()->notFound();
		}
		
		$settings = config($settingKey);
		
		// Remove the 'purchase_code' value
		if (is_array($settings)) {
			if (isset($settings['purchase_code'])) {
				unset($settings['purchase_code']);
			}
		}
		if (is_string($settings)) {
			if (str_ends_with($settingKey, 'purchase_code')) {
				$settings = null;
			}
		}
		
		// Remove settings hidden values
		if (is_array($settings)) {
			$settings = collect($settings)
				->reject(function ($v, $k) {
					return in_array($k, Setting::optionsThatNeedToBeHidden());
				})->toArray();
		}
		if (is_string($settings)) {
			foreach (Setting::optionsThatNeedToBeHidden() as $hiddenValue) {
				if (str_ends_with($settingKey, $hiddenValue)) {
					$settings = null;
					break;
				}
			}
		}
		
		if (empty($settings)) {
			return apiResponse()->notFound();
		}
		
		$data = [
			'success' => true,
			'result'  => $settings,
		];
		
		return apiResponse()->json($data);
	}
}
