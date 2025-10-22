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

namespace App\Services\Auth\Traits\Custom\Verification;

use App\Helpers\Common\Arr;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Throwable;

trait CheckIfAuthFieldIsVerified
{
	use Metadata, VerificationExtraData;
	
	/**
	 * Check if a model object has an auth field to be verified
	 * If yes, check that field is verified,
	 * If it's not verified, output the verification code re-send message
	 *
	 * @param $authUser
	 * @param string|null $authField
	 * @return array
	 */
	protected function userHasVerifiedAuthField($authUser = null, ?string $authField = null): array
	{
		$defaultMessage = trans('auth.unauthorized_access');
		
		if (doesRequestIsFromWebClient()) {
			$flashNotification = getFlashNotificationData();
			$fMessage = $flashNotification['message'] ?? null;
			$fMethod = $flashNotification['method'] ?? 'info';
		}
		
		$data = [
			'success' => false,
			'message' => $fMessage ?? $defaultMessage,
		];
		
		if (empty($authUser)) {
			return $data;
		}
		
		// Auth field is not empty
		if (!empty($authField)) {
			// Get the login verification field's name
			$loginVerificationField = $authField . '_verified_at';
			
			// Check if the 'users' table is up-to-date
			// If the 'users' table's login verification fields are not available, don't check them. So keep user logged.
			$usersTableIsUpToDate = (in_array($authField, ['email', 'phone']))
				? Arr::keyExists($loginVerificationField, $authUser)
				: (Arr::keyExists('email_verified_at', $authUser) && Arr::keyExists('phone_verified_at', $authUser));
			if (!$usersTableIsUpToDate) {
				$data['success'] = true;
				$data['message'] = null;
				
				return $data;
			}
			
			// Check if the user has a verified login (email address or/and phone number)
			$userHasVerifiedAuthField = (in_array($authField, ['email', 'phone']))
				? !empty($authUser->{$loginVerificationField})
				: (!empty($authUser->email_verified_at) && !empty($authUser->phone_verified_at));
			
		} else {
			// Auth field is empty, so check if both email and phone are verified
			// ---
			// Check if the 'users' table is up-to-date
			// If the 'users' table's login verification field are not available, don't check them. So keep user logged.
			$usersTableIsUpToDate = (
				Arr::keyExists('email_verified_at', $authUser)
				&& Arr::keyExists('phone_verified_at', $authUser)
			);
			if (!$usersTableIsUpToDate) {
				$data['success'] = true;
				$data['message'] = null;
				
				return $data;
			}
			
			// Check if the user has a verified login (email address or/and phone number)
			$userHasVerifiedAuthField = (!empty($authUser->email_verified_at) && !empty($authUser->phone_verified_at));
		}
		
		// The user's login (email address and/or phone number) is/are verified
		if ($userHasVerifiedAuthField) {
			$data['success'] = true;
			$data['message'] = null;
			
			return $data;
		}
		
		// The user's login (email address and/or phone number) is/are NOT verified
		// Invalid user (Log out user) that does not have a non-verified login (email address or/and phone number)
		$this->invalidateTheAuthenticatedUser();
		
		/** @var User|null $user */
		$user = null;
		
		// phone
		if (empty($authUser->phone_verified_at)) {
			if (empty($authUser->phone_token)) {
				if (empty($user)) {
					$user = User::query()
						->withoutGlobalScopes([VerifiedScope::class])
						->where('id', $authUser->id)
						->first();
				}
				$user->phone_token = generateOtp(defaultOtpLength());
			}
			
			// Update data & extra
			$entityMetadata = $this->getEntityMetadata('users');
			if (!empty($entityMetadata)) {
				$data = $this->updateExtraDataForPhone($entityMetadata, $authUser, $data);
			}
		}
		
		// email
		if (empty($authUser->email_verified_at)) {
			if (empty($authUser->email_token)) {
				if (empty($user)) {
					$user = User::query()
						->withoutGlobalScopes([VerifiedScope::class])
						->where('id', $authUser->id)
						->first();
				}
				$user->email_token = generateToken(hashed: true);
			}
			
			// Update data & extra
			$entityMetadata = $this->getEntityMetadata('users');
			if (!empty($entityMetadata)) {
				$data = $this->updateExtraDataForEmail($entityMetadata, $authUser, $data);
			}
		}
		
		if (doesRequestIsFromWebClient()) {
			if (empty($authUser->phone_verified_at) || empty($authUser->email_verified_at)) {
				$resendFieldVerificationData = data_get($data, 'extra');
				
				if (!empty($resendFieldVerificationData)) {
					$field = data_get($resendFieldVerificationData, 'field');
					
					if ($field == 'phone') {
						session()->put('resendPhoneVerificationData', collect($resendFieldVerificationData)->toJson());
					} else {
						session()->put('resendEmailVerificationData', collect($resendFieldVerificationData)->toJson());
					}
				}
			} else {
				clearResendVerificationData();
			}
		}
		
		// Fill the fields tokens (If they are missed)
		$isLoginTokenUpdated = (
			!empty($user)
			&& ($user->isDirty('phone_token') || $user->isDirty('email_token'))
		);
		if ($isLoginTokenUpdated) {
			$user->save();
		}
		
		$data['extra']['flashMethod'] = $fMethod ?? null;
		
		return $data;
	}
	
	/**
	 * Invalidate the authenticated user
	 *
	 * @return void
	 */
	private function invalidateTheAuthenticatedUser(): void
	{
		if (!auth(getAuthGuard())->check()) {
			return;
		}
		
		if (isFromApi()) {
			
			try {
				/** @var User $authUser */
				$authUser = request()->user() ?? auth(getAuthGuard())->user();
				
				if (empty($authUser)) return;
				
				auth()->logout();
				
				// Revoke all tokens
				$authUser->tokens()->delete();
			} catch (Throwable $e) {
			}
			
		} else {
			
			logoutSession(withNotification: false);
			
		}
	}
}
