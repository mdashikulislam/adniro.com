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

use App\Helpers\Common\Num;
use App\Services\Auth\App\Helpers\SocialLogin\SocialLoginButton;
use Illuminate\Support\Number;

/**
 * Get password tips & requirements
 *
 * @param bool $withCommon
 * @return array
 */
function getPasswordTips(bool $withCommon = false): array
{
	$minLength = config('settings.auth.password_min_length', 6);
	$maxLength = config('settings.auth.password_max_length', 30);
	
	$tips = [];
	
	if ($withCommon) {
		$tips[] = trans('auth.password_tip_common');
	}
	$tips[] = trans('auth.password_tip_length', ['min' => $minLength, 'max' => $maxLength]);
	if (config('settings.auth.password_letters_required')) {
		$tips[] = trans('auth.password_tip_letter');
	}
	if (config('settings.auth.password_mixedCase_required')) {
		$tips[] = trans('auth.password_tip_mixed_case');
	}
	if (config('settings.auth.password_numbers_required')) {
		$tips[] = trans('auth.password_tip_number');
	}
	if (config('settings.auth.password_symbols_required')) {
		$tips[] = trans('auth.password_tip_symbol');
	}
	
	return $tips;
}

/**
 * @return bool
 */
function registerFromSocialAuthWithPasswordEnabled(): bool
{
	return (config('settings.social_auth.generate_password') == '1');
}

/**
 * Check if phone number as auth field is enabled
 * i.e. If user can use their phone number to access to their account
 *
 * @return bool
 */
function isPhoneAsAuthFieldEnabled(): bool
{
	return (config('settings.sms.enable_phone_as_auth_field') == '1');
}

/**
 * @param bool $allowUserToChoose
 * @return bool
 */
function isUsersCanChooseNotifyChannel(bool $allowUserToChoose = false): bool
{
	$usersCanChooseNotifyChannel = isPhoneAsAuthFieldEnabled();
	if ($allowUserToChoose) {
		return $usersCanChooseNotifyChannel;
	}
	
	if (auth()->check()) {
		$usersCanChooseNotifyChannel = (
			$usersCanChooseNotifyChannel
			&& config('settings.sms.messenger_notifications') == '1'
		);
	}
	
	return $usersCanChooseNotifyChannel;
}

/**
 * @return bool
 */
function isBothAuthFieldsCanBeDisplayed(): bool
{
	$emailNeedToBeVerified = (config('settings.mail.email_verification') == '1');
	$phoneNeedToBeVerified = (config('settings.sms.phone_verification') == '1');
	
	$isBothAuthFieldNeedToBeVerified = ($emailNeedToBeVerified && $phoneNeedToBeVerified);
	$isBothAuthFieldsCanBeDisplayed = (bool)config('larapen.core.displayBothAuthFields');
	
	if ($isBothAuthFieldNeedToBeVerified) {
		return false;
	}
	
	return $isBothAuthFieldsCanBeDisplayed;
}

function getSocialLoginButtonType(): string
{
	$default = SocialLoginButton::Default->value;
	$buttonType = config('settings.social_auth.button_type', $default);
	
	return is_string($buttonType) ? $buttonType : $default;
}

/**
 * The maximum number of attempts to allow
 *
 * @return int
 */
function loginMaxAttempts(): int
{
	$default = 5;
	
	return (int)config('settings.auth.login_max_attempts', $default);
}

/**
 * The number of minutes to throttle for
 *
 * @return int
 */
function loginDecayMinutes(): int
{
	$default = 15;
	$decayMinutes = (int)config('settings.auth.login_decay_minutes', $default);
	$decayMinutes = Number::clamp($decayMinutes, min: 1, max: 999999);
	
	return (int)$decayMinutes;
}

/**
 * @return int
 */
function maxLoginLockoutAttempts(): int
{
	$default = 30;
	$maxAttempts = (int)config('settings.auth.max_login_lockout_attempts', $default);
	$maxAttempts = Num::clampMin($maxAttempts, min: 1);
	
	return (int)$maxAttempts;
}

/**
 * @return bool
 */
function isOtpEnabledForEmail(): bool
{
	return (config('settings.auth.otp_for_email') == '1');
}

/**
 * @param string|null $method
 * @return bool
 */
function isTwoFactorEnabled(?string $method = null): bool
{
	$methodMapping = [
		'email' => 'mail',
		'mail'  => 'mail',
		'sms'   => 'sms',
	];
	
	if (!empty($method) && array_key_exists($method, $methodMapping)) {
		$method = $methodMapping[$method];
		$isTwoFactorEnabled = config("settings.auth.2fa_with_$method") == '1';
	} else {
		$isTwoFactorEnabled = (
			config("settings.auth.2fa_with_mail") == '1' ||
			config("settings.auth.2fa_with_sms") == '1'
		);
	}
	
	return $isTwoFactorEnabled;
}

/**
 * Require two-factor challenge on enable
 *
 * i.e.
 * Require the user to complete the two-factor authentication challenge immediately upon enabling the two-factor setting.
 * Force the user to complete the two-factor authentication challenge immediately after enabling the two-factor authentication setting.
 *
 * @return bool
 */
function isTwoFactorChallengeRequiredOnEnable(): bool
{
	return (config('settings.auth.require_2fa_challenge_on_enable') == '1');
}

/**
 * @return int
 */
function defaultOtpLength(): int
{
	$otpLength = (int)config('settings.auth.otp_length', 6);
	$otpLength = Number::clamp($otpLength, min: 4, max: 8);
	
	return (int)$otpLength;
}

/**
 * Time in minutes before an OTP expires after being generated
 *
 * @return int
 */
function otpExpireTimeInSeconds(): int
{
	$min = 30; // 30 seconds
	$max = 86400; // 1 day (60 * 60 * 24 = 86400)
	$default = 300; // 5 mn (60 * 5 = 300)
	
	$otpExpireTime = (int)config('settings.auth.otp_expire_time_seconds', $default);
	$otpExpireTime = Number::clamp($otpExpireTime, min: $min, max: $max);
	
	return (int)$otpExpireTime;
}

/**
 * Cooldown period in seconds between consecutive OTP resend requests
 *
 * @return int
 */
function otpCooldownInSeconds(): int
{
	$min = 30; // 30 seconds
	$max = 86400; // 1 day (60 * 60 * 24 = 86400)
	$default = 300; // 15 mn (60 * 15 = 900)
	
	$otpExpireTime = (int)config('settings.auth.otp_cooldown_seconds', $default);
	$otpExpireTime = Number::clamp($otpExpireTime, min: $min, max: $max);
	
	return (int)$otpExpireTime;
}

/**
 * Maximum number of OTP resend attempts allowed within the decay period
 *
 * @return int
 */
function otpResendMaxAttempts(): int
{
	$default = 3;
	$otpMaxAttempts = (int)config('settings.auth.otp_max_attempts', $default);
	$otpMaxAttempts = Number::clamp($otpMaxAttempts, min: 1, max: 999999);
	
	return (int)$otpMaxAttempts;
}

/**
 * Time in minutes before resend attempts reset
 *
 * @return int
 */
function otpResendDecayInMinutes(): int
{
	$default = 15;
	$decayMinutes = (int)config('settings.auth.otp_decay_minutes', $default);
	$decayMinutes = Number::clamp($decayMinutes, min: 1, max: 999999);
	
	return (int)$decayMinutes;
}

/**
 * @return int
 */
function maxResendLockoutAttempts(): int
{
	$default = 3;
	$maxAttempts = (int)config('settings.auth.max_resend_lockout_attempts', $default);
	$maxAttempts = Num::clampMin($maxAttempts, min: 1);
	
	return (int)$maxAttempts;
}

/**
 * @return int
 */
function lockoutDurationInMinutes(): int
{
	$default = 1440; // 1 day
	$decayMinutes = (int)config('settings.auth.lockout_duration_minutes', $default);
	$decayMinutes = Num::clampMin($decayMinutes, 10);
	
	return (int)$decayMinutes;
}
