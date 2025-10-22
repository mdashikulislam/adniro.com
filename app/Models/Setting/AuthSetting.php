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
 * settings.auth.option
 */

use App\Helpers\Common\Files\Upload;
use App\Helpers\Common\TimeRangeGenerator;
use Illuminate\Support\Facades\Storage;

class AuthSetting extends BaseSetting
{
	public static function passedValidation($request)
	{
		$mediaOpPath = 'larapen.media.resize.namedOptions';
		$params = [
			[
				'attribute' => 'hero_image_path',
				'destPath'  => 'app/auth',
				'width'     => (int)config($mediaOpPath . '.bg-body.width', 2500),
				'height'    => (int)config($mediaOpPath . '.bg-body.height', 2500),
				'ratio'     => config($mediaOpPath . '.bg-body.ratio', '1'),
				'upsize'    => config($mediaOpPath . '.bg-body.upsize', '0'),
				'filename'  => 'auth-hero-image-',
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
		
		$defaultHeroImagePath = config('larapen.media.auth_hero_image');
		
		$defaultValue = [
			'open_login_in_modal'              => '1',
			'login_max_attempts'               => '5',
			'login_decay_minutes'              => '15',
			'password_min_length'              => '6',
			'password_max_length'              => '30',
			'password_letters_required'        => '0',
			'password_mixedCase_required'      => '0',
			'password_numbers_required'        => '0',
			'password_symbols_required'        => '0',
			'password_uncompromised_required'  => '0',
			'password_uncompromised_threshold' => '0',
			'email_validator_rfc'              => '1',
			'email_validator_strict'           => '0',
			'email_validator_dns'              => '0',
			'email_validator_spoof'            => '0',
			'email_validator_filter'           => '0',
			'otp_length'                       => 4,
			'otp_expire_time_seconds'          => 60 * 5,
			'otp_cooldown_seconds'             => 60,
			'otp_max_attempts'                 => 3,
			'otp_decay_minutes'                => 60,
			'max_login_lockout_attempts'       => 0,
			'max_resend_lockout_attempts'      => 15,
			'lockout_duration_minutes'         => 1440,
			'hero_image_path'                  => $defaultHeroImagePath,
		];
		
		$value = array_merge($defaultValue, $value);
		
		/** @var $disk Storage */
		$filePathList = ['hero_image_path'];
		foreach ($value as $key => $item) {
			if (in_array($key, $filePathList)) {
				if (empty($item) || !$disk->exists($item)) {
					$value[$key] = $defaultValue[$key] ?? null;
				}
			}
		}
		
		// Append files URLs
		// hero_image_url
		$heroImage = $value['hero_image_path'] ?? $value['login_bg_image'] ?? $defaultHeroImagePath;
		$value['hero_image_url'] = thumbService($heroImage, false)->resize('bg-body')->url();
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [];
		
		// Login Options
		$tabName = trans('admin.auth_limiting_login_attempts_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'limiting_login_attempts_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'limiting_login_attempts_info',
			'type'  => 'custom_html',
			'value' => trans('admin.card_light', ['content' => trans('admin.auth_limiting_login_attempts_info')]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'login_max_attempts',
			'label'   => trans('admin.login_max_attempts_label'),
			'type'    => 'select2_from_array',
			'options' => self::getMaxAttempts(30),
			'hint'    => trans('admin.login_max_attempts_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'login_decay_minutes',
			'label'   => trans('admin.login_decay_label'),
			'type'    => 'select2_from_array',
			'options' => self::getDecayRangeInMinutes(),
			'hint'    => trans('admin.login_decay_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		// Password Validation Options
		$tabName = trans('admin.password_validator_title_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'password_validator_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'       => 'password_min_length',
			'label'      => trans('admin.password_min_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'step' => 1,
				'max'  => 100,
			],
			'hint'       => trans('admin.password_min_length_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'password_max_length',
			'label'      => trans('admin.password_max_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'step' => 1,
				'max'  => 100,
			],
			'hint'       => trans('admin.password_max_length_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'password_letters_required',
			'label'   => trans('admin.password_letters_required_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.password_letters_required_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'password_mixedCase_required',
			'label'   => trans('admin.password_mixedCase_required_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.password_mixedCase_required_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'password_numbers_required',
			'label'   => trans('admin.password_numbers_required_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.password_numbers_required_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'password_symbols_required',
			'label'   => trans('admin.password_symbols_required_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.password_symbols_required_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'password_uncompromised_required',
			'label'   => trans('admin.password_uncompromised_required_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.password_uncompromised_required_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'password_uncompromised_threshold',
			'label'      => trans('admin.password_uncompromised_threshold_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'step' => 1,
				'max'  => 10,
			],
			'hint'       => trans('admin.password_uncompromised_threshold_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 mt-4',
			],
			'tab'        => $tabName,
		];
		
		// Email Address Validation Options
		$tabName = trans('admin.email_validator_title_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'email_validator_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'email_validator_rfc',
			'label'   => trans('admin.email_validator_rfc_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.email_validator_rfc_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'email_validator_strict',
			'label'   => trans('admin.email_validator_strict_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.email_validator_strict_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'email_validator_dns',
			'label'   => trans('admin.email_validator_dns_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.email_validator_dns_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'email_validator_spoof',
			'label'   => trans('admin.email_validator_spoof_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.email_validator_spoof_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'email_validator_filter',
			'label'   => trans('admin.email_validator_filter_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.email_validator_filter_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		// Two-Factor Authentication
		$tabName = trans('admin.2fa_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => '2fa_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => '2fa_info',
			'type'  => 'custom_html',
			'value' => trans('admin.card_light', ['content' => trans('admin.2fa_info')]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => '2fa_with_mail',
			'label'   => trans('admin.2fa_with_mail_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.2fa_with_mail_hint', ['settingUrl' => urlGen()->adminUrl('settings/find/mail')]),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => '2fa_with_sms',
			'label'   => trans('admin.2fa_with_sms_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.2fa_with_sms_hint', ['settingUrl' => urlGen()->adminUrl('settings/find/sms')]),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'require_2fa_challenge_on_enable',
			'label'   => trans('admin.require_2fa_challenge_on_enable_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.require_2fa_challenge_on_enable_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'otp_length',
			'label'   => trans('admin.otp_length_label'),
			'type'    => 'select2_from_array',
			'options' => self::getOptLengths(),
			'hint'    => trans('admin.otp_length_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'otp_expire_time_seconds',
			'label'   => trans('admin.otp_expire_time_label'),
			'type'    => 'select2_from_array',
			'options' => self::getOtpExpireTimeRangeInSeconds(),
			'hint'    => trans('admin.otp_expire_time_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'otp_cooldown_seconds',
			'label'   => trans('admin.otp_cooldown_label'),
			'type'    => 'select2_from_array',
			'options' => self::getOtpCooldownInSeconds(),
			'hint'    => trans('admin.otp_cooldown_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'otp_max_attempts',
			'label'   => trans('admin.otp_max_attempts_label'),
			'type'    => 'select2_from_array',
			'options' => self::getMaxAttempts(),
			'hint'    => trans('admin.otp_max_attempts_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'otp_decay_minutes',
			'label'   => trans('admin.otp_decay_label'),
			'type'    => 'select2_from_array',
			'options' => self::getDecayRangeInMinutes(),
			'hint'    => trans('admin.otp_decay_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		// Auto-Lockout Options
		$tabName = trans('admin.auth_lockout_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'auth_lockout_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'auth_lockout_info',
			'type'  => 'custom_html',
			'value' => trans('admin.card_light', ['content' => trans('admin.auth_lockout_info')]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'max_login_lockout_attempts',
			'label'   => trans('admin.max_login_lockout_attempts_label'),
			'type'    => 'select2_from_array',
			'options' => self::getMaxAttempts(100),
			'hint'    => trans('admin.max_login_lockout_attempts_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'max_resend_lockout_attempts',
			'label'   => trans('admin.max_resend_lockout_attempts_label'),
			'type'    => 'select2_from_array',
			'options' => self::getMaxAttempts(100),
			'hint'    => trans('admin.max_resend_lockout_attempts_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'lockout_duration_minutes',
			'label'   => trans('admin.lockout_duration_label'),
			'type'    => 'select2_from_array',
			'options' => self::getLockoutDurationTimeRangeInMinutes(),
			'hint'    => trans('admin.lockout_duration_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		// User's Suspension & ban
		$tabName = trans('admin.user_suspension_and_ban_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'user_suspension_and_ban_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'send_notification_on_user_suspension',
			'label'   => trans('admin.send_notification_on_user_suspension_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'none'        => trans('admin.send_notification_on_user_suspension_ban_option_0'),
				'send'        => trans('admin.send_notification_on_user_suspension_ban_option_1'),
				'forceToSend' => trans('admin.send_notification_on_user_suspension_ban_option_2'),
			],
			'hint'    => trans('admin.send_notification_on_user_suspension_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'send_notification_on_user_ban',
			'label'   => trans('admin.send_notification_on_user_ban_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'none'        => trans('admin.send_notification_on_user_suspension_ban_option_0'),
				'send'        => trans('admin.send_notification_on_user_suspension_ban_option_1'),
				'forceToSend' => trans('admin.send_notification_on_user_suspension_ban_option_2'),
			],
			'hint'    => trans('admin.send_notification_on_user_ban_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		// Auth Other Options
		$tabName = trans('admin.auth_other_options_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'other_options_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'open_login_in_modal',
			'label' => trans('admin.open_login_in_modal_label'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.open_login_in_modal_hint'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'hero_image_path',
			'label'   => trans('admin.auth_hero_image_label'),
			'type'    => 'image',
			'upload'  => true,
			'disk'    => $diskName,
			'default' => config('larapen.media.auth_hero_image'),
			'hint'    => trans('admin.auth_hero_image_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
	
	/**
	 * @param int $max
	 * @return array
	 */
	private static function getMaxAttempts(int $max = 30): array
	{
		$array = [];
		for ($i = 0; $i <= $max; $i++) {
			$array[$i] = ($i == 0) ? trans('admin.disabled') : $i;
		}
		
		return $array;
	}
	
	/**
	 * @return array
	 */
	private static function getDecayRangeInMinutes(): array
	{
		return TimeRangeGenerator::generateRange(
			startUnit: 'minute',
			endUnit: 'day',
			limits: [
				'day' => 1,
			],
			keyUnit: 'minute'
		);
	}
	
	/**
	 * @return array
	 */
	private static function getOtpExpireTimeRangeInSeconds(): array
	{
		return TimeRangeGenerator::generateRange(
			startUnit: 'minute',
			endUnit: 'week',
			limits: [
				'week' => 1,
			],
			keyUnit: 'second'
		);
	}
	
	/**
	 * @return array
	 */
	private static function getOtpCooldownInSeconds(): array
	{
		return TimeRangeGenerator::generateRange(
			startUnit: 'second',
			endUnit: 'day',
			limits: [
				'day' => 1,
			],
			keyUnit: 'second'
		);
	}
	
	/**
	 * @return array
	 */
	private static function getOptLengths(): array
	{
		$array = [];
		for ($i = 4; $i <= 8; $i++) {
			$array[$i] = $i;
		}
		
		return $array;
	}
	
	/**
	 * @return array
	 */
	private static function getLockoutDurationTimeRangeInMinutes(): array
	{
		return TimeRangeGenerator::generateRange(
			startUnit: 'minute',
			limits: [
				'year' => 1,
			],
			keyUnit: 'minute'
		);
	}
}
