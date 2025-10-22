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

use App\Services\Auth\Traits\Custom\SaveSocialLoginData;
use App\Services\Auth\Traits\System\AuthenticatesUsers;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialService extends BaseService
{
	use AuthenticatesUsers, SaveSocialLoginData;
	
	/**
	 * Supported providers and their Service Key
	 * URI Path => Service Key
	 *
	 * @var array<string, string>
	 */
	private array $supportedProviders;
	
	/**
	 * @var array
	 */
	private array $messages;
	
	public function __construct()
	{
		parent::__construct();
		
		/*
		 * Supported providers
		 * For API, stateless authentication is not available for OAuth 1.0 APIs,
		 * So we need to remove it from the list when the requests come from API.
		 */
		$this->supportedProviders = socialLogin()->providers();
		$this->supportedProviders = collect($this->supportedProviders)
			->map(fn ($item) => ($item['serviceKey'] ?? null))
			->filter(fn ($item) => isFromApi() ? (!empty($item) && $item != 'twitter') : !empty($item))
			->toArray();
		
		// Messages
		$this->messages['serviceNotFound'] = trans('auth.social_login_service_not_found');
		$this->messages['serviceNotEnabled'] = trans('auth.social_login_service_not_enabled');
		$this->messages['serviceError'] = trans('auth.social_login_service_error');
		$this->messages['emailAddressNotFound'] = trans('auth.social_login_email_not_found');
		$this->messages['unknownError'] = trans('auth.social_login_unknown_error');
		$this->messages['userNotSavedError'] = trans('auth.social_login_user_not_saved_error');
	}
	
	/**
	 * Get target URL
	 *
	 * @param string $provider
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getProviderTargetUrl(string $provider): JsonResponse
	{
		// Get the Provider and verify that if it's supported
		$serviceKey = $this->supportedProviders[$provider] ?? null;
		if (empty($serviceKey)) {
			$message = sprintf($this->messages['serviceNotFound'], $provider);
			
			return apiResponse()->notFound($message);
		}
		
		// Check if the Provider is enabled
		if (!socialLogin()->isEnabled($provider)) {
			$message = sprintf($this->messages['serviceNotEnabled'], $provider);
			
			return apiResponse()->notFound($message);
		}
		
		// Redirect to the Provider's website
		try {
			$socialiteObj = Socialite::driver($serviceKey)->stateless();
			
			return $socialiteObj->redirect()->getTargetUrl();
		} catch (Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->messages['serviceError'];
			}
			
			return apiResponse()->error($message);
		}
	}
	
	/**
	 * Get user info
	 *
	 * @param string $provider
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function handleProviderCallback(string $provider, array $params = []): JsonResponse
	{
		// Get the Provider and verify that if it's supported
		$serviceKey = $this->supportedProviders[$provider] ?? null;
		if (empty($serviceKey)) {
			$message = sprintf($this->messages['serviceNotFound'], $provider);
			
			return apiResponse()->notFound($message);
		}
		
		// Handle input data
		$token = $params['accessToken'] ?? null;
		
		// Provider API call - Get the user from the provider service
		try {
			// $providerData = Socialite::driver($provider)->stateless()->user();
			$providerData = Socialite::driver($serviceKey)->stateless()->userFromToken($token);
			
			// Data not found
			if (!$providerData) {
				return apiResponse()->error($this->messages['unknownError']);
			}
			
			// Email not found
			if (!filter_var($providerData->getEmail(), FILTER_VALIDATE_EMAIL)) {
				$message = sprintf($this->messages['emailAddressNotFound'], str($provider)->headline());
				
				return apiResponse()->error($message);
			}
		} catch (Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->messages['serviceError'];
			}
			
			return apiResponse()->error($message);
		}
		
		// Debug!
		// dd($providerData);
		
		// Save the user & log-in him (By creating a new login API token)
		return $this->saveUser($provider, $providerData, $token);
	}
}
