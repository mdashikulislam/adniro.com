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

use App\Events\UserWasLogged;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Services\Auth\App\Http\Requests\LoginRequest;
use App\Services\Auth\Traits\Custom\CreateLoginToken;
use App\Services\Auth\Traits\Custom\TwoFactorCode;
use App\Services\Auth\Traits\System\AuthenticatesUsers;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Throwable;

class LoginService extends BaseService
{
	use AuthenticatesUsers;
	use TwoFactorCode, CreateLoginToken;
	
	protected int $maxAttempts;
	protected int $decayMinutes;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->maxAttempts = loginMaxAttempts();
		$this->decayMinutes = loginDecayMinutes();
	}
	
	/**
	 * Log in
	 *
	 * @param \App\Services\Auth\App\Http\Requests\LoginRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function login(LoginRequest $request): JsonResponse
	{
		// Get the right auth field (email or phone)
		$authField = getAuthField();
		$authFieldDbColumn = $authField;
		
		// Handle Input
		// ---
		$authFieldValue = $request->input($authField);
		$password = $request->input('password');
		$needToBeRemembered = $request->has('remember_me');
		$deviceName = $request->input('device_name');
		
		// Check if username is provided instead of email (in email field)
		if ($authField == 'email') {
			$authFieldDbColumn = getAuthFieldFromItsValue($authFieldValue);
		}
		
		// Handle Login
		// ---
		$errorMessage = trans('auth.failed_login');
		
		try {
			if ($this->maxAttempts > 0) {
				// If the class is using the ThrottlesLogins trait, we can automatically throttle
				// the login attempts for this application. We'll key this by the username and
				// the IP address of the client making these requests into this application.
				// IMPORTANT: The RateLimiter class in Laravel relies on the cache system.
				// Ensure that caching is enabled and properly configured to utilize this feature.
				if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
					$this->fireLockoutEvent($request);
					$this->sendLockoutResponse($request); // EXIT!
				}
			}
			
			// Find user by email/phone/username
			/** @var User $user */
			$user = User::query()
				->withoutGlobalScopes([VerifiedScope::class])
				->where($authFieldDbColumn, $authFieldValue)
				->first();
			
			if (empty($user)) {
				$message = trans('auth.failed_to_find_login_field');
				
				return apiResponse()->unauthorized($message);
			}
			
			// Check if user exists and is locked
			if ($user->isLocked()) {
				$message = trans('auth.account_locked_for_excessive_login_attempts');
				
				return apiResponse()->unauthorized($message);
			}
			
			// Check if user exists and is suspended
			if (!empty($user->suspended_at)) {
				$message = trans('auth.account_suspended_due_to');
				
				return apiResponse()->unauthorized($message);
			}
			
			// Ensure that user password is set
			// Empty password means that user account was created using social login
			if (empty($user->password)) {
				$message = socialLogin()->isEnabled()
					? trans('auth.account_created_via_social_login')
					: $errorMessage;
				
				return apiResponse()->unauthorized($message);
			}
			
			// Verify password
			if (!Hash::check($password, $user->password)) {
				// Increment login attempts and check for lockout
				$user->incrementLoginAttempts();
				
				return apiResponse()->unauthorized($errorMessage);
			}
			
			// Clear login attempts
			$this->clearLoginAttempts($request);
			
			// Create new auth token
			return $this->createNewToken($user, $authField, $needToBeRemembered);
		} catch (Throwable $e) {
			$errorMessage = $e->getMessage();
		}
		
		if ($this->maxAttempts > 0) {
			// If the login attempt was unsuccessful we will increment the number of attempts
			// to log in and redirect the user back to the login form. Of course, when this
			// user surpasses their maximum number of attempts they will get locked out.
			// IMPORTANT: The RateLimiter class in Laravel relies on the cache system.
			// Ensure that caching is enabled and properly configured to utilize this feature.
			$this->incrementLoginAttempts($request);
		}
		
		return apiResponse()->error($errorMessage);
	}
	
	/**
	 * Log out
	 *
	 * @param $userId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function logout($userId): JsonResponse
	{
		/** @var User $authUser */
		$authUser = request()->user() ?? auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->error(trans('auth.logout_failed'));
		}
		
		if ($authUser->getAuthIdentifier() != $userId) {
			return apiResponse()->unauthorized();
		}
		
		if (isFromApi()) {
			// Get the User Personal Access Token Object
			$personalAccess = $authUser->tokens()->where('id', getApiAuthToken())->first();
			if (!empty($personalAccess)) {
				if ($personalAccess->tokenable_id == $userId) {
					// Revoke the specific token
					$personalAccess->delete();
				}
			}
		}
		
		// Update last user logged date
		$user = User::query()
			->withoutGlobalScopes([VerifiedScope::class])
			->where('id', $userId)
			->first();
		if (!empty($user)) {
			UserWasLogged::dispatch($user);
		}
		
		return apiResponse()->success(trans('auth.logout_successful'));
	}
}
