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

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Web\Front\FrontController;
use App\Services\Auth\Traits\Custom\SaveSocialLoginData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialController extends FrontController
{
	use SaveSocialLoginData;
	
	// If not logged in redirect to
	protected mixed $loginUrl;
	
	// After you've logged in redirect to
	protected string $redirectTo;
	
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
		
		// Set default URLs
		$isFromLoginPage = str_contains(url()->previous(), urlGen()->signIn());
		$this->loginUrl = $isFromLoginPage ? urlGen()->signIn() : url()->previous();
		$this->redirectTo = $isFromLoginPage ? urlGen()->accountOverview() : url()->previous();
		
		// Supported providers
		$this->supportedProviders = socialLogin()->providers();
		$this->supportedProviders = collect($this->supportedProviders)
			->map(fn ($item) => ($item['serviceKey'] ?? null))
			->filter(fn ($item) => !empty($item))
			->toArray();
		
		// Messages
		$this->messages['serviceNotFound'] = 'The social network "%s" is not available.';
		$this->messages['serviceNotEnabled'] = 'The social network "%s" is not enabled.';
		$this->messages['serviceError'] = "Unknown error. The service does not work.";
		$this->messages['emailAddressNotFound'] = 'Email address not found. Your "%s" account cannot be linked to our website.';
		$this->messages['unknownError'] = 'Unknown error. Please try again in a few minutes.';
		$this->messages['userNotSavedError'] = 'Unknown error. User data not saved.';
	}
	
	/**
	 * Redirect the user to the Provider authentication page.
	 *
	 * @param string $provider
	 * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function redirectToProvider(string $provider): SymfonyRedirectResponse|RedirectResponse
	{
		// Get the Provider and verify that if it's supported
		$serviceKey = $this->supportedProviders[$provider] ?? null;
		
		if (empty($serviceKey)) {
			$message = sprintf($this->messages['serviceNotFound'], $provider);
			abort(404, $message);
		}
		
		// Check if the Provider is enabled
		if (!socialLogin()->isEnabled($provider)) {
			$message = sprintf($this->messages['serviceNotEnabled'], $provider);
			flash($message)->error();
			
			return redirect()->to(urlGen()->signIn(), 301);
		}
		
		// If previous page is not the Login page...
		if (!str_contains(url()->previous(), urlGen()->signIn())) {
			// Save the previous URL to retrieve it after success or failed login.
			session()->put('url.intended', url()->previous());
		}
		
		// Redirect to the provider's website
		try {
			
			return Socialite::driver($serviceKey)->redirect();
			
		} catch (Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->messages['serviceError'];
			}
			flash($message)->error();
			
			return redirect()->to($this->loginUrl);
		}
	}
	
	/**
	 * Obtain the user information from the Provider.
	 *
	 * @param string $provider
	 * @param array $params
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function handleProviderCallback(string $provider, array $params = []): RedirectResponse
	{
		// Get the Provider and verify that if it's supported
		$serviceKey = $this->supportedProviders[$provider] ?? null;
		
		if (empty($serviceKey)) {
			$message = sprintf($this->messages['serviceNotFound'], $provider);
			abort(404, $message);
		}
		
		// Handle input data
		$token = $params['accessToken'] ?? null;
		
		// Provider API call - Get the user from the provider service
		try {
			$providerData = Socialite::driver($serviceKey)->user();
			
			// Data aren't found
			if (!$providerData) {
				flash($this->messages['unknownError'])->error();
				
				return redirect()->to(urlGen()->signIn());
			}
			
			// Email isn't found
			if (!filter_var($providerData->getEmail(), FILTER_VALIDATE_EMAIL)) {
				$message = sprintf($this->messages['emailAddressNotFound'], str($provider)->headline());
				flash($message)->error();
				
				return redirect()->to(urlGen()->signIn());
			}
		} catch (Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->messages['serviceError'];
			}
			flash($message)->error();
			
			return redirect()->to(urlGen()->signIn());
		}
		
		// Debug!
		// dd($providerData);
		
		// Save the user & log-in him (By creating a new login API token)
		$data = getServiceData($this->saveUser($provider, $providerData, $token));
		
		// Login the user in the browser
		return $this->loginUserFromWeb($data);
	}
	
	/**
	 * Login the user from Web
	 *
	 * Note: Even user has been logged in via the saveUser() method
	 * by creating new login API token, we need to open a session for him in the browser.
	 *
	 * @param array $data
	 * @return \Illuminate\Http\RedirectResponse
	 */
	private function loginUserFromWeb(array $data): RedirectResponse
	{
		$message = data_get($data, 'message');
		$userIsSaved = data_get($data, 'success');
		
		if ($userIsSaved) {
			// Response for successful login
			$userId = data_get($data, 'result.id');
			$authToken = data_get($data, 'extra.authToken');
			
			// Auto log-in the user
			if (!empty($userId)) {
				if (auth()->loginUsingId($userId)) {
					if (!empty($authToken)) {
						session()->put('authToken', $authToken);
					}
					
					return redirect()->intended($this->redirectTo);
				}
			}
		}
		
		$message = $message ?? t('unknown_error');
		flash($message)->error();
		
		return redirect()->to(urlGen()->signIn());
	}
}
