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

namespace App\Http\Controllers\Web\Auth\Traits\Custom\UrlGen;

/*
 * Authentication
 */

trait AuthUrlGen
{
	/**
	 * Path of sign-in route
	 *
	 * @return string
	 */
	public function signIn(): string
	{
		$path = $this->getAuthBasePath() . '/login';
		
		return urlBuilder($path)->toString();
	}
	
	public function signInModal(): string
	{
		$isModalEnabled = (config('settings.auth.open_login_in_modal') == '1');
		
		if ($isModalEnabled) {
			$url = '#quickLogin" data-bs-toggle="modal';
		} else {
			$url = $this->signIn();
		}
		
		return $url;
	}
	
	/**
	 * Path of sign-in with social network route
	 *
	 * @param string $provider
	 * @return string
	 */
	public function socialSignInPath(string $provider): string
	{
		return $this->getAuthBasePath() . "/connect/$provider";
	}
	
	public function socialSignIn(string $provider): string
	{
		return urlBuilder($this->socialSignInPath($provider))->toString();
	}
	
	/**
	 * Path of callback sign-in with social network route
	 *
	 * @param string $provider
	 * @return string
	 */
	public function socialSignInCallbackPath(string $provider): string
	{
		return $this->socialSignInPath($provider) . "/callback";
	}
	
	public function socialSignInCallback(string $provider): string
	{
		return urlBuilder($this->socialSignInCallbackPath($provider))->toString();
	}
	
	/**
	 * Path of sign-up route
	 *
	 * @return string
	 */
	public function signUp(): string
	{
		$path = $this->getAuthBasePath() . '/register';
		
		return urlBuilder($path)->toString();
	}
	
	public function signUpFinished(): string
	{
		$signUpUrl = urlBuilder($this->signUp())->removeAllParameters();
		$signUpUrl = rtrim($signUpUrl, '/');
		
		return urlBuilder($signUpUrl . '/finished')->toString();
	}
	
	/**
	 * Path of sign-out route
	 *
	 * @return string
	 */
	public function signOut(): string
	{
		$path = $this->getAuthBasePath() . '/logout';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of password forgot route
	 *
	 * @return string
	 */
	public function passwordForgot(): string
	{
		$path = $this->getAuthBasePath() . "/password/forgot";
		$countryCode = config('country.code');
		
		return (config('plugins.domainmapping.installed'))
			? dmUrl($countryCode, $path)
			: urlBuilder($path)->toString();
	}
	
	/**
	 * Path of reset password route
	 *
	 * @param string|null $token
	 * @return string
	 */
	public function passwordReset(?string $token = null): string
	{
		$token = !empty($token) ? "/$token" : '';
		$path = $this->getAuthBasePath() . "/password/reset{$token}";
		$countryCode = config('country.code');
		
		return (config('plugins.domainmapping.installed'))
			? dmUrl($countryCode, $path)
			: urlBuilder($path)->toString();
	}
	
	/*
	 * Path of password reset token/code sending route
	 *
	 * @return string
	 /
	public function sendPasswordResetTokenPath(): string
	{
		return $this->getAuthBasePath() . '/password/token';
	}
	
	public function sendPasswordResetToken(): string
	{
		return urlBuilder($this->sendPasswordResetTokenPath())->toString();
	}*/
	
	/**
	 * @param string $entityMetadataKey
	 * @param string|null $entityId
	 * @return string
	 */
	public function resendEmailVerification(string $entityMetadataKey, ?string $entityId): string
	{
		$path = $this->getAuthBasePath() . "/verify/$entityMetadataKey/$entityId/resend/email";
		
		return urlBuilder($path)->toString();
	}
	
	public function emailVerification(string $entityMetadataKey, ?string $token = null, ?string $countryCode = null): string
	{
		$path = $this->getAuthBasePath() . "/verify/$entityMetadataKey/email/$token";
		
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		return (config('plugins.domainmapping.installed'))
			? dmUrl($countryCode, $path)
			: urlBuilder($path)->toString();
	}
	
	/**
	 * @param string $entityMetadataKey
	 * @param string|null $entityId
	 * @return string
	 */
	public function resendSmsVerification(string $entityMetadataKey, ?string $entityId): string
	{
		$path = $this->getApiBasePath()
			. $this->getAuthBasePath()
			. "/verify/$entityMetadataKey/$entityId/resend/sms";
		
		return urlBuilder($path)->toString();
	}
	
	public function phoneVerification(string $entityMetadataKey, ?string $token = null, ?string $countryCode = null): string
	{
		$path = $this->getApiBasePath()
			. $this->getAuthBasePath()
			. "/verify/$entityMetadataKey/phone/$token";
		
		if (empty($countryCode)) {
			$countryCode = config('country.code');
		}
		
		return (config('plugins.domainmapping.installed'))
			? dmUrl($countryCode, $path)
			: urlBuilder($path)->toString();
	}
	
	// 2FA
	
	/**
	 * @return string
	 */
	public function twoFactorChallenge(): string
	{
		$path = $this->getAuthBasePath() . '/two-factor/verify';
		
		return urlBuilder($path)->toString();
	}
	
	public function twoFactorResend(): string
	{
		$path = $this->getAuthBasePath() . '/two-factor/resend';
		
		return urlBuilder($path)->toString();
	}
	
	// PRIVATE
	
	public function getAuthBasePath(): string
	{
		$basePath = 'auth';
		$basePath = config('larapen.core.basePath.auth', $basePath);
		$basePath = trim($basePath, '/');
		
		return castToString($basePath);
	}
	
	public function getApiBasePath(): string
	{
		return (isFromApi() && !doesRequestIsFromWebClient()) ? 'api/' : '';
	}
}
