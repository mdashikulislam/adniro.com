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

namespace App\Services\Auth\App\Helpers;

use App\Models\User;
use App\Services\Auth\App\Helpers\SocialLogin\SocialLoginButton;

class SocialLogin
{
	/**
	 * @param bool $strict
	 * @return array
	 */
	public function providersForConnection(bool $strict = false): array
	{
		$array = $this->providers($strict);
		
		return collect($array)
			->map(function ($item, $key) {
				$connection = $item['connection'];
				$connection['name'] = $item['name'] ?? ucfirst($key);
				$connection['isEnabled'] = $item['isEnabled'];
				
				return $connection;
			})->toArray();
	}
	
	/**
	 * @param bool $strict
	 * @return array
	 */
	public function providersForDisconnection(bool $strict = false): array
	{
		/** @var User|null $authUser */
		$authUser = auth(getAuthGuard())->user();
		$array = $this->providers($strict);
		
		$array = collect($array)
			->map(function ($item, $key) use ($authUser) {
				$socialLogin = !empty($authUser)
					? $authUser->socialLogins()->where('provider', '=', $key)->first()
					: null;
				
				$disconnection = $item['disconnection'];
				$disconnection['name'] = $item['name'] ?? ucfirst($key);
				$disconnection['isEnabled'] = $item['isEnabled'];
				$disconnection['isConnected'] = !empty($socialLogin);
				$disconnection['connectedAt'] = !empty($socialLogin) ? $socialLogin->created_at : '--';
				
				return $disconnection;
			})->toArray();
		
		if ($strict) {
			$array = collect($array)->filter(fn ($item) => ($item['isConnected'] ?? false))->toArray();
		}
		
		return $array;
	}
	
	/**
	 * @param bool $strict
	 * @return array
	 */
	public function providers(bool $strict = false): array
	{
		$buttonType = getSocialLoginButtonType();
		
		$array = [
			'facebook' => [
				'name'          => 'Facebook',
				'serviceKey'    => 'facebook',
				'isEnabled'     => $this->isEnabled('facebook'),
				'connection'    => [
					'btnClass'  => 'btn-facebook',
					'iconClass' => 'fa-brands fa-facebook',
					'url'       => urlGen()->socialSignIn('facebook'),
					'callback'  => urlGen()->socialSignInCallback('facebook'),
					'label'     => match ($buttonType) {
						SocialLoginButton::LoginWithDefault->value => trans('auth.login_with', ['provider' => 'Facebook']),
						SocialLoginButton::LogoOnly->value => '',
						default => 'Facebook',
					},
				],
				'disconnection' => [
					'btnClass'  => 'btn-facebook',
					'iconClass' => 'fa-brands fa-facebook',
					'url'       => urlGen()->accountDisconnectLinkedAccount('facebook'),
					'label'     => trans('auth.connected_with', ['provider' => 'Facebook']),
				],
			],
			
			'linkedin' => [
				'name'          => 'LinkedIn',
				'serviceKey'    => 'linkedin-openid',
				'isEnabled'     => $this->isEnabled('linkedin'),
				'connection'    => [
					'btnClass'  => 'btn-linkedin',
					'iconClass' => 'fa-brands fa-linkedin',
					'url'       => urlGen()->socialSignIn('linkedin'),
					'callback'  => urlGen()->socialSignInCallback('linkedin'),
					'label'     => match ($buttonType) {
						SocialLoginButton::LoginWithDefault->value => trans('auth.login_with', ['provider' => 'LinkedIn']),
						SocialLoginButton::LogoOnly->value => '',
						default => 'LinkedIn',
					},
				],
				'disconnection' => [
					'btnClass'  => 'btn-linkedin',
					'iconClass' => 'fa-brands fa-linkedin',
					'url'       => urlGen()->accountDisconnectLinkedAccount('linkedin'),
					'label'     => trans('auth.connected_with', ['provider' => 'LinkedIn']),
				],
			],
			
			'twitter-oauth-2' => [
				'name'          => 'X (Twitter)',
				'serviceKey'    => 'twitter-oauth-2',
				'isEnabled'     => $this->isEnabled('twitter-oauth-2'),
				'connection'    => [
					'btnClass'  => 'btn-x-twitter',
					'iconClass' => 'fa-brands fa-x-twitter',
					'url'       => urlGen()->socialSignIn('twitter-oauth-2'),
					'callback'  => urlGen()->socialSignInCallback('twitter-oauth-2'),
					'label'     => match ($buttonType) {
						SocialLoginButton::LoginWithDefault->value => trans('auth.login_with', ['provider' => 'X (Twitter)']),
						SocialLoginButton::LogoOnly->value => '',
						default => 'X (Twitter)',
					},
				],
				'disconnection' => [
					'btnClass'  => 'btn-x-twitter',
					'iconClass' => 'fa-brands fa-x-twitter',
					'url'       => urlGen()->accountDisconnectLinkedAccount('twitter-oauth-2'),
					'label'     => trans('auth.connected_with', ['provider' => 'X (Twitter)']),
				],
			],
			
			'twitter' => [
				'name'          => 'X (Twitter)',
				'serviceKey'    => 'twitter',
				'isEnabled'     => $this->isEnabled('twitter'),
				'connection'    => [
					'btnClass'  => 'btn-x-twitter',
					'iconClass' => 'fa-brands fa-x-twitter',
					'url'       => urlGen()->socialSignIn('twitter'),
					'callback'  => urlGen()->socialSignInCallback('twitter'),
					'label'     => match ($buttonType) {
						SocialLoginButton::LoginWithDefault->value => trans('auth.login_with', ['provider' => 'X (Twitter)']),
						SocialLoginButton::LogoOnly->value => '',
						default => 'X (Twitter)',
					},
				],
				'disconnection' => [
					'btnClass'  => 'btn-x-twitter',
					'iconClass' => 'fa-brands fa-x-twitter',
					'url'       => urlGen()->accountDisconnectLinkedAccount('twitter'),
					'label'     => trans('auth.connected_with', ['provider' => 'X (Twitter)']),
				],
			],
			
			'google' => [
				'name'          => 'Google',
				'serviceKey'    => 'google',
				'isEnabled'     => $this->isEnabled('google'),
				'connection'    => [
					'btnClass'  => 'btn-google',
					'iconClass' => 'fa-brands fa-google',
					'url'       => urlGen()->socialSignIn('google'),
					'callback'  => urlGen()->socialSignInCallback('google'),
					'label'     => match ($buttonType) {
						SocialLoginButton::LoginWithDefault->value => trans('auth.login_with', ['provider' => 'Google']),
						SocialLoginButton::LogoOnly->value => '',
						default => 'Google',
					},
				],
				'disconnection' => [
					'btnClass'  => 'btn-google',
					'iconClass' => 'fa-brands fa-google',
					'url'       => urlGen()->accountDisconnectLinkedAccount('google'),
					'label'     => trans('auth.connected_with', ['provider' => 'Google']),
				],
			],
		];
		
		if ($strict) {
			$array = collect($array)->filter(fn ($item) => ($item['isEnabled'] ?? false))->toArray();
		}
		
		return $array;
	}
	
	/**
	 * @param string $provider
	 * @param bool $strict
	 * @return array
	 */
	public function provider(string $provider, bool $strict = false): array
	{
		return $this->providers($strict)[$provider] ?? [];
	}
	
	/**
	 * @param string|null $provider
	 * @param array $settings
	 * @return bool
	 */
	public function isEnabled(?string $provider = null, array $settings = []): bool
	{
		if (empty($settings)) {
			$settings = config('settings.social_auth');
			if (!is_array($settings)) return false;
		}
		
		$isFacebookOauthEnabled = (
			data_get($settings, 'facebook_enabled')
			&& data_get($settings, 'facebook_client_id')
			&& data_get($settings, 'facebook_client_secret')
		);
		
		$isLinkedInOauthEnabled = (
			data_get($settings, 'linkedin_enabled')
			&& data_get($settings, 'linkedin_client_id')
			&& data_get($settings, 'linkedin_client_secret')
		);
		
		$isTwitterOauth2Enabled = (
			data_get($settings, 'twitter_oauth_2_enabled')
			&& data_get($settings, 'twitter_oauth_2_client_id')
			&& data_get($settings, 'twitter_oauth_2_client_secret')
		);
		
		$isTwitterOauth1Enabled = (
			data_get($settings, 'twitter_oauth_1_enabled')
			&& data_get($settings, 'twitter_client_id')
			&& data_get($settings, 'twitter_client_secret')
		);
		
		// Twitter API versions selection
		$isTwitterOauth1Enabled = !($isTwitterOauth2Enabled && $isTwitterOauth1Enabled) && $isTwitterOauth1Enabled;
		
		$isGoogleOauthEnabled = (
			data_get($settings, 'google_enabled')
			&& data_get($settings, 'google_client_id')
			&& data_get($settings, 'google_client_secret')
		);
		
		$isSocialAuthEnabled = (
			data_get($settings, 'social_auth_enabled')
			&& (
				$isFacebookOauthEnabled
				|| $isLinkedInOauthEnabled
				|| $isTwitterOauth2Enabled
				|| $isTwitterOauth1Enabled
				|| $isGoogleOauthEnabled
			)
		);
		
		$providerList = [
			'facebook'        => $isFacebookOauthEnabled,
			'linkedin'        => $isLinkedInOauthEnabled,
			'twitter-oauth-2' => $isTwitterOauth2Enabled,
			'twitter'         => $isTwitterOauth1Enabled,
			'google'          => $isGoogleOauthEnabled,
		];
		
		if (!empty($provider)) {
			return (array_key_exists($provider, $providerList) && $providerList[$provider]);
		}
		
		return $isSocialAuthEnabled;
	}
}
