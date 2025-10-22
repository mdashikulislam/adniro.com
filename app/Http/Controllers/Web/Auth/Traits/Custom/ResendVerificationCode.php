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

namespace App\Http\Controllers\Web\Auth\Traits\Custom;

use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;

trait ResendVerificationCode
{
	/**
	 * URL: Resend the verification message
	 *
	 * @param string $entityMetadataKey
	 * @param int|string $entityId
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function resendEmailVerification(string $entityMetadataKey, int|string $entityId): RedirectResponse
	{
		// Resend the email verification link
		$data = getServiceData((new VerificationService())->resendEmailVerification($entityMetadataKey, $entityId));
		
		// Parsing the API response
		$isSuccess = data_get($data, 'success');
		$message = data_get($data, 'message');
		
		// Check if user field is not verified yet and if the link/code resend is not locked
		$isUnverifiedField = (bool)(data_get($data, 'extra.isUnverifiedField') ?? false);
		$isResendLocked = (bool)(data_get($data, 'extra.resendLocked') ?? false);
		$isUnverifiedFieldAndResendNotLocked = ($isUnverifiedField && !$isResendLocked);
		
		// Create Notification Trigger
		if ($isUnverifiedFieldAndResendNotLocked) {
			$resendEmailVerificationData = data_get($data, 'extra');
			session()->put('resendEmailVerificationData', collect($resendEmailVerificationData)->toJson());
		}
		
		// Notification Message
		if ($isSuccess) {
			notification($message, 'success');
		} else {
			$message = $message ?? t('unknown_error');
			notification($message, 'error');
		}
		
		// Remove Notification Trigger
		if (!$isUnverifiedFieldAndResendNotLocked) {
			if (session()->has('resendEmailVerificationData')) {
				session()->forget('resendEmailVerificationData');
			}
		}
		
		return redirect()->back();
	}
	
	/**
	 * URL: Resend the verification SMS
	 *
	 * @param string $entityMetadataKey
	 * @param int|string $entityId
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function resendPhoneVerification(string $entityMetadataKey, int|string $entityId): RedirectResponse
	{
		// Resend the verification code
		$data = getServiceData((new VerificationService())->resendPhoneVerification($entityMetadataKey, $entityId));
		
		// Parsing the API response
		$isSuccess = data_get($data, 'success');
		$message = data_get($data, 'message');
		
		// Check if user field is not verified yet and if the link/code resend is not locked
		$isUnverifiedField = (bool)(data_get($data, 'extra.isUnverifiedField') ?? false);
		$isResendLocked = (bool)(data_get($data, 'extra.resendLocked') ?? false);
		$isUnverifiedFieldAndResendNotLocked = ($isUnverifiedField && !$isResendLocked);
		
		// Create Notification Trigger
		if ($isUnverifiedFieldAndResendNotLocked) {
			$resendPhoneVerificationData = data_get($data, 'extra');
			session()->put('resendPhoneVerificationData', collect($resendPhoneVerificationData)->toJson());
		}
		
		// Notification Message
		if ($isSuccess) {
			notification($message, 'success');
		} else {
			$message = $message ?? t('unknown_error');
			notification($message, 'error');
		}
		
		// Remove Notification Trigger
		if (!$isUnverifiedFieldAndResendNotLocked) {
			if (session()->has('resendPhoneVerificationData')) {
				session()->forget('resendPhoneVerificationData');
			}
		}
		
		// Go to user's account after the phone number verification
		if ($entityMetadataKey == 'users') {
			session()->put('userNextUrl', urlGen()->accountOverview());
		}
		
		// Go to the code (received by SMS) verification page
		if (!isFromAdminPanel()) {
			$url = urlGen()->phoneVerification($entityMetadataKey);
			
			return redirect()->to($url);
		}
		
		return redirect()->back();
	}
}
