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
 * User Profile & Settings + Account Management
 */

trait AccountUrlGen
{
	/**
	 * Path of user account overview/dashboard route
	 *
	 * @return string
	 */
	public function accountOverview(): string
	{
		$path = $this->getAccountBasePath() . '/overview';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user account update profile information route
	 *
	 * @return string
	 */
	public function accountProfile(): string
	{
		$path = $this->getAccountBasePath() . '/profile';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user account change password, 2FA, etc. route
	 *
	 * @return string
	 */
	public function accountSecurity(): string
	{
		$path = $this->getAccountBasePath() . '/security';
		
		return urlBuilder($path)->toString();
	}
	
	public function accountSecurityPassword(): string
	{
		return $this->accountSecurity() . '/password';
	}
	
	public function accountSecurityTwoFactor(): string
	{
		return $this->accountSecurity() . '/two-factor';
	}
	
	/**
	 * Path of user account notification & theme settings, etc. route
	 *
	 * @return string
	 */
	public function accountPreferences(): string
	{
		$path = $this->getAccountBasePath() . '/preferences';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user account social logins & integrations route
	 *
	 * @return string
	 */
	public function accountLinkedAccounts(): string
	{
		$path = $this->getAccountBasePath() . '/linked-accounts';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user account social logins disconnection route
	 *
	 * @param string $provider
	 * @return string
	 */
	public function accountDisconnectLinkedAccount(string $provider): string
	{
		return $this->accountLinkedAccounts() . "/$provider/disconnect";
	}
	
	/**
	 * Path of user payment methods & invoices route
	 *
	 * @return string
	 */
	public function accountBilling(): string
	{
		$path = $this->getAccountBasePath() . '/billing';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user subscription details route
	 *
	 * @return string
	 */
	public function accountSubscription(): string
	{
		$path = $this->getAccountBasePath() . '/subscription';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user manage notifications route
	 *
	 * @return string
	 */
	public function accountNotifications(): string
	{
		$path = $this->getAccountBasePath() . '/notifications';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user login history, recent actions route
	 *
	 * @return string
	 */
	public function accountActivity(): string
	{
		$path = $this->getAccountBasePath() . '/activity';
		
		return urlBuilder($path)->toString();
	}
	
	/**
	 * Path of user account closing route
	 *
	 * @return string
	 */
	public function accountClosing(): string
	{
		$path = $this->getAccountBasePath() . '/closing';
		
		return urlBuilder($path)->toString();
	}
	
	// PRIVATE
	
	public function getAccountBasePath(): string
	{
		$basePath = 'account';
		$basePath = config('larapen.core.basePath.account', $basePath);
		$basePath = rtrim($basePath, '/');
		
		return castToString($basePath);
	}
}
