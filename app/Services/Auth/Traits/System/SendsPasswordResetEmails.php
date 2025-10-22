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
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Services\Auth\App\Notifications\ResetPasswordSendEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Throwable;

trait SendsPasswordResetEmails
{
	/**
	 * Send a reset token (or OTP code) to the given user
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendResetLinkToEmail(Request $request): JsonResponse
	{
		// Form validation
		$request->validate(['email' => ['required', 'email']]);
		
		$email = $request->input('email');
		
		// Check if the email address exists
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('email', $email)->first();
		if (empty($user)) {
			$message = trans('auth.provided_information_doesnt_match');
			
			return apiResponse()->error($message);
		}
		
		// Create the token (or OTP code)
		$token = isOtpEnabledForEmail() ? generateOtp(defaultOtpLength()) : generateToken();
		$hashedToken = Hash::make($token);
		
		// Associate the token to an entry in the database
		$passwordReset = PasswordReset::query()->where('email', $email)->first();
		if (empty($passwordReset)) {
			$passwordResetInfo = [
				'email'         => $email,
				'phone'         => null,
				'phone_country' => null,
				'token'         => $hashedToken,
				'created_at'    => date('Y-m-d H:i:s'),
			];
			$passwordReset = new PasswordReset($passwordResetInfo);
		} else {
			$passwordReset->token = $hashedToken;
			$passwordReset->created_at = date('Y-m-d H:i:s');
		}
		$passwordReset->save();
		
		// Send the token link (or OTP code) by Email
		try {
			$passwordReset->notify(new ResetPasswordSendEmail($user, $token));
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
		
		$message = trans('auth.code_sent_to_email');
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => null,
			'extra'   => [
				'codeSentTo'     => 'email',
				'code'           => $token,
				'authFieldValue' => $email,
				'isOtpEnabled'   => isOtpEnabledForEmail(),
			],
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Send a reset link to the given user (Laravel Version)
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendResetLinkToEmailSystem(Request $request): JsonResponse
	{
		$request->validate(['email' => ['required', 'email']]);
		
		$credentials = $request->only('email');
		$email = $request->input('email');
		
		// We will send the password reset link to this user. Once we have attempted
		// to send the link, we will examine the response then see the message we
		// need to show to the user. Finally, we'll send out a proper response.
		$status = Password::sendResetLink($credentials);
		
		$message = trans($status);
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => null,
			'extra'   => [
				'codeSentTo'     => 'email',
				'authFieldValue' => $email,
			],
		];
		
		return $status === Password::RESET_LINK_SENT
			? apiResponse()->json($data)
			: apiResponse()->error($message);
	}
}
