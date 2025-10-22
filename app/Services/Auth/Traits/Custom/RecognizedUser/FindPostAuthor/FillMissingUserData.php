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

namespace App\Services\Auth\Traits\Custom\RecognizedUser\FindPostAuthor;

use App\Helpers\Common\Arr;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\JsonResponse;

trait FillMissingUserData
{
	/**
	 * Use the listing data to complete the user's missing auth data
	 * NOTE: $user need to null or verified user model instance
	 *
	 * @param \App\Models\User|null $user
	 * @param $post
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function fillMissingUserData(?User $user = null, $post = null): JsonResponse
	{
		$data = [
			'success' => false,
			'message' => null,
		];
		
		if (empty($user)) {
			$user = auth(getAuthGuard())->user();
		}
		
		if (
			empty($user)
			|| !Arr::keyExists('id', $user)
			|| !Arr::keyExists('email', $user)
			|| !Arr::keyExists('phone', $user)
		) {
			return apiResponse()->json($data);
		}
		
		$isVerifiedEmail = false;
		$isVerifiedPhone = false;
		
		if (!empty($post)) {
			$isVerifiedEmail = (
				config('settings.mail.email_verification') == '1'
				&& !empty($post->email)
				&& !empty($post->email_verified_at)
			);
			
			$isVerifiedPhone = (
				config('settings.sms.phone_verification') == '1'
				&& !empty($post->phone)
				&& !empty($post->phone_verified_at)
			);
			
			// From filled entity (listings : create|update)
			$email = $isVerifiedEmail ? $post->email : null;
			$phone = $isVerifiedPhone ? $post->phone : null;
		} else {
			// From input (When a user contact an author)
			$email = request()->input('email');
			$phone = request()->input('phone');
		}
		
		if (empty($email) && empty($phone)) {
			return apiResponse()->json($data);
		}
		
		// Don't make any DB query if filled data is the same as auth user data
		if ($user->email == $email && $user->phone == $phone) {
			return apiResponse()->json($data);
		}
		
		$message = null;
		
		// Complete missing email address
		if (empty($user->email) && !empty($email)) {
			$emailDoesntExist = User::query()
				->withoutGlobalScopes([VerifiedScope::class])
				->where('email', $email)
				->doesntExist();
			
			if ($emailDoesntExist) {
				$user->email = $email;
				$user->email_verified_at = $isVerifiedEmail ? now() : null;
				$message = t('email_completed');
			}
		}
		
		// Complete missing phone number
		if (empty($user->phone) && !empty($phone)) {
			$phoneDoesntExist = User::query()
				->withoutGlobalScopes([VerifiedScope::class])
				->where('phone', $phone)
				->doesntExist();
			
			if ($phoneDoesntExist) {
				$user->phone = $phone;
				$user->phone_verified_at = $isVerifiedPhone ? now() : null;
				$message = t('phone_completed');
			}
		}
		
		if ($user->isDirty()) {
			$user->save();
			
			$data['success'] = true;
			$data['message'] = $message;
		}
		
		return apiResponse()->json($data);
	}
}
