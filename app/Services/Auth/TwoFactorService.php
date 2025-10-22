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

namespace App\Services\Auth;

use App\Helpers\Common\Num;
use App\Http\Resources\UserResource;
use App\Models\Permission;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Services\Auth\App\Http\Requests\TwoFactorRequest;
use App\Services\Auth\Traits\Custom\CreateLoginToken;
use App\Services\Auth\Traits\Custom\TwoFactorCode;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TwoFactorService extends BaseService
{
	use TwoFactorCode, CreateLoginToken;
	
	/**
	 * Set up 2FA for an authenticated user
	 *
	 * @param $userId
	 * @param \App\Services\Auth\App\Http\Requests\TwoFactorRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function setup($userId, TwoFactorRequest $request): JsonResponse
	{
		$twoFactorDefaultMethod = 'email';
		
		$isInputTwoFactorEnabled = $request->filled('two_factor_enabled');
		$twoFactorMethod = $request->input('two_factor_method', $twoFactorDefaultMethod);
		$twoFactorMethod = in_array($twoFactorMethod, ['email', 'sms']) ? $twoFactorMethod : $twoFactorDefaultMethod;
		$phone = $request->input('phone');
		
		// Get user
		/** @var User $user */
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('id', $userId)->first();
		
		if (empty($user)) {
			return apiResponse()->unauthorized(trans('auth.invalid_session'));
		}
		
		/** @var User $authUser */
		$authUser = request()->user() ?? auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Check logged User
		// Get the User Personal Access Token Object
		$personalAccess = isFromApi() ? $authUser->tokens()->where('id', getApiAuthToken())->first() : null;
		if (!empty($personalAccess)) {
			if ($personalAccess->tokenable_id != $user->id) {
				return apiResponse()->unauthorized();
			}
		} else {
			if ($authUser->getAuthIdentifier() != $user->id) {
				return apiResponse()->unauthorized();
			}
		}
		
		// Store previous 2FA state
		$wasEnabled = $user->two_factor_enabled ?? false;
		
		// Update 2FA settings based on request
		if ($isInputTwoFactorEnabled) {
			$user->two_factor_enabled = true;
			$user->two_factor_method = $twoFactorMethod;
			if ($twoFactorMethod === 'sms' && empty($user->phone)) {
				$user->phone = $phone;
			}
		} else {
			$user->two_factor_enabled = false;
			$user->two_factor_method = $twoFactorMethod;
		}
		
		// Save
		$user->save();
		
		$message = $isInputTwoFactorEnabled
			? trans('auth.two_factor_has_been_enabled')
			: trans('auth.two_factor_has_been_disabled');
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		// If 2FA was newly enabled, log out and initiate 2FA verification
		if (isTwoFactorChallengeRequiredOnEnable()) {
			if ($isInputTwoFactorEnabled) {
				$data = $this->checkTwoFactorChallenge($user, !$wasEnabled, $data);
			}
		}
		
		return apiResponse()->updated($data);
	}
	
	/**
	 * Verify the submitted 2FA code and log the user in
	 *
	 * @param $userId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function verify($userId, Request $request): JsonResponse
	{
		$request->validate(['code' => ['required', 'numeric']]);
		
		$code = $request->input('code');
		
		/** @var User $user */
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('id', $userId)->first();
		
		if (empty($user)) {
			return apiResponse()->unauthorized(trans('auth.invalid_session'));
		}
		
		// Check if the OTP has expired
		if ($user->otp_expires_at < now()) {
			$user->resetTwoFactorCode();
			
			return apiResponse()->forbidden(trans('auth.expired_otp'));
		}
		
		// Verify the code (save like password)
		if (!Hash::check($code, $user->two_factor_otp)) {
			$message = trans('auth.invalid_otp');
			
			return apiResponse()->forbidden($message);
		}
		
		// Clear the used code
		$user->resetTwoFactorCode();
		
		// Get the auth field
		$authField = ($user->two_factor_method === 'sms') ? 'phone' : 'email';
		
		// Bypass the auth field verification step, since user has a valid OTP
		if ($user->can(Permission::getStaffPermissions())) {
			$user->email_verified_at = now();
			$user->phone_verified_at = now();
		} else {
			if ($authField == 'email') {
				$user->email_verified_at = now();
			}
			if ($authField == 'phone') {
				$user->phone_verified_at = now();
			}
		}
		if ($user->isDirty()) {
			$user->save();
		}
		
		// Create new auth token
		return $this->createNewToken($user, $authField, bypassTwoFactor: true);
	}
	
	/**
	 * Resend a new 2FA code to the user
	 *
	 * @param $userId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resend($userId): JsonResponse
	{
		$cooldownInSecond = otpCooldownInSeconds();
		$maxAttempts = otpResendMaxAttempts();
		
		// Get user
		/** @var User $user */
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('id', $userId)->first();
		
		if (empty($user)) {
			return apiResponse()->unauthorized(trans('auth.invalid_session'));
		}
		
		if ($user->isLocked()) {
			$message = trans('auth.account_locked_for_excessive_otp_resend_attempts');
			
			return apiResponse()->unauthorized($message);
		}
		
		// Check if the user can request a new OTP
		if (!$user->canRequestNewOtp()) {
			$remainingSeconds = $cooldownInSecond - ($user->last_otp_sent_at ? $user->last_otp_sent_at->diffInSeconds(now()) : 0);
			$humanReadableTime = Num::shortTime($remainingSeconds);
			
			$message = ($user->otp_resend_attempts >= $maxAttempts)
				? trans('auth.maximum_otp_resend_attempts_reached')
				: trans('auth.wait_before_request_new_otp', ['humanReadableTime' => $humanReadableTime]);
			
			return apiResponse()->forbidden($message);
		}
		
		// Send new OTP
		$user->incrementOtpAttempts();
		$sendCodeData = $this->sendCode($user, alreadyIncremented: true);
		
		if (data_get($sendCodeData, 'success') !== true) {
			$data = [
				'success' => false,
				'message' => data_get($sendCodeData, 'message'),
				'extra'   => ['sendCodeFailed' => true],
			];
			
			return apiResponse()->json($data);
		}
		
		$message = trans('auth.new_otp_sent');
		
		return apiResponse()->success($message);
	}
}
