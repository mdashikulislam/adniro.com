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

use App\Helpers\Common\JsonUtils;
use App\Http\Controllers\Web\Auth\Traits\Custom\CreateLoginSession;
use App\Http\Controllers\Web\Front\FrontController;
use App\Services\Auth\App\Http\Requests\LoginRequest;
use App\Services\Auth\LoginService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class LoginController extends FrontController
{
	use CreateLoginSession;
	
	protected LoginService $loginService;
	
	// If not logged-in redirect to
	protected string $loginUrl;
	
	// After you've logged-in redirect to
	protected string $redirectTo;
	
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
		$this->loginUrl = urlGen()->signIn();
		$this->redirectTo = $isUrlFromAdminArea ? urlGen()->adminUrl() : urlGen()->accountOverview();
	}
	
	/**
	 * Show the application login form.
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function showForm(): View|RedirectResponse
	{
		// Remembering Login
		if (auth()->viaRemember()) {
			return redirect()->intended($this->redirectTo);
		}
		
		// Check verification notification
		if (!session()->has('flash_messages')) {
			if (session()->has('resendVerificationData')) {
				$resendVerificationData = JsonUtils::jsonToArray(session('resendVerificationData'));
				
				$isErrorOccurred = data_get($resendVerificationData, 'isErrorOccurred');
				$field = data_get($resendVerificationData, 'field');
				$fieldHiddenValue = data_get($resendVerificationData, 'fieldHiddenValue');
				
				$message = ($field == 'phone')
					? trans('auth.verification_code_sent', ['fieldHiddenValue' => $fieldHiddenValue])
					: trans('auth.verification_link_sent', ['fieldHiddenValue' => $fieldHiddenValue]);
				
				if ($isErrorOccurred) {
					flash($message)->error();
				} else {
					flash($message)->info();
				}
			}
		}
		
		$coverTitle = trans('auth.login_cover_title');
		$coverDescription = trans('auth.login_cover_description');
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('login');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return view('auth.login.index', compact('coverTitle', 'coverDescription'));
	}
	
	/**
	 * @param \App\Services\Auth\App\Http\Requests\LoginRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm(LoginRequest $request): RedirectResponse
	{
		// Log-in the user
		$data = getServiceData($this->loginService->login($request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		$success = data_get($data, 'success');
		
		// Two-Factor Authentication Challenge Checking
		$user = data_get($data, 'result');
		$isTwoFactorEnabled = (isTwoFactorEnabled() && data_get($user, 'two_factor_enabled', false));
		if ($isTwoFactorEnabled) {
			$isTwoFactorSendCodeFailed = (data_get($data, 'extra.sendCodeFailed') === true);
			if ($isTwoFactorSendCodeFailed) {
				// Remove temporary session key
				session()->forget('twoFactorUserId');
				
				// Mark 2FA as completed
				session()->put('twoFactorAuthenticated', true);
			} else {
				// Get the 2FA data
				$userId = data_get($user, 'id');
				$twoFactorSuccess = data_get($data, 'extra.twoFactorSuccess');
				$twoFactorChallengeRequired = data_get($data, 'extra.twoFactorChallengeRequired');
				$isTwoFactorChallengeRequired = ($success === false && $twoFactorChallengeRequired === true);
				$twoFactorMethodValue = data_get($data, 'extra.twoFactorMethodValue');
				
				// Check the 2FA challenge
				if ($isTwoFactorChallengeRequired && !empty($userId) && !empty($twoFactorMethodValue)) {
					session()->put('twoFactorUserId', $userId);
					session()->put('twoFactorMethodValue', $twoFactorMethodValue);
					
					if ($twoFactorSuccess) {
						flash($message)->success();
					} else {
						flash($message)->error();
					}
					
					return redirect()->to(urlGen()->twoFactorChallenge());
				}
			}
		}
		
		if ($success) {
			return $this->createNewSession($data);
		}
		
		$message = $message ?? trans('auth.failed');
		
		return redirect()->to($this->loginUrl)->withErrors(['error' => $message])->withInput();
	}
}
