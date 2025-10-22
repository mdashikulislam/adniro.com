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

namespace App\Http\Controllers\Web\Front\Account;

use App\Services\Auth\App\Http\Requests\PasswordRequest;
use App\Services\Auth\App\Http\Requests\TwoFactorRequest;
use App\Services\Auth\PasswordService;
use App\Services\Auth\TwoFactorService;
use App\Services\UserService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class SecurityController extends AccountBaseController
{
	protected PasswordService $passwordService;
	protected TwoFactorService $twoFactorService;
	
	/**
	 * @param \App\Services\UserService $userService
	 * @param \App\Services\Auth\PasswordService $passwordService
	 * @param \App\Services\Auth\TwoFactorService $twoFactorService
	 */
	public function __construct(
		UserService      $userService,
		PasswordService  $passwordService,
		TwoFactorService $twoFactorService
	)
	{
		parent::__construct($userService);
		
		$this->passwordService = $passwordService;
		$this->twoFactorService = $twoFactorService;
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index(): View
	{
		$appName = config('settings.app.name', 'Site Name');
		$title = trans('auth.security') . ' - ' . $appName;
		$description = t('my_account_on', ['appName' => config('settings.app.name')]);
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		
		// Breadcrumb
		BreadcrumbFacade::add(trans('auth.security'));
		
		return view('front.account.security');
	}
	
	/**
	 * Update the user's password
	 *
	 * @param \App\Services\Auth\App\Http\Requests\PasswordRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function changePassword(PasswordRequest $request): RedirectResponse
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Update the user's settings
		$data = getServiceData($this->passwordService->change($authUserId, $request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back()->withInput();
		}
		
		return redirect()->to(urlGen()->accountSecurity());
	}
	
	/**
	 * Setup user's Two-Factor Authentication
	 *
	 * @param \App\Services\Auth\App\Http\Requests\TwoFactorRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function setupTwoFactor(TwoFactorRequest $request): RedirectResponse
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Update the user's settings
		$data = getServiceData($this->twoFactorService->setup($authUserId, $request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		$success = data_get($data, 'success');
		
		// Two-Factor Authentication Challenge Checking
		$user = data_get($data, 'result');
		$isTwoFactorEnabled = (isTwoFactorEnabled() && data_get($user, 'two_factor_enabled', false));
		if ($isTwoFactorEnabled) {
			if (isTwoFactorChallengeRequiredOnEnable()) {
				$isTwoFactorSendCodeFailed = (data_get($data, 'extra.sendCodeFailed') === true);
				if ($isTwoFactorSendCodeFailed) {
					// Remove temporary session key
					session()->forget('twoFactorUserId');
					
					// Mark 2FA as completed
					session()->put('twoFactorAuthenticated', true);
				} else {
					if (session()->has('twoFactorAuthenticated')) {
						session()->forget('twoFactorAuthenticated');
					}
					
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
			} else {
				// Remove temporary session key
				session()->forget('twoFactorUserId');
				
				// Mark 2FA as completed
				session()->put('twoFactorAuthenticated', true);
			}
		}
		
		// Notification Message
		if ($success) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back()->withInput();
		}
		
		return redirect()->to(urlGen()->accountSecurity());
	}
}
