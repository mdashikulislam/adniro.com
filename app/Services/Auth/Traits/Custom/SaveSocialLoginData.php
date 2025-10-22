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

use App\Helpers\Common\Ip;
use App\Http\Resources\UserResource;
use App\Models\Blacklist;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Models\UserSocialLogin;
use App\Services\Auth\App\Notifications\AccountCreatedWithPassword;
use App\Services\Auth\App\Notifications\NewUserRegistered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Throwable;

trait SaveSocialLoginData
{
	use RecognizedUser;
	
	/**
	 * @param string $provider
	 * @param SocialiteUser $providerData
	 * @param string|null $token
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function saveUser(string $provider, SocialiteUser $providerData, ?string $token = null): JsonResponse
	{
		// Get the Country Code
		$countryCode = config('country.code', config('ipCountry.code'));
		
		try {
			$remoteId = $providerData->getId();
			$name = $this->getName($providerData);
			$email = $providerData->getEmail();
			// $avatar = $providerData->getAvatar();
			
			// Check if the user's email address has been banned
			$bannedUser = Blacklist::query()->ofType('email')->where('entry', $email)->first();
			if (!empty($bannedUser)) {
				return apiResponse()->error(trans('auth.account_suspended_due_to_violation'));
			}
			
			// GET LOCAL USER
			$socialAccount = UserSocialLogin::query()
				->where('provider', $provider)
				->where('provider_id', $remoteId)
				->first();
			
			// CREATE LOCAL USER IF DON'T EXISTS
			if (!empty($socialAccount)) {
				
				$user = $socialAccount->user;
				$this->saveUserMissingData($user);
				
			} else {
				// Before... Check if user has not signed up with an email
				$user = User::query()
					->withoutGlobalScopes([VerifiedScope::class])
					->where('email', $email)
					->first();
				
				if (!empty($user)) {
					
					$this->saveUserMissingData($user);
					
				} else {
					// Generate random password
					$generatedPassword = registerFromSocialAuthWithPasswordEnabled() ? generateRandomPassword(8) : null;
					$hashedPassword = !empty($generatedPassword) ? Hash::make($generatedPassword) : null;
					
					// Register the User (As New User)
					$userInfo = [
						'country_code'      => $countryCode,
						'language_code'     => config('app.locale'),
						'name'              => $name,
						'auth_field'        => 'email',
						'email'             => $email,
						'password'          => $hashedPassword,
						'create_from_ip'    => Ip::get(),
						'email_verified_at' => now(),
						'phone_verified_at' => now(),
						'accept_terms'      => 1,
						'created_at'        => now()->format('Y-m-d H:i:s'),
					];
					$user = new User($userInfo);
					$user->save();
					
					// Match User's Posts (posted as Guest)
					$isEmailVerificationEnabled = config('settings.mail.email_verification');
					$isPhoneVerificationEnabled = config('settings.sms.phone_verification');
					config()->set('settings.mail.email_verification', '1');
					config()->set('settings.sms.phone_verification', '1');
					$this->matchAuthorPosts($user);
					config()->set('settings.mail.email_verification', $isEmailVerificationEnabled);
					config()->set('settings.sms.phone_verification', $isPhoneVerificationEnabled);
					
					// Send Generated Password by Email
					if (!empty($generatedPassword)) {
						try {
							$user->notify(new AccountCreatedWithPassword($user, $generatedPassword));
						} catch (Throwable $e) {
						}
					}
					
					// Update Listings created by this email
					if (isset($user->id) && $user->id > 0) {
						Post::query()
							->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
							->where('email', $userInfo['email'])
							->update(['user_id' => $user->id]);
					}
					
					// Send Admin Notification Email
					if (config('settings.mail.admin_notification') == 1) {
						try {
							// Get all admin users
							$admins = User::permission(Permission::getStaffPermissions())->get();
							if ($admins->count() > 0) {
								Notification::send($admins, new NewUserRegistered($user));
							}
						} catch (Throwable $e) {
						}
					}
				}
				
				// Save the user's social account (As linked account)
				$socialAccountInfo = [
					'user_id'     => $user->id,
					'provider'    => $provider,
					'provider_id' => $remoteId,
					'token'       => $token,
					'created_at'  => now()->format('Y-m-d H:i:s'),
				];
				$socialAccount = new UserSocialLogin($socialAccountInfo);
				$socialAccount->save();
			}
			
			return $this->loginUserFromApi($user, $provider);
		} catch (Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->messages['userNotSavedError'] ?? '';
			}
			
			return apiResponse()->error($message);
		}
	}
	
	/**
	 * @param \App\Models\User $user
	 * @param string|null $deviceName
	 * @return \Illuminate\Http\JsonResponse
	 */
	private function loginUserFromApi(User $user, ?string $deviceName = null): JsonResponse
	{
		if (isFromApi()) {
			// Revoke previous tokens
			$user->tokens()->delete();
		}
		
		if (auth()->loginUsingId($user->id)) {
			$extra = [];
			
			if (isFromApi()) {
				// Create the API access token
				$defaultDeviceName = doesRequestIsFromWebClient() ? 'Website' : 'Other Client';
				$deviceName = !empty($deviceName) ? ucfirst($deviceName) : $defaultDeviceName;
				$token = $user->createToken($deviceName);
				
				$extra['authToken'] = $token->plainTextToken;
				$extra['tokenType'] = 'Bearer';
			}
			
			// For JC
			// If the user has not yet specified the type of account, redirect him to his user area where he can do so.
			if (config('larapen.core.item.id') == '18776089') {
				$extra['userTypeId'] = $user->userType?->id ?? null;
			}
			
			$data = [
				'success' => true,
				'result'  => new UserResource($user),
				'extra'   => $extra,
			];
			
			return apiResponse()->json($data);
		} else {
			return apiResponse()->error(trans('auth.issue_to_login_in'));
		}
	}
	
	/**
	 * @param \App\Models\User $user
	 * @return void
	 */
	private function saveUserMissingData(User $user): void
	{
		$user->email_verified_at = now();
		$user->phone_verified_at = now();
		
		if (empty($user->accept_terms) || $user->accept_terms !== true) {
			$user->accept_terms = 1;
		}
		
		// Required (for time ago displaying)
		if (empty($user->created_at)) {
			$user->created_at = now()->format('Y-m-d H:i:s');
		}
		// Optional
		if (empty($user->updated_at)) {
			$user->updated_at = now()->format('Y-m-d H:i:s');
		}
		
		$user->save();
	}
	
	/**
	 * @param \Laravel\Socialite\Contracts\User $providerData
	 * @return string|null
	 */
	private function getName(SocialiteUser $providerData): ?string
	{
		$name = $providerData->getName();
		if ($name != '') {
			return $name;
		}
		
		// Get the user's name (First Name & Last Name)
		$name = (isset($providerData->name) && is_string($providerData->name)) ? $providerData->name : '';
		if ($name == '') {
			// facebook
			if (isset($providerData->user['first_name']) && isset($providerData->user['last_name'])) {
				$name = $providerData->user['first_name'] . ' ' . $providerData->user['last_name'];
			}
		}
		if ($name == '') {
			// linkedin
			$name = (isset($providerData->user['formattedName'])) ? $providerData->user['formattedName'] : '';
			if ($name == '') {
				if (isset($providerData->user['firstName']) && isset($providerData->user['lastName'])) {
					$name = $providerData->user['firstName'] . ' ' . $providerData->user['lastName'];
				}
			}
		}
		
		return is_string($name) ? $name : 'Unnamed User';
	}
}
