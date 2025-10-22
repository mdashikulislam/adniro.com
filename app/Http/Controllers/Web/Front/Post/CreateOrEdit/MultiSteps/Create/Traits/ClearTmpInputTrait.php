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

namespace App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\Traits;

use Throwable;

trait ClearTmpInputTrait
{
	/**
	 * Clear Temporary Inputs & Files
	 *
	 * @return void
	 */
	public function clearTemporaryInput(): void
	{
		if (session()->has('postInput')) {
			session()->forget('postInput');
		}
		
		if (session()->has('picturesInput')) {
			$picturesInput = (array)session('picturesInput');
			if (!empty($picturesInput)) {
				try {
					foreach ($picturesInput as $filePath) {
						$this->removePictureWithItsThumbs($filePath);
					}
				} catch (Throwable $e) {
					$message = $e->getMessage();
					flash($message)->error();
				}
				session()->forget('picturesInput');
			}
		}
		
		if (session()->has('paymentInput')) {
			session()->forget('paymentInput');
		}
		
		if (session()->has('uid')) {
			session()->forget('uid');
		}
	}
	
	/**
	 * Ensure that the country data stored in the session corresponds to the current selection
	 *
	 * Synchronizes session country data with the current country selection,
	 * updating related fields and clearing outdated location data.
	 *
	 * @return void
	 */
	public function syncSessionCountryData(): void
	{
		if (!session()->has('postInput')) {
			return;
		}
		
		$postInput = session('postInput');
		
		// Validate the selected country
		if (!empty($postInput)) {
			$countryCode = config('country.code');
			$savedCountryCode = $postInput['country_code'] ?? null;
			if ($countryCode != $savedCountryCode) {
				$postInput['country_code'] = $countryCode;
				$postInput['phone_country'] = $countryCode;
				$postInput['currency_code'] = config('currency.code', 'USD');
				
				$locationData = [
					'selected_admin_type',
					'selected_admin_code',
					'selected_city_id',
					'selected_city_name',
					'city_id',
					'city_name',
					'admin_type',
					'admin_code',
					'phone',
					'phone_intl',
					'phone_national',
				];
				foreach ($locationData as $item) {
					if (array_key_exists($item, $postInput)) {
						unset($postInput[$item]);
					}
				}
				
				session()->put('postInput', $postInput);
			}
		}
	}
}
