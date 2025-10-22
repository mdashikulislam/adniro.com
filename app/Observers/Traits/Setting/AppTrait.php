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
use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Common\JsonUtils;
use App\Models\Country;
use App\Models\Language;
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\LocalizedScope;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

trait AppTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 */
	public function appUpdating($setting, $original)
	{
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		$this->deleteJsonPathFile(
			model: $setting,
			column: 'field_values',
			path: 'logo',
			filesystem: $disk,
			protectedPath: config('larapen.media.logo'),
			original: $original
		);
		$this->deleteJsonPathFile(
			model: $setting,
			column: 'field_values',
			path: 'favicon',
			filesystem: $disk,
			protectedPath: config('larapen.media.favicon'),
			original: $original
		);
		$this->deleteJsonPathFile(
			model: $setting,
			column: 'field_values',
			path: 'logo_dark',
			filesystem: $disk,
			protectedPath: config('larapen.media.logo-dark'),
			original: $original
		);
		$this->deleteJsonPathFile(
			model: $setting,
			column: 'field_values',
			path: 'logo_light',
			filesystem: $disk,
			protectedPath: config('larapen.media.logo-light'),
			original: $original
		);
		
		$darkThemeEnabled = $setting->field_values['dark_theme_enabled'] ?? null;
		$darkThemeEnabledOld = $original['field_values']['dark_theme_enabled'] ?? null;
		
		$systemThemeEnabled = $setting->field_values['system_theme_enabled'] ?? null;
		$systemThemeEnabledOld = $original['field_values']['system_theme_enabled'] ?? null;
		
		if (($darkThemeEnabled != $darkThemeEnabledOld) || ($systemThemeEnabled != $systemThemeEnabledOld)) {
			if (Cookie::has('themePreference')) {
				Cookie::forget('themePreference');
			}
		}
		
		$this->removeAutoLanguageDetectedSession($setting, $original);
		
		$phpSpecificDateFormat = $setting->field_values['php_specific_date_format'] ?? null;
		$phpSpecificDateFormatOld = $original['field_values']['php_specific_date_format'] ?? null;
		
		if ($phpSpecificDateFormat != $phpSpecificDateFormatOld) {
			request()->request->add(['formatTypeFieldWasChanged' => 1]);
		}
	}
	
	/**
	 * Updated
	 *
	 * @param $setting
	 */
	public function appUpdated($setting)
	{
		$this->clearOldDateFormats($setting);
	}
	
	/**
	 * Remove the language detection created sessions
	 *
	 * @param $setting
	 * @param $original
	 */
	private function removeAutoLanguageDetectedSession($setting, $original): void
	{
		$autoDetectLanguage = $setting->field_values['auto_detect_language'] ?? null;
		$autoDetectLanguageOld = $original['field_values']['auto_detect_language'] ?? null;
		
		if (empty($autoDetectLanguage) || ($autoDetectLanguage != $autoDetectLanguageOld)) {
			if (session()->has('browserLangCode')) {
				session()->forget('browserLangCode');
			}
			if (session()->has('countryLangCode')) {
				session()->forget('countryLangCode');
			}
			$countries = Country::all();
			if ($countries->count() > 0) {
				foreach ($countries as $country) {
					$sessionName = strtolower($country->code) . 'CountryLangCode';
					if (session()->has($sessionName)) {
						session()->forget($sessionName);
					}
				}
			}
		}
	}
	
	/**
	 * Clear all Date formats when the format type has changed
	 *
	 * @param $setting
	 */
	private function clearOldDateFormats($setting): void
	{
		if (request()->has('formatTypeFieldWasChanged') && request()->input('formatTypeFieldWasChanged') == 1) {
			$settingTable = (new Setting)->getTable();
			$appSetting = DB::table($settingTable)->where('name', 'app')->first();
			if (!empty($appSetting)) {
				$appSetting->field_values = $setting->field_values;
				
				$values = JsonUtils::jsonToArray($appSetting->field_values);
				if (array_key_exists('date_format', $values)) {
					unset($values['date_format']);
				}
				if (array_key_exists('datetime_format', $values)) {
					unset($values['datetime_format']);
				}
				$values = JsonUtils::arrayToJson($values);
				
				DB::table($settingTable)->where('name', 'app')->update(['field_values' => $values]);
			}
			
			$languages = Language::query()->withoutGlobalScopes([ActiveScope::class])->get();
			if ($languages->count() > 0) {
				foreach ($languages as $language) {
					$language->date_format = null;
					$language->datetime_format = null;
					$language->save();
				}
			}
			
			$countries = Country::query()->withoutGlobalScopes([ActiveScope::class, LocalizedScope::class])->get();
			if ($countries->count() > 0) {
				foreach ($countries as $country) {
					$country->date_format = null;
					$country->datetime_format = null;
					$country->save();
				}
			}
		}
	}
}
