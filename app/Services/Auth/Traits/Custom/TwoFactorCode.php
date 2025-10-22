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

namespace App\Services\Auth\Traits\Custom;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\App\Notifications\TwoFactorSendCode;

trait TwoFactorCode
{
	/**
	 * @param \App\Models\User $user
	 * @param bool $twoFactorIsJustEnabled
	 * @param array $data
	 * @return array
	 */
	private function checkTwoFactorChallenge(User $user, bool $twoFactorIsJustEnabled = false, array $data = []): array
	{
		if (empty($data)) {
			$data = [
				'success' => true,
				'message' => null,
				'result'  => (new UserResource($user))->toArray(request()),
			];
		}
		
		// Check if 2FA is enabled globally
		if (!isTwoFactorEnabled()) {
			return $data;
		}
		
		// Check if 2FA is enabled by the user
		$isTwoFactorEnabled = (isset($user->two_factor_enabled) && $user->two_factor_enabled == 1);
		if ($isTwoFactorEnabled) {
			$message = $twoFactorIsJustEnabled
				? trans('auth.two_factor_was_just_enabled')
				: trans('auth.otp_sent');
			
			$data = $this->sendTwoFactorCode($user, $data, $message);
		}
		
		return $data;
	}
	
	/**
	 * @param \App\Models\User $user
	 * @param array $data
	 * @param string|null $message
	 * @return array
	 */
	public function sendTwoFactorCode(User $user, array $data, ?string $message = null): array
	{
		// Logout the user
		$guard = getAuthGuard();
		if (auth($guard)->check()) {
			auth($guard)->logout();
			if (doesRequestIsFromWebClient()) {
				logoutSession(withNotification: false);
			}
		}
		
		$methodField = (isset($user->two_factor_method) && $user->two_factor_method === 'sms') ? 'phone' : 'email';
		$methodValue = $user->{$methodField} ?? null;
		$methodValue = !empty($methodValue) ? addMaskToString($methodValue, keepRgt: 2, keepLft: 5) : '********';
		
		$extra = [
			'twoFactorSuccess'           => true,
			'twoFactorChallengeRequired' => true,
			'twoFactorMethodValue'       => $methodValue,
		];
		
		$data['success'] = false;
		$data['message'] = $message;
		$data['result'] = array_key_exists('result', $data) ? $data['result'] : (new UserResource($user))->toArray(request());
		
		// Send 2FA code
		$sendCodeData = $this->sendCode($user);
		
		if (data_get($sendCodeData, 'success') !== true) {
			$data['message'] = data_get($sendCodeData, 'message');
			$extra['twoFactorSuccess'] = false;
			$extra['sendCodeFailed'] = true;
			
			// Reset the Two-Factor OTP
			$user->resetTwoFactorCode();
		}
		
		$data['extra'] = $extra;
		
		return $data;
	}
	
	/**
	 * Send the 2FA code to the user via their chosen method
	 *
	 * @param \App\Models\User $user
	 * @param array $data
	 * @param bool $alreadyIncremented
	 * @return array
	 */
	public function sendCode(User $user, array $data = [], bool $alreadyIncremented = false): array
	{
		$data['success'] = true;
		$data['message'] = array_key_exists('message', $data) ? $data['message'] : null;
		$data['result'] = array_key_exists('result', $data) ? $data['result'] : (new UserResource($user))->toArray(request());
		
		if ($user->canRequestNewOtp()) {
			if (!$alreadyIncremented) {
				$user->incrementOtpAttempts();
			}
			
			// Generate an OTP code
			$code = generateOtp(defaultOtpLength());
			
			// Save the OTP and set an expiration time for it
			$user->generateTwoFactorCode($code);
			
			// Send the code
			try {
				$user->notify(new TwoFactorSendCode($user, $code));
			} catch (\Throwable $e) {
				$data['success'] = false;
				$data['message'] = $e->getMessage();
			}
		}
		
		return $data;
	}
}
