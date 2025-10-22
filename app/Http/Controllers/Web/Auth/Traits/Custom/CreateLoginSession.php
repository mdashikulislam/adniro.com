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

namespace App\Http\Controllers\Web\Auth\Traits\Custom;

use Illuminate\Http\RedirectResponse;

trait CreateLoginSession
{
	/**
	 * Create a new login session
	 *
	 * @param array $data
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function createNewSession(array $data): RedirectResponse
	{
		// Parsing the API response
		$message = data_get($data, 'message');
		$userId = data_get($data, 'result.id');
		$authToken = data_get($data, 'extra.authToken');
		
		$message = $message ?? trans('auth.failed');
		
		if (empty($userId)) {
			flash($message)->error();
			
			return redirect()->to(urlGen()->signIn())->withInput();
		}
		
		// Log-in the user
		if (!auth()->loginUsingId($userId)) {
			flash($message)->error();
			
			return redirect()->to(urlGen()->signIn())->withInput();
		}
		
		if (!empty($authToken)) {
			session()->put('authToken', $authToken);
		}
		
		// Get the intended URL
		$intendedUrl = castToStringOrNull(session('url.intended'));
		
		// Check if the user is an admin user
		$isAdminUser = data_get($data, 'extra.isAdmin');
		$isUrlFromAdminArea = (!empty($intendedUrl) && str_contains($intendedUrl, '/' . urlGen()->adminUri()));
		
		// Since non-admin users are automatically log-in from the admin panel URLs,
		// redirect the non-admin users to their account URL at: /account
		if ($isUrlFromAdminArea && !$isAdminUser) {
			return redirect()->to(urlGen()->accountOverview());
		}
		
		$redirectTo = $isAdminUser ? urlGen()->adminUri() : ($this->redirectTo ?? urlGen()->accountOverview());
		
		// Retrieve the previously intended location/URL to redirect user on it after successful log-in
		// If no intended location found, the $redirectTo URL will be used to redirect the user
		return redirect()->intended($redirectTo);
	}
}
