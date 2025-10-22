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

namespace App\Services\Auth\Traits\System;

use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Services\Auth\App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

trait ResetsPasswordsForPhone
{
	/**
	 * Reset the given user's password
	 *
	 * @param \App\Services\Auth\App\Http\Requests\ResetPasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resetForPhone(ResetPasswordRequest $request): JsonResponse
	{
		$token = $request->input('token');
		$phone = $request->input('phone');
		$password = $request->input('password');
		$deviceName = $request->input('device_name');
		
		// Check if a password request exists for the given phone number
		$passwordReset = PasswordReset::query()->where('phone', $phone)->first();
		if (empty($passwordReset)) {
			$message = trans('auth.failed_to_find_phone');
			
			return apiResponse()->error($message);
		}
		
		// Verify the token (save like password)
		if (!Hash::check($token, $passwordReset->token)) {
			$message = trans('auth.code_doesnt_match');
			
			return apiResponse()->forbidden($message);
		}
		
		// Get User
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('phone', $passwordReset->phone)->first();
		if (empty($user)) {
			$message = trans('auth.provided_information_doesnt_match');
			
			return apiResponse()->unauthorized($message);
		}
		
		// Update the User
		$user->password = Hash::make($password);
		
		$user->phone_verified_at = now();
		if ($user->can(Permission::getStaffPermissions())) {
			// Email address auto-verified (for Admin Users)
			$user->email_verified_at = now();
		}
		
		$user->save();
		
		// Remove password reset data
		$passwordReset->delete();
		
		$message = trans('auth.password_reset');
		
		// Auto-Auth the User (API)
		// By creating an API token for the User
		return $this->createNewToken($user, 'phone', false, $message, $deviceName);
	}
}
