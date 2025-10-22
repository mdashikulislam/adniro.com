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

use App\Helpers\Common\Cookie;
use App\Http\Controllers\Web\Front\FrontController;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;

class LogoutController extends FrontController
{
	protected LoginService $loginService;
	
	// After you've logged-out redirect to
	protected string $redirectAfterLogout;
	
	/**
	 * @param \App\Services\Auth\LoginService $loginService
	 */
	public function __construct(LoginService $loginService)
	{
		parent::__construct();
		
		$this->loginService = $loginService;
		
		// Check if the previous URL is from the admin panel area
		$isUrlFromAdminArea = str_contains(url()->previous(), urlGen()->adminUrl());
		
		// Update the Laravel login redirections URLs
		$this->redirectAfterLogout = $isUrlFromAdminArea ? urlGen()->signIn() : '/';
	}
	
	/**
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function logout(): RedirectResponse
	{
		$userId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Log-out the user
		$data = getServiceData($this->loginService->logout($userId));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		if (data_get($data, 'success')) {
			// Log out the user on a web client (Browser)
			logoutSession($message);
			
			// Reset Dark Mode
			Cookie::forget('darkTheme');
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
		}
		
		$uriPath = property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/';
		
		return redirect()->to($uriPath);
	}
}
