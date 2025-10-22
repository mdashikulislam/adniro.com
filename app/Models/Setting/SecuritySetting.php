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
 * settings.security.option
 */

class SecuritySetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'honeypot_enabled'               => '1',
			'honeypot_name_field_name'       => 'entity_field',
			'honeypot_valid_from_field_name' => 'valid_field',
			'honeypot_amount_of_seconds'     => '3',
			'honeypot_respond_to_spam_with'  => 'blank_page',
			'captcha_delay'                  => '1000',
			'recaptcha_version'              => 'v2',
			'recaptcha_v2_site_key'          => $value['recaptcha_public_key'] ?? null, // from old saved value
			'recaptcha_v2_secret_key'        => $value['recaptcha_private_key'] ?? null, // from old saved value
		];
		
		return array_merge($defaultValue, $value);
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [];
		
		$tabName = trans('admin.csrf_protection_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'csrf_protection_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'csrf_protection',
			'label'   => trans('admin.csrf_protection_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.csrf_protection_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.honeypot_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'honeypot_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'honeypot_enabled',
			'label'   => trans('admin.honeypot_enabled_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.honeypot_enabled_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'honeypot_name_field_name',
			'label'   => trans('admin.honeypot_name_field_name_label'),
			'type'    => 'text',
			'hint'    => trans('admin.honeypot_name_field_name_hint'),
			'wrapper' => [
				'class' => 'col-md-6 honeypot-el',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'honeypot_respond_to_spam_with',
			'label'   => trans('admin.honeypot_respond_to_spam_with_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'blank_page'     => 'Blank Page',
				'http_error_500' => 'HTTP Error 500',
			],
			'hint'    => trans('admin.honeypot_respond_to_spam_with_hint'),
			'wrapper' => [
				'class' => 'col-md-6 honeypot-el',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'honeypot_separator_1',
			'type'    => 'custom_html',
			'value'   => '<div style="clear: both;"></div>',
			'wrapper' => [
				'class' => 'col-md-12 extended honeypot-el',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'honeypot_randomize_name_field_name',
			'label'   => trans('admin.honeypot_randomize_name_field_name_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.honeypot_randomize_name_field_name_hint'),
			'wrapper' => [
				'class' => 'col-md-6 honeypot-el',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'honeypot_valid_from_timestamp',
			'label'   => trans('admin.honeypot_valid_from_timestamp_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.honeypot_valid_from_timestamp_hint'),
			'wrapper' => [
				'class' => 'col-md-6 honeypot-el',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'honeypot_valid_from_field_name',
			'label'   => trans('admin.honeypot_valid_from_field_name_label'),
			'type'    => 'text',
			'hint'    => trans('admin.honeypot_valid_from_field_name_hint'),
			'wrapper' => [
				'class' => 'col-md-6 honeypot-el honeypot-timestamp-el',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'honeypot_amount_of_seconds',
			'label'      => trans('admin.honeypot_amount_of_seconds_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => 3600,
				'step' => 1,
			],
			'hint'       => trans('admin.honeypot_amount_of_seconds_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 honeypot-el honeypot-timestamp-el',
			],
			'tab'        => $tabName,
		];
		
		$tabName = trans('admin.captcha_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'captcha_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'captcha',
			'label'   => trans('admin.captcha_label'),
			'type'    => 'select2_from_array',
			'options' => [
				''          => 'Disabled',
				'default'   => 'Simple Captcha (Default)',
				'math'      => 'Simple Captcha (Math)',
				'flat'      => 'Simple Captcha (Flat)',
				'mini'      => 'Simple Captcha (Mini)',
				'inverse'   => 'Simple Captcha (Inverse)',
				'custom'    => 'Simple Captcha (Custom)',
				'recaptcha' => 'Google reCAPTCHA',
			],
			'hint'    => trans('admin.captcha_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_delay',
			'label'   => trans('admin.captcha_delay_label'),
			'type'    => 'select2_from_array',
			'options' => [
				1000 => '1000ms',
				1100 => '1100ms',
				1200 => '1200ms',
				1300 => '1300ms',
				1400 => '1400ms',
				1500 => '1500ms',
				1600 => '1600ms',
				1700 => '1700ms',
				1800 => '1800ms',
				1900 => '1900ms',
				2000 => '2000ms',
				2500 => '2500ms',
				3000 => '3000ms',
			],
			'hint'    => trans('admin.captcha_delay_hint'),
			'wrapper' => [
				'class' => 'col-md-6 s-captcha',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_custom',
			'type'    => 'custom_html',
			'value'   => trans('admin.captcha_custom'),
			'wrapper' => [
				'class' => 'col-md-12 s-captcha s-captcha-custom',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_custom_info',
			'type'    => 'custom_html',
			'value'   => trans('admin.captcha_custom_info'),
			'wrapper' => [
				'class' => 'col-md-12 s-captcha s-captcha-custom',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_width',
			'label'      => trans('admin.captcha_width_label', ['max' => 300]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 100,
				'max'  => 300,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_height',
			'label'      => trans('admin.captcha_height_label', ['max' => 150]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 30,
				'max'  => 150,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_length',
			'label'      => trans('admin.captcha_length_label', ['max' => 8]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 3,
				'max'  => 8,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_quality',
			'label'      => trans('admin.captcha_quality_label', ['max' => 100]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => 100,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_bgImage',
			'label'   => trans('admin.captcha_bgImage_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.captcha_bgImage_hint'),
			'wrapper' => [
				'class' => 'col-md-12 s-captcha s-captcha-custom',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_bgColor',
			'label'      => trans('admin.captcha_bgColor_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '',
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_lines',
			'label'      => trans('admin.captcha_lines_label', ['max' => 20]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 20,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-3 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_angle',
			'label'      => trans('admin.captcha_angle_label', ['max' => 180]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 180,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-3 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_sharpen',
			'label'      => trans('admin.captcha_sharpen_label', ['max' => 20]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 20,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_blur',
			'label'      => trans('admin.captcha_blur_label', ['max' => 20]),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 20,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_contrast',
			'label'      => trans('admin.captcha_contrast_label', ['max' => 50]),
			'type'       => 'number',
			'attributes' => [
				'min'  => -50,
				'max'  => 50,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'captcha_expire',
			'label'      => trans('admin.captcha_expire_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_math',
			'label'   => trans('admin.captcha_math_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.captcha_math_hint'),
			'wrapper' => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_encrypt',
			'label'   => trans('admin.captcha_encrypt_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.captcha_encrypt_hint'),
			'wrapper' => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_sensitive',
			'label'   => trans('admin.captcha_sensitive_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.captcha_sensitive_hint'),
			'wrapper' => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'captcha_invert',
			'label'   => trans('admin.captcha_invert_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.captcha_invert_hint'),
			'wrapper' => [
				'class' => 'col-md-6 s-captcha s-captcha-custom',
			],
			'tab'     => $tabName,
		];
		
		// ==========
		
		$fields[] = [
			'name'    => 'recaptcha_sep_info',
			'type'    => 'custom_html',
			'value'   => trans('admin.recaptcha_sep_info_value'),
			'wrapper' => [
				'class' => 'col-md-12 recaptcha',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'recaptcha_version',
			'label'   => trans('admin.recaptcha_version_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'v2' => 'v2 (Checkbox)',
				'v3' => 'v3',
			],
			'hint'    => trans('admin.recaptcha_version_hint'),
			'wrapper' => [
				'class' => 'col-md-6 recaptcha',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'separator_clear_recaptcha',
			'type'    => 'custom_html',
			'value'   => '<div style="clear: both;"></div>',
			'wrapper' => [
				'class' => 'col-md-12 recaptcha',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'recaptcha_v2_site_key',
			'label'   => trans('admin.recaptcha_v2_site_key_label'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-12 recaptcha recaptcha-v2',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'recaptcha_v2_secret_key',
			'label'   => trans('admin.recaptcha_v2_secret_key_label'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-12 recaptcha recaptcha-v2',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'recaptcha_v3_site_key',
			'label'   => trans('admin.recaptcha_v3_site_key_label'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-12 recaptcha recaptcha-v3',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'recaptcha_v3_secret_key',
			'label'   => trans('admin.recaptcha_v3_secret_key_label'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-12 recaptcha recaptcha-v3',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'recaptcha_skip_ips',
			'label'   => trans('admin.recaptcha_skip_ips_label'),
			'type'    => 'textarea',
			'hint'    => trans('admin.recaptcha_skip_ips_hint'),
			'wrapper' => [
				'class' => 'col-md-12 recaptcha',
			],
			'tab'     => $tabName,
		];
		
		// ==========
		
		$tabName = trans('admin.others_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'others_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'unsaved_form_guard',
			'label'   => trans('admin.unsaved_form_guard_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.unsaved_form_guard_hint'),
			'wrapper' => [
				// 'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
}
