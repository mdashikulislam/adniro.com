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

use App\Helpers\Common\JsonUtils;
use App\Models\Permission;
use App\Models\Post;
use App\Models\User;
use App\Services\Auth\App\Helpers\SocialLogin;
use Illuminate\Support\Number;

/**
 * @return \App\Services\Auth\App\Helpers\SocialLogin
 */
function socialLogin(): SocialLogin
{
	return new SocialLogin();
}

// PERMISSION

/**
 * Does user can ?
 *
 * @param $authUser
 * @param array|string $permission
 * @param bool $forceToFallbackOnErrorOccurred
 * @return bool
 */
function doesUserHavePermission($authUser, array|string $permission, bool $forceToFallbackOnErrorOccurred = false): bool
{
	try {
		return (
			!empty($authUser)
			&& method_exists($authUser, 'can')
			&& $authUser->can($permission)
		);
	} catch (Throwable $e) {
		return $forceToFallbackOnErrorOccurred;
	}
}

/**
 * @param $authUser
 * @param bool $forceToFallbackOnErrorOccurred
 * @return bool
 */
function doesUserHaveSuperAdminPermission($authUser, bool $forceToFallbackOnErrorOccurred = false): bool
{
	$permissions = Permission::getSuperAdminPermissions();
	$hasPermissions = doesUserHavePermission($authUser, $permissions, $forceToFallbackOnErrorOccurred);
	if ($hasPermissions) {
		return true;
	}
	
	// For versions 18.0.1 (LC)/17.0.1 (JC) and earlier, check legacy permission system
	// This maintains backward compatibility for users who had admin access under the old system
	$legacyPermissions = Permission::getSuperAdminLegacyPermissions();
	
	return doesUserHavePermission($authUser, $legacyPermissions, $forceToFallbackOnErrorOccurred);
}

/**
 * @param $authUser
 * @param bool $forceToFallbackOnErrorOccurred
 * @return bool
 */
function doesUserHaveStaffPermission($authUser, bool $forceToFallbackOnErrorOccurred = false): bool
{
	$permissions = Permission::getStaffPermissions();
	$hasPermissions = doesUserHavePermission($authUser, $permissions, $forceToFallbackOnErrorOccurred);
	if ($hasPermissions) {
		return true;
	}
	
	// For versions 18.0.1 (LC)/17.0.1 (JC) and earlier, check legacy permission system
	// This maintains backward compatibility for users who had admin access under the old system
	$legacyPermissions = Permission::getStaffLegacyPermissions();
	
	return doesUserHavePermission($authUser, $legacyPermissions, $forceToFallbackOnErrorOccurred);
}

// AUTH

/**
 * Log out the user on a web client (Browser)
 *
 * @param string|null $message
 * @param bool $withNotification
 * @return string|null
 */
function logoutSession(?string $message = null, bool $withNotification = true): ?string
{
	if (isFromApi()) return null;
	
	$guard = getAuthGuard();
	
	if (!auth($guard)->check()) return $message;
	
	// Save some important session data (temporary)
	if (session()->has('countryCode')) {
		$countryCode = session('countryCode');
	}
	if (session()->has('allowMeFromReferrer')) {
		$allowMeFromReferrer = session('allowMeFromReferrer');
	}
	if (session()->has('browserLangCode')) {
		$browserLangCode = session('browserLangCode');
	}
	
	// Remove all session vars
	auth($guard)->logout();
	request()->session()->flush();
	request()->session()->regenerate();
	
	// 2FA
	if (session()->has('twoFactorAuthenticated')) {
		session()->forget('twoFactorAuthenticated');
	}
	
	// Retrieve the session data saved (temporary)
	if (!empty($countryCode)) {
		session()->put('countryCode', $countryCode);
	}
	if (!empty($allowMeFromReferrer)) {
		session()->put('allowMeFromReferrer', $allowMeFromReferrer);
	}
	if (!empty($browserLangCode)) {
		session()->put('browserLangCode', $browserLangCode);
	}
	
	if (!$withNotification) return null;
	
	// Unintentional disconnection
	if (empty($message)) {
		$message = t('unintentional_logout');
		notification($message, 'error');
		
		return $message;
	}
	
	// Intentional disconnection
	notification($message, 'success');
	
	return $message;
}

// AUTH FIELD

/**
 * List of auth fields | List of notification channels
 *
 * @param bool $asChannel
 * @return array
 */
function getAuthFields(bool $asChannel = false): array
{
	$authFields = [
		'email' => $asChannel ? trans('auth.channel_mail') : trans('auth.email_address'),
	];
	
	if (isPhoneAsAuthFieldEnabled()) {
		$authFields['phone'] = $asChannel ? trans('auth.channel_sms') : trans('auth.phone_number');
	}
	
	return $authFields;
}

/**
 * Get the auth field
 *
 * @param $entity
 * @return string
 */
function getAuthField($entity = null): string
{
	$authFields = array_keys(getAuthFields());
	$defaultAuthField = config('settings.sms.default_auth_field', 'email');
	
	// From default value
	$authField = $defaultAuthField;
	
	// From authenticated user's data
	$guard = getAuthGuard();
	if (auth($guard)->check()) {
		$savedValue = auth($guard)->user()->auth_field ?? $authField;
		$authField = (!empty($savedValue)) ? $savedValue : $authField;
	}
	
	// From a database table
	// '$entity' can be any table object that has 'auth_field' column
	if (!empty($entity)) {
		$savedValue = (is_array($entity))
			? ($entity['auth_field'] ?? $defaultAuthField)
			: ($entity->auth_field ?? $defaultAuthField);
		$authField = (!empty($savedValue)) ? $savedValue : $defaultAuthField;
	}
	
	// From form
	if (request()->filled('auth_field')) {
		$authField = request()->input('auth_field');
	}
	
	$authField = (in_array($authField, $authFields)) ? $authField : $defaultAuthField;
	
	return isPhoneAsAuthFieldEnabled() ? $authField : 'email';
}

/**
 * Get the auth field name from its value
 *
 * @param string|null $value
 * @return string
 */
function getAuthFieldFromItsValue(?string $value = null): string
{
	$field = 'username';
	if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
		$field = 'email';
	} else if (preg_match('/^((\+|00)\d{1,3})?[\s\d]+$/', $value)) {
		$field = 'phone';
	}
	
	return $field;
}

/**
 * Get the auth field from the OTP page
 *
 * @return string|null
 */
function getAuthFieldOnOtpPage(): ?string
{
	$authFields = array_keys(getAuthFields());
	
	// Get the right auth field
	$authField = null;
	if (request()->segment(2) == 'verify') {
		$field = request()->segment(4);
		if (!empty($field) && in_array($field, $authFields)) {
			$authField = $field;
		}
	}
	
	return $authField;
}

/**
 * @param $defaultCountryCode
 * @return string|null
 */
function getPhoneCountry($defaultCountryCode = null): ?string
{
	$countryCode = isFromApi() ? config('country.code') : session('countryCode');
	$countryCode = $defaultCountryCode ?? $countryCode;
	$countryCode = request()->input('country_code', $countryCode);
	$countryCode = request()->input('phone_country', $countryCode);
	
	return is_string($countryCode) ? $countryCode : null;
}

function getLoginDescription(): string
{
	$authFields = getAuthFields();
	
	$text = trans('auth.login_description');
	if (count($authFields) == 1 && array_key_exists('email', $authFields)) {
		$text = trans('auth.login_description_email');
	}
	if (count($authFields) == 1 && array_key_exists('phone', $authFields)) {
		$text = trans('auth.login_description_phone');
	}
	
	return is_string($text) ? $text : '';
}

function getPasswordForgotDescription(): string
{
	$authFields = getAuthFields();
	
	$text = trans('auth.forgotten_password_description');
	if (count($authFields) == 1 && array_key_exists('email', $authFields)) {
		$text = trans('auth.forgotten_password_description_email');
	}
	if (count($authFields) == 1 && array_key_exists('phone', $authFields)) {
		$text = trans('auth.forgotten_password_description_phone');
	}
	
	return is_string($text) ? $text : '';
}

function getResetPasswordDescription(): string
{
	$authFields = getAuthFields();
	
	$text = trans('auth.reset_password_description');
	if (count($authFields) == 1 && array_key_exists('email', $authFields)) {
		$text = trans('auth.reset_password_description_email');
	}
	if (count($authFields) == 1 && array_key_exists('phone', $authFields)) {
		$text = trans('auth.reset_password_description_phone');
	}
	
	return is_string($text) ? $text : '';
}

/**
 * @return void
 */
function clearResendVerificationData(): void
{
	if (session()->has('resendEmailVerificationData')) {
		session()->forget('resendEmailVerificationData');
	}
	if (session()->has('resendPhoneVerificationData')) {
		session()->forget('resendPhoneVerificationData');
	}
}

/**
 * @param \App\Models\User|\App\Models\Post $object
 * @param string $field
 * @return string|null
 */
function getModelVerificationMessage(User|Post $object, string $field): ?string
{
	if (!in_array($field, ['email', 'phone'])) {
		return null;
	}
	
	$fieldValue = $object->$field ?? '*********';
	$fieldHiddenValue = addMaskToString($fieldValue, keepRgt: 2, keepLft: 5);
	
	$message = ($field == 'phone')
		? trans('auth.verification_code_sent', ['fieldHiddenValue' => $fieldHiddenValue])
		: trans('auth.verification_link_sent', ['fieldHiddenValue' => $fieldHiddenValue]);
	
	return castToStringOrNull($message);
}

/**
 * @return array
 */
function getResendVerificationDataFromSession(): array
{
	$json = session('resendEmailVerificationData', session('resendPhoneVerificationData'));
	$data = JsonUtils::jsonToArray($json);
	if (empty($data)) return [];
	
	$resendUrl = data_get($data, 'resendUrl');
	$field = data_get($data, 'field');
	$fieldHiddenValue = data_get($data, 'fieldHiddenValue');
	
	$message = ($field == 'phone')
		? trans('auth.verification_code_sent', ['fieldHiddenValue' => $fieldHiddenValue])
		: trans('auth.verification_link_sent', ['fieldHiddenValue' => $fieldHiddenValue]);
	$notReceivedMessage = ($field == 'phone') ? trans('auth.not_received_code') : trans('auth.not_received_link');
	$resendLabel = ($field == 'phone') ? trans('auth.resend_code') : trans('auth.resend_link');
	
	$array = [];
	if (!empty($resendUrl)) {
		$array = [
			'message'            => $message,
			'notReceivedMessage' => $notReceivedMessage,
			'resendUrl'          => $resendUrl,
			'resendLabel'        => $resendLabel,
			'fieldHiddenValue'   => $fieldHiddenValue,
		];
	}
	
	return $array;
}

/**
 * @param bool $withMessage
 * @return string|null
 */
function getResendVerificationLink(bool $withMessage = false): ?string
{
	$data = getResendVerificationDataFromSession();
	
	$message = $data['message'] ?? null;
	$notReceivedMessage = $data['notReceivedMessage'] ?? null;
	$resendUrl = $data['resendUrl'] ?? null;
	$resendLabel = $data['resendLabel'] ?? null;
	
	$out = null;
	if (!empty($resendUrl)) {
		$out = $withMessage ? $message . '<br>' : '';
		$out .= $notReceivedMessage . ' ';
		$out .= '<a href="' . $resendUrl . '">';
		$out .= $resendLabel;
		$out .= '</a>';
	}
	
	return $out;
}

/**
 * @return string
 */
function getOtpFieldLabel(): string
{
	$authField = getAuthFieldOnOtpPage();
	
	$label = trans('auth.code_received_by');
	if ($authField == 'email') {
		$label = trans('auth.code_received_by_email');
	}
	if ($authField == 'phone') {
		$label = trans('auth.code_received_by_sms');
	}
	
	return is_string($label) ? $label : '';
}

/**
 * @param string|null $fieldHiddenValue
 * @return string
 */
function getOtpValidationDescription(?string $fieldHiddenValue = null): string
{
	/*
	$authField = getAuthFieldOnOtpPage();
	$text = trans('auth.enter_code_received_by');
	if ($authField == 'email') {
		$text = trans('auth.enter_code_received_by_email');
	}
	if ($authField == 'phone') {
		$text = trans('auth.enter_code_received_by_sms');
	}
	*/
	
	$fieldHiddenValue = $fieldHiddenValue ?? '*********';
	$text = trans('auth.otp_validation_description', ['fieldHiddenValue' => $fieldHiddenValue]);
	
	return is_string($text) ? $text : '';
}

/**
 * Generate Token for Email Verification
 *
 * @param int $length
 * @param bool $hashed
 * @return string
 */
function generateToken(int $length = 32, bool $hashed = false): string
{
	return $hashed
		? md5(microtime() . mt_rand())
		: generateOtp($length, 'alphanumeric');
}

/**
 * Generate a One-Time Password (OTP)
 *
 * Note: The OTP is also known as:
 * One-Time PIN
 * One-Time Passcode
 * One-Time Authorization Code (OTAC)
 * Dynamic password
 * Mot de passe à usage unique (in French)
 *
 * @param int $length
 * @param string $type
 * @return string
 */
function generateOtp(int $length = 6, string $type = 'numeric'): string
{
	// Validate length (Max 20 for OTP)
	$length = Number::clamp($length, min: 4, max: 32);
	
	// Generate random OTP of specified length
	return generateRandomString($length, $type);
}

// API

/**
 * Get the auth guard
 *
 * @return string|null
 */
function getAuthGuard(): ?string
{
	$guard = isFromApi() ? 'sanctum' : config('larapen.core.web.guard');
	
	return is_string($guard) ? $guard : null;
}

/**
 * @return string|null
 */
function getApiAuthToken(): ?string
{
	$token = null;
	
	if (request()->hasHeader('Authorization')) {
		$authorization = request()->header('Authorization');
		if (str_contains($authorization, 'Bearer')) {
			$token = str_replace('Bearer ', '', $authorization);
		}
	}
	
	return is_string($token) ? $token : null;
}
