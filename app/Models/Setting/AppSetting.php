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

namespace App\Models\Setting;

use App\Helpers\Common\Files\Upload;
use Illuminate\Support\Facades\Storage;

/*
 * settings.app.option
 */

class AppSetting extends BaseSetting
{
	public static function passedValidation($request)
	{
		$params = [
			[
				'attribute' => 'logo',
				'destPath'  => 'app/logo',
				'width'     => (int)config('settings.upload.img_resize_logo_width', 454),
				'height'    => (int)config('settings.upload.img_resize_logo_height', 80),
				'ratio'     => config('settings.upload.img_resize_logo_ratio', '1'),
				'upsize'    => config('settings.upload.img_resize_logo_upsize', '1'),
				'filename'  => 'logo-',
			],
			[
				'attribute' => 'logo_dark',
				'destPath'  => 'app/logo',
				'width'     => (int)config('settings.upload.img_resize_logo_width', 454),
				'height'    => (int)config('settings.upload.img_resize_logo_height', 80),
				'ratio'     => config('settings.upload.img_resize_logo_ratio', '1'),
				'upsize'    => config('settings.upload.img_resize_logo_upsize', '0'),
				'filename'  => 'logo-dark-',
			],
			[
				'attribute' => 'logo_light',
				'destPath'  => 'app/logo',
				'width'     => (int)config('settings.upload.img_resize_logo_width', 454),
				'height'    => (int)config('settings.upload.img_resize_logo_height', 80),
				'ratio'     => config('settings.upload.img_resize_logo_ratio', '1'),
				'upsize'    => config('settings.upload.img_resize_logo_upsize', '0'),
				'filename'  => 'logo-light-',
			],
			[
				'attribute' => 'favicon',
				'destPath'  => 'app/ico',
				'width'     => (int)config('settings.upload.img_resize_favicon_width', 32),
				'height'    => (int)config('settings.upload.img_resize_favicon_height', 32),
				'ratio'     => config('settings.upload.img_resize_favicon_ratio', '1'),
				'upsize'    => config('settings.upload.img_resize_favicon_upsize', '0'),
				'filename'  => 'ico-',
			],
		];
		
		foreach ($params as $param) {
			$file = $request->hasFile($param['attribute'])
				? $request->file($param['attribute'])
				: $request->input($param['attribute']);
			
			$request->request->set($param['attribute'], Upload::image($file, $param['destPath'], $param));
		}
		
		return $request;
	}
	
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		// Required keys & values
		// If $value exists and these keys don't exist, then set their default values
		$defaultValue = [
			'purchase_code'                          => env('PURCHASE_CODE', ''),
			'name'                                   => config('app.name'),
			'logo'                                   => config('larapen.media.logo'),
			'logo_dark'                              => config('larapen.media.logo-dark'),
			'logo_light'                             => config('larapen.media.logo-light'),
			'favicon'                                => config('larapen.media.favicon'),
			'date_format'                            => config('larapen.core.dateFormat.default'),
			'datetime_format'                        => config('larapen.core.datetimeFormat.default'),
			'date_from_now_modifier'                 => 'DIFF_RELATIVE_TO_NOW',
			'date_from_now_short'                    => '0',
			'vector_charts_type'                     => 'morris_bar',
			'vector_charts_limit'                    => 7,
			'show_countries_charts'                  => '1',
			'countries_charts_limit'                 => 5,
			'latest_entries_limit'                   => 5,
			'general_settings_as_submenu_in_sidebar' => '1',
		];
		
		$value = array_merge($defaultValue, $value);
		
		/** @var $disk Storage */
		$filePathList = ['logo', 'logo_dark', 'logo_light', 'favicon'];
		foreach ($value as $key => $item) {
			if ($key == 'logo') {
				$item = str_replace('uploads/', '', $item);
			}
			
			if (in_array($key, $filePathList)) {
				if (empty($item) || !$disk->exists($item)) {
					$value[$key] = $defaultValue[$key] ?? null;
				}
			}
		}
		
		// logo
		$defaultLogo = 'app/default/logo.png';
		$defaultLogo = config('larapen.media.logo', $defaultLogo);
		$logo = $value['logo'] ?? $defaultLogo;
		
		// logo_dark
		$defaultLogoDark = 'app/default/logo-dark.png';
		$defaultLogoDark = config('larapen.media.logo-dark', $defaultLogoDark);
		$logoDark = $value['logo_dark'] ?? $defaultLogoDark;
		if ($logoDark == $defaultLogoDark && $logo != $defaultLogo) {
			if (!empty($logo) && $disk->exists($logo)) {
				$logoDark = $logo;
			}
		}
		
		// logo_light
		$defaultLogoLight = 'app/default/logo-light.png';
		$defaultLogoLight = config('larapen.media.logo-light', $defaultLogoLight);
		$logoLight = $value['logo_light'] ?? $defaultLogoLight;
		if ($logoLight == $defaultLogoLight && $logo != $defaultLogo) {
			if (!empty($logo) && $disk->exists($logo)) {
				$logoLight = $logo;
			}
		}
		
		// Append files URLs
		// logo
		$value['logo_url'] = thumbService($logo)->resize('logo')->url();
		$value['logo_dark_url'] = thumbService($logoDark)->resize('logo')->url();
		$value['logo_light_url'] = thumbService($logoLight)->resize('logo')->url();
		
		// favicon_url
		$favicon = 'app/default/ico/favicon.png';
		$favicon = $value['favicon'] ?? config('larapen.media.favicon', $favicon);
		$value['favicon_url'] = thumbService($favicon)->resize('favicon')->url();
		
		// Old versions fix
		if (array_key_exists('dark_mode', $value)) {
			$value['dark_theme_enabled'] = $value['dark_mode'] ?? '0';
		}
		
		// Clean some entries
		$entriesToClean = ['name', 'slogan'];
		foreach ($entriesToClean as $entry) {
			if (!empty($value[$entry])) {
				$value[$entry] = singleLineStringCleanerStrict($value[$entry]);
			}
		}
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [];
		
		// App's Info
		$tabName = trans('admin.app_html_brand_info');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'separator_1',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'purchase_code',
			'label' => trans('admin.Purchase Code'),
			'type'  => 'text',
			'hint'  => trans('admin.find_my_purchase_code', [
				'purchaseCodeFindingUrl' => config('larapen.core.purchaseCodeFindingUrl'),
			]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'name',
			'label'   => trans('admin.App Name'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'slogan',
			'label'   => trans('admin.App Slogan'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		// App's Logo
		$fields[] = [
			'name'    => 'logo',
			'label'   => trans('admin.App Logo'),
			'type'    => 'image',
			'upload'  => true,
			'disk'    => $diskName,
			'default' => config('larapen.media.logo'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'favicon',
			'label'   => trans('admin.Favicon'),
			'type'    => 'image',
			'upload'  => true,
			'disk'    => $diskName,
			'default' => config('larapen.media.favicon'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'dark_theme_enabled',
			'label'   => trans('admin.dark_theme_enabled_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.dark_theme_enabled_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'system_theme_enabled',
			'label'   => trans('admin.system_theme_enabled_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.system_theme_enabled_hint'),
			'wrapper' => [
				'class' => 'col-md-6 dark-mode-field',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'logo_dark',
			'label'   => trans('admin.logo_dark_label'),
			'type'    => 'image',
			'upload'  => true,
			'disk'    => $diskName,
			'default' => config('larapen.media.logo-dark'),
			'hint'    => trans('admin.logo_dark_hint'),
			'wrapper' => [
				'class' => 'col-md-12 dark-mode-field',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'logo_light',
			'label'   => trans('admin.logo_light_label'),
			'type'    => 'image',
			'upload'  => true,
			'disk'    => $diskName,
			'default' => config('larapen.media.logo-light'),
			'hint'    => trans('admin.logo_light_hint'),
			'wrapper' => [
				'class' => 'col-md-12 dark-mode-field',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		// App's Contact Info
		$fields[] = [
			'name'    => 'email',
			'label'   => trans('admin.Email'),
			'type'    => 'email',
			'hint'    => trans('admin.The email address that all emails from the contact form will go to'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'phone_number',
			'label'   => trans('admin.Phone number'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		// Date Parameters
		$tabName = trans('admin.dates_title');
		$phpDateFormat = config('larapen.core.dateFormat.php');
		$phpDatetimeFormat = config('larapen.core.datetimeFormat.php');
		$phpDateFormatHint = trans('admin.php_date_format_hint', ['year' => date('Y')]);
		
		$isoDateFormat = config('larapen.core.dateFormat.default');
		$isoDatetimeFormat = config('larapen.core.datetimeFormat.default');
		$isoDateFormatHint = trans('admin.iso_date_format_hint', ['year' => date('Y')]);
		
		if (config('settings.app.php_specific_date_format')) {
			$dateFormat = $phpDateFormat;
			$datetimeFormat = $phpDatetimeFormat;
			$dateFormatHint = $phpDateFormatHint;
		} else {
			$dateFormat = $isoDateFormat;
			$datetimeFormat = $isoDatetimeFormat;
			$dateFormatHint = $isoDateFormatHint;
		}
		
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'dates_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'php_specific_date_format',
			'label'   => trans('admin.php_specific_date_format_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.php_specific_date_format_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'  => 'php_specific_date_format_info',
			'type'  => 'custom_html',
			'value' => trans('admin.php_specific_date_format_info'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'date_format',
			'label'   => trans('admin.date_format_label'),
			'type'    => 'text',
			'default' => $dateFormat,
			'hint'    => $dateFormatHint . ' ' . trans('admin.app_date_format_hint_help'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'datetime_format',
			'label'   => trans('admin.datetime_format_label'),
			'type'    => 'text',
			'default' => $datetimeFormat,
			'hint'    => $dateFormatHint . ' ' . trans('admin.app_date_format_hint_help'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'  => 'admin_date_format_info',
			'type'  => 'custom_html',
			'value' => trans('admin.admin_date_format_info', [
				'languagesUrl' => urlGen()->adminUrl('languages'),
				'countriesUrl' => urlGen()->adminUrl('countries'),
			]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'date_from_now_options_title',
			'type'  => 'custom_html',
			'value' => trans('admin.date_from_now_options_title', [
				'listingsList'    => trans('settings.listings_list'),
				'listingsListUrl' => urlGen()->adminUrl('settings/find/listings_list'),
				'listingPage'     => trans('settings.listing_page'),
				'listingPageUrl'  => urlGen()->adminUrl('settings/find/listing_page'),
			]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'date_from_now_modifier',
			'label'   => trans('admin.date_from_now_modifier_label'),
			'type'    => 'select2_from_array',
			'options' => self::getDateFromNowModifiers(),
			'hint'    => trans('admin.date_from_now_modifier_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'date_from_now_short',
			'label'   => trans('admin.date_from_now_short_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.date_from_now_short_hint'),
			'wrapper' => [
				'class' => 'col-md-6 mt-4',
			],
			'tab'     => $tabName,
		];
		
		// Admin Panel Dashboard
		$tabName = trans('admin.backend_title_separator');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'backend_title_separator',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'settings_app_dashboard_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.settings_app_dashboard_sep'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'vector_charts_type',
			'label'   => trans('admin.vector_charts_type_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'morris_bar'  => 'Morris - Bar Charts',
				'morris_line' => 'Morris - Line Charts',
			],
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'vector_charts_limit',
			'label'   => trans('admin.vector_charts_limit_label'),
			'type'    => 'select2_from_array',
			'options' => collect(generateNumberRange(2, 15, 1))->mapWithKeys(fn ($i) => [$i => $i])->toArray(),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'show_countries_charts',
			'label'   => trans('admin.show_countries_charts_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6 mt-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'countries_charts_limit',
			'label'   => trans('admin.countries_charts_limit_label'),
			'type'    => 'select2_from_array',
			'options' => collect(generateNumberRange(2, 10, 1))->mapWithKeys(fn ($i) => [$i => $i])->toArray(),
			'wrapper' => [
				'class' => 'col-md-6 countries-charts-field',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'latest_entries_limit',
			'label'   => trans('admin.settings_app_latest_entries_limit_label'),
			'type'    => 'select2_from_array',
			'options' => collect(generateNumberRange(5, 25, 5))->mapWithKeys(fn ($i) => [$i => $i])->toArray(),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'general_settings_as_submenu_in_sidebar',
			'label'   => trans('admin.general_settings_as_submenu_in_sidebar_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6 mt-4',
			],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields, [
			'phpDateFormatHint' => $phpDateFormatHint,
			'phpDateFormat'     => $phpDateFormat,
			'phpDatetimeFormat' => $phpDatetimeFormat,
			'isoDateFormatHint' => $isoDateFormatHint,
			'isoDateFormat'     => $isoDateFormat,
			'isoDatetimeFormat' => $isoDatetimeFormat,
		]);
	}
	
	/**
	 * @return array
	 */
	private static function getDateFromNowModifiers(): array
	{
		$dateFromNowModifiers = [
			'DIFF_ABSOLUTE',
			'DIFF_RELATIVE_TO_NOW',
			'DIFF_RELATIVE_TO_OTHER',
		];
		
		return collect($dateFromNowModifiers)
			->mapWithKeys(function ($item, $key) {
				$index = $key + 1;
				$label = str($item)->remove('DIFF_')->replace('_', ' ')->title();
				
				return [$item => $index . '. ' . $label];
			})->toArray();
	}
}
