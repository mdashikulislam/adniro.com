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
use App\Services\Auth\Traits\Custom\Verification\CheckIfAuthFieldIsVerified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait CreateLoginToken
{
	use CheckIfAuthFieldIsVerified;
	
	/**
	 * Create a new login token
	 *
	 * @param \App\Models\User $user
	 * @param string $authField
	 * @param bool $needToBeRemembered
	 * @param string|null $message
	 * @param string|null $deviceName
	 * @param bool $bypassTwoFactor
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function createNewToken(
		User    $user,
		string  $authField,
		bool    $needToBeRemembered = false,
		?string $message = null,
		?string $deviceName = null,
		bool    $bypassTwoFactor = false
	): JsonResponse
	{
		$extra = [];
		
		// Two-Factor Authentication
		if (!$bypassTwoFactor) {
			$isTwoFactorEnabledForUser = (isset($user->two_factor_enabled) && $user->two_factor_enabled);
			$isTwoFactorEnabled = (isTwoFactorEnabled() && $isTwoFactorEnabledForUser);
			if ($isTwoFactorEnabled) {
				$twoFactorChallengeData = $this->checkTwoFactorChallenge($user);
				
				$success = data_get($twoFactorChallengeData, 'success');
				$twoFactorChallengeRequired = data_get($twoFactorChallengeData, 'extra.twoFactorChallengeRequired');
				$isTwoFactorChallengeRequired = ($success === false && $twoFactorChallengeRequired === true);
				
				// Cancel the two-factor challenge when the OTP cannot be sent
				$isTwoFactorSendCodeFailed = (data_get($twoFactorChallengeData, 'extra.sendCodeFailed') === true);
				if ($isTwoFactorSendCodeFailed) {
					$extra['sendCodeFailed'] = true;
					$isTwoFactorChallengeRequired = false;
				}
				
				if ($isTwoFactorChallengeRequired) {
					return apiResponse()->json($twoFactorChallengeData);
				}
			}
		}
		
		// Create new token (i.e. Log in the user)
		$errorMessage = trans('auth.failed_login');
		
		// Log in the user
		auth()->login($user, $needToBeRemembered);
		
		// Auth the User
		if (!auth()->check()) {
			return apiResponse()->error($errorMessage);
		}
		
		$authUser = auth()->user();
		
		// Is user has verified login?
		$vData = $this->userHasVerifiedAuthField($authUser, $authField);
		$isSuccess = array_key_exists('success', $vData) && $vData['success'];
		
		// Send the right error message (with possibility to re-send verification code)
		if (!$isSuccess) {
			if (
				array_key_exists('success', $vData)
				&& array_key_exists('message', $vData)
				&& array_key_exists('extra', $vData)
			) {
				return apiResponse()->json($vData, Response::HTTP_FORBIDDEN);
			}
			
			return apiResponse()->error($errorMessage);
		}
		
		// Reset lockout
		$user->resetLockout();
		
		// Redirect admin users to the Admin panel
		$isAdmin = doesUserHaveStaffPermission($user);
		$extra['isAdmin'] = $isAdmin;
		
		if (isFromApi()) {
			// Revoke previous tokens
			$user->tokens()->delete();
			
			// Create the API access token
			$defaultDeviceName = doesRequestIsFromWebClient() ? 'Website' : 'Other Client';
			$deviceName = $deviceName ?? $defaultDeviceName;
			$token = $user->createToken($deviceName);
			
			// Save extra data
			$extra['authToken'] = $token->plainTextToken;
			$extra['tokenType'] = 'Bearer';
		}
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => (new UserResource($user))->toArray(request()),
			'extra'   => $extra,
		];
		
		return apiResponse()->json($data);
	}
}
