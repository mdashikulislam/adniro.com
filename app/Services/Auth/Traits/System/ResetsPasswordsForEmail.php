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
use Illuminate\Auth\Events\PasswordReset as LaravelPasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

trait ResetsPasswordsForEmail
{
	/**
	 * Reset the given user's password
	 *
	 * @param \App\Services\Auth\App\Http\Requests\ResetPasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resetForEmail(ResetPasswordRequest $request): JsonResponse
	{
		$token = $request->input('token');
		$email = $request->input('email');
		$password = $request->input('password');
		$deviceName = $request->input('device_name');
		
		// Check if a password request exists for the given email
		$passwordReset = PasswordReset::query()->where('email', $email)->first();
		if (empty($passwordReset)) {
			$message = trans('auth.failed_to_find_email');
			
			return apiResponse()->error($message);
		}
		
		// Verify the token (save like password)
		if (!Hash::check($token, $passwordReset->token)) {
			$message = trans('auth.code_doesnt_match');
			
			return apiResponse()->forbidden($message);
		}
		
		// Get User
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('email', $passwordReset->email)->first();
		if (empty($user)) {
			$message = trans('auth.provided_information_doesnt_match');
			
			return apiResponse()->unauthorized($message);
		}
		
		// Update the User
		$user->password = Hash::make($password);
		
		$user->email_verified_at = now();
		if ($user->can(Permission::getStaffPermissions())) {
			// Phone auto-verified (for Admin Users)
			$user->phone_verified_at = now();
		}
		
		$user->save();
		
		// Remove password reset data
		$passwordReset->delete();
		
		$message = trans('auth.password_reset');
		
		// Auto-Auth the User (API)
		// By creating an API token for the User
		return $this->createNewToken($user, 'email', false, $message, $deviceName);
	}
	
	/**
	 * Reset the given user's password (Laravel Version)
	 *
	 * @param \App\Services\Auth\App\Http\Requests\ResetPasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resetForEmailSystem(ResetPasswordRequest $request): JsonResponse
	{
		$credentials = $request->only('email', 'password', 'password_confirmation', 'token');
		$email = $request->input('email');
		$password = $request->input('password');
		$deviceName = $request->input('device_name');
		
		// Here we will attempt to reset the user's password. If it is successful we
		// will update the password on an actual user model and persist it to the
		// database. Otherwise, we will parse the error and return the response.
		$status = Password::reset(
			$credentials,
			function ($user, $password) use ($request) {
				$user->password = Hash::make($password);
				
				$user->setRememberToken(Str::random(60));
				
				$user->email_verified_at = now();
				if ($user->can(Permission::getStaffPermissions())) {
					// Phone auto-verified (for Admin Users)
					$user->phone_verified_at = now();
				}
				
				$user->save();
				
				event(new LaravelPasswordReset($user));
			}
		);
		
		if ($status == Password::PASSWORD_RESET) {
			$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('email', $email)->first();
			
			if (!empty($user)) {
				if (Hash::check($password, $user->password)) {
					// Auto-Auth the User (API)
					// By creating an API token for the User
					return $this->createNewToken($user, 'email', false, trans($status), $deviceName);
				}
			}
			
			return apiResponse()->success(trans($status));
		} else {
			return apiResponse()->error(trans($status));
		}
	}
}
