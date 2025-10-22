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

namespace App\Providers\AppService;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

trait SettingsToConfigTrait
{
	/**
	 * Load database settings into Laravel's configuration system.
	 *
	 * Retrieves active settings from the database and binds them to the Laravel config
	 * under the 'settings' namespace, making them accessible via config('settings.name.field').
	 * Sets default purchase code from larapen.core.purchaseCode if available.
	 *
	 * @param \Illuminate\Database\Eloquent\Collection<Setting>|null $settings Collection of Setting models to load, or null to fetch from database
	 * @return void
	 */
	private function loadDatabaseSettingsToConfig(?Collection $settings = null): void
	{
		// Get some default values
		config()->set('settings.app.purchase_code', config('larapen.core.purchaseCode'));
		
		try {
			// Get all settings from the database
			if (is_null($settings)) {
				$settings = Setting::active()->get();
			}
			
			// Bind all settings to the Laravel config
			if ($settings->count() > 0) {
				foreach ($settings as $setting) {
					$fieldValues = $setting->field_values ?? [];
					
					if (is_array($fieldValues) && count($fieldValues) > 0) {
						foreach ($fieldValues as $subField => $subValue) {
							if (!empty($subValue) || is_numeric($subValue)) {
								config()->set('settings.' . $setting->name . '.' . $subField, $subValue);
							}
						}
					}
				}
			}
		} catch (Throwable $e) {
			config()->set('settings.app.logo', config('larapen.media.logo'));
		}
	}
}
