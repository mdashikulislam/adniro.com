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
use App\Services\Auth\App\Notifications\ResetPasswordSendSms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

trait SendsPasswordResetSms
{
	/**
	 * Send a reset OTP code to the given user
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendOtpCodeToSms(Request $request): JsonResponse
	{
		// Form validation
		$request->validate(['phone' => ['required']]);
		
		$phone = $request->input('phone');
		$phoneCountry = $request->input('phone_country');
		
		// Check if the phone number exists
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('phone', $phone)->first();
		if (empty($user)) {
			$msg = trans('auth.provided_information_doesnt_match');
			
			return apiResponse()->error($msg);
		}
		
		// Create the OTP code in database
		$otp = generateOtp(defaultOtpLength());
		$hashedOtp = Hash::make($otp);
		
		// Associate the OTP to an entry in the database
		$passwordReset = PasswordReset::query()->where('phone', $phone)->first();
		if (empty($passwordReset)) {
			$passwordResetInfo = [
				'email'         => null,
				'phone'         => $phone,
				'phone_country' => $phoneCountry,
				'token'         => $hashedOtp,
				'created_at'    => date('Y-m-d H:i:s'),
			];
			$passwordReset = new PasswordReset($passwordResetInfo);
		} else {
			$passwordReset->token = $hashedOtp;
			$passwordReset->created_at = date('Y-m-d H:i:s');
		}
		$passwordReset->save();
		
		// Send the OTP code by SMS
		try {
			$passwordReset->notify(new ResetPasswordSendSms($user, $otp));
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
		
		$message = trans('auth.code_sent_by_sms');
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => null,
			'extra'   => [
				'codeSentTo'     => 'phone',
				'code'           => $otp,
				'authFieldValue' => $phone,
			],
		];
		
		return apiResponse()->json($data);
	}
}
