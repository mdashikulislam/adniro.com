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

/*
 * settings.localization.option
 */

use App\Models\Country;

class LocalizationSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$oldAutoDetectLanguage = config('settings.app.auto_detect_language', '0');
		$oldAutoDetectLanguageOptions = [
			'0' => 'disabled',
			'1' => 'from_browser',
			'2' => 'from_country',
		];
		$oldShowLanguagesFlags = config('settings.app.show_languages_flags', '0');
		$defaultCountryFlagShape = 'circle'; // rectangle, circle, hexagon
		
		$defaultValue = [
			'geoip_activation'     => $value['active'] ?? null, // from old saved value
			'geoip_driver'         => 'ipapi',
			'country_flag_shape'   => $defaultCountryFlagShape,
			'show_country_flag'    => 'in_next_logo',
			'auto_detect_language' => $oldAutoDetectLanguageOptions[$oldAutoDetectLanguage] ?? 'disabled',
			'show_languages_flags' => $oldShowLanguagesFlags,
		];
		
		$value = array_merge($defaultValue, $value);
		
		// Validate the 'country_flag_shape' value
		$flagShapes = array_keys(getCountryFlagShapes());
		$countryFlagShape = $value['country_flag_shape'] ?? $defaultCountryFlagShape;
		$value['country_flag_shape'] = in_array($countryFlagShape, $flagShapes)
			? $countryFlagShape
			: $defaultCountryFlagShape;
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		// Get Drivers List
		$geoipDrivers = (array)config('larapen.options.geoip');
		
		// Get the drivers selectors list as JS objects
		$geoipDriversSelectorsJson = collect($geoipDrivers)
			->keys()
			->mapWithKeys(fn ($item) => [$item => '.' . $item])
			->toJson();
		
		$fields = [];
		
		// Geo Location
		$tabName = trans('admin.localization_geolocation');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'localization_geolocation',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'geoip_activation',
			'label'   => trans('admin.geoip_activation_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.geoip_activation_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'geoip_driver',
			'label'   => trans('admin.geoip_driver_label'),
			'type'    => 'select2_from_array',
			'options' => $geoipDrivers,
			'hint'    => trans('admin.geoip_driver_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'geoip_driver_test',
			'label'   => trans('admin.geoip_driver_test_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.geoip_driver_test_hint'),
			'wrapper' => [
				'class' => 'col-md-12 mt-3',
			],
			'tab'     => $tabName,
		];
		
		// ipinfo.io
		if (array_key_exists('ipinfo', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'ipinfo_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.ipinfo_info'),
				'wrapper' => [
					'class' => 'ipinfo',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'ipinfo_token',
				'label'   => trans('admin.ipinfo_token_label'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-12 ipinfo',
				],
				'tab'     => $tabName,
			];
		}
		
		// db-ip.com
		if (array_key_exists('dbip', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'dbip_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.dbip_info'),
				'wrapper' => [
					'class' => 'dbip',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'dbip_pro',
				'label'   => trans('admin.geoip_driver_pro_label'),
				'type'    => 'checkbox_switch',
				'wrapper' => [
					'class' => 'col-md-6 dbip',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'dbip_api_key',
				'label'   => trans('admin.dbip_api_key_label'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-12 dbip',
				],
				'tab'     => $tabName,
			];
		}
		
		// ipbase.com
		if (array_key_exists('ipbase', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'ipbase_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.ipbase_info'),
				'wrapper' => [
					'class' => 'ipbase',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'     => 'ipbase_api_key',
				'label'    => trans('admin.ipbase_api_key_label'),
				'type'     => 'text',
				'required' => true,
				'wrapper'  => [
					'class' => 'col-md-12 ipbase',
				],
				'tab'      => $tabName,
			];
		}
		
		// ip2location.com
		if (array_key_exists('ip2location', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'ip2location_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.ip2location_info'),
				'wrapper' => [
					'class' => 'ip2location',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'     => 'ip2location_api_key',
				'label'    => trans('admin.ip2location_api_key_label'),
				'type'     => 'text',
				'required' => true,
				'wrapper'  => [
					'class' => 'col-md-12 ip2location',
				],
				'tab'      => $tabName,
			];
		}
		
		// ip-api.com
		if (array_key_exists('ipapi', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'ipapi_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.ipapi_info'),
				'wrapper' => [
					'class' => 'ipapi',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'ipapi_pro',
				'label'   => trans('admin.geoip_driver_pro_label'),
				'type'    => 'checkbox_switch',
				'wrapper' => [
					'class' => 'col-md-6 ipapi',
				],
				'tab'     => $tabName,
			];
		}
		
		// ipapi.co
		if (array_key_exists('ipapico', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'ipapico_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.ipapico_info'),
				'wrapper' => [
					'class' => 'ipapico',
				],
				'tab'     => $tabName,
			];
		}
		
		// ipgeolocation.io
		if (array_key_exists('ipgeolocation', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'ipgeolocation_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.ipgeolocation_info'),
				'wrapper' => [
					'class' => 'ipgeolocation',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'     => 'ipgeolocation_api_key',
				'label'    => trans('admin.ipgeolocation_api_key_label'),
				'type'     => 'text',
				'required' => true,
				'wrapper'  => [
					'class' => 'col-md-12 ipgeolocation',
				],
				'tab'      => $tabName,
			];
		}
		
		// iplocation.net
		if (array_key_exists('iplocation', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'iplocation_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.iplocation_info'),
				'wrapper' => [
					'class' => 'iplocation',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'iplocation_pro',
				'label'   => trans('admin.geoip_driver_pro_label'),
				'type'    => 'checkbox_switch',
				'wrapper' => [
					'class' => 'col-md-6 iplocation',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'iplocation_api_key',
				'label'   => trans('admin.iplocation_api_key_label'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-12 iplocation',
				],
				'tab'     => $tabName,
			];
		}
		
		// ipstack.com
		if (array_key_exists('ipstack', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'ipstack_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.ipstack_info'),
				'wrapper' => [
					'class' => 'ipstack',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'ipstack_pro',
				'label'   => trans('admin.geoip_driver_pro_label'),
				'type'    => 'checkbox_switch',
				'wrapper' => [
					'class' => 'col-md-6 ipstack',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'ipstack_access_key',
				'label'   => trans('admin.ipstack_access_key_label'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-12 ipstack',
				],
				'tab'     => $tabName,
			];
		}
		
		// maxmind.com (Web Services)
		if (array_key_exists('maxmind_api', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'maxmind_api_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.maxmind_api_info'),
				'wrapper' => [
					'class' => 'maxmind_api',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'     => 'maxmind_api_account_id',
				'label'    => trans('admin.maxmind_api_account_id_label'),
				'type'     => 'text',
				'required' => true,
				'wrapper'  => [
					'class' => 'col-md-12 maxmind_api',
				],
				'tab'      => $tabName,
			];
			$fields[] = [
				'name'     => 'maxmind_api_license_key',
				'label'    => trans('admin.maxmind_api_license_key_label'),
				'type'     => 'text',
				'required' => true,
				'wrapper'  => [
					'class' => 'col-md-12 maxmind_api',
				],
				'tab'      => $tabName,
			];
		}
		
		// maxmind.com (Database)
		if (array_key_exists('maxmind_database', $geoipDrivers)) {
			$fields[] = [
				'name'    => 'maxmind_database_info',
				'type'    => 'custom_html',
				'value'   => trans('admin.maxmind_database_info'),
				'wrapper' => [
					'class' => 'maxmind_database',
				],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'maxmind_database_license_key',
				'label'   => trans('admin.maxmind_database_license_key_label'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-12 maxmind_database',
				],
				'tab'     => $tabName,
			];
		}
		
		// Country & Region
		$tabName = trans('admin.localization_country_region');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'localization_country_region',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'        => 'default_country_code',
			'label'       => trans('admin.Default Country'),
			'type'        => 'select2',
			'attribute'   => 'name',
			'model'       => Country::class,
			'allows_null' => true,
			'hint'        => trans('admin.default_country_code_hint'),
			'wrapper'     => [
				'class' => 'col-md-12',
			],
			'tab'         => $tabName,
		];
		$fields[] = [
			'name'    => 'local_currency_packages_activation',
			'label'   => trans('admin.Allow users to pay the Packages in their country currency'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.package_currency_by_country_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		// Language
		$tabName = trans('admin.localization_language');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'localization_language',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'show_country_spoken_languages',
			'label'   => trans('admin.show_country_spoken_languages_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'disabled'         => trans('admin.country_spoken_languages_option_0'),
				'active'           => trans('admin.country_spoken_languages_option_1'),
				'active_with_en'   => trans('admin.country_spoken_languages_option_2'),
				'active_with_main' => trans('admin.country_spoken_languages_option_3'),
			],
			'hint'    => trans('admin.show_country_spoken_languages_hint', [
				'field'    => trans('admin.country_spoken_languages_label'),
				'url'      => urlGen()->adminUrl('countries'),
				'option_0' => trans('admin.country_spoken_languages_option_0'),
				'option_1' => trans('admin.country_spoken_languages_option_1'),
				'option_2' => trans('admin.country_spoken_languages_option_2'),
				'option_3' => trans('admin.country_spoken_languages_option_3'),
			]),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'auto_detect_language',
			'label'   => trans('admin.auto_detect_language_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'disabled'     => trans('admin.auto_detect_language_option_0'),
				'from_browser' => trans('admin.auto_detect_language_option_1'),
				'from_country' => trans('admin.auto_detect_language_option_2'),
			],
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'  => 'auto_detect_language_warning_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.auto_detect_language_warning_sep_value'),
			'tab'   => $tabName,
		];
		
		// Front Header UI/UX
		$tabName = trans('admin.localization_front_header');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'localization_front_header',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'show_country_flag',
			'label'   => trans('admin.show_country_flag_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'disabled'     => trans('admin.show_country_flag_option_0'),
				'in_next_logo' => trans('admin.show_country_flag_option_1'),
				'in_next_lang' => trans('admin.show_country_flag_option_2'),
			],
			'hint'    => trans('admin.show_country_flag_hint', [
				'option_0' => trans('admin.show_country_flag_option_0'),
				'option_1' => trans('admin.show_country_flag_option_1'),
				'option_2' => trans('admin.show_country_flag_option_2'),
			]),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'country_flag_shape',
			'label'   => trans('admin.country_flag_shape_label'),
			'type'    => 'select2_from_array',
			'options' => getCountryFlagShapes(),
			'hint'    => trans('admin.country_flag_shape_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'show_languages_flags',
			'label'   => trans('admin.show_languages_flags_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.show_languages_flags_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields, [
			'geoipDriversSelectorsJson' => $geoipDriversSelectorsJson,
		]);
	}
}
