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

use App\Helpers\Services\Referrer;
use App\Http\Controllers\Web\Front\FrontController;
use App\Http\Requests\Front\UserRequest;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class RegisterController extends FrontController
{
	protected UserService $userService;
	
	// Where to redirect users after login / registration
	protected string $redirectTo;
	
	/**
	 * @param \App\Services\UserService $userService
	 */
	public function __construct(UserService $userService)
	{
		parent::__construct();
		
		$this->userService = $userService;
		
		$this->redirectTo = urlGen()->accountOverview();
	}
	
	/**
	 * Show the form the creation a new user account.
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showForm(): View
	{
		clearResendVerificationData();
		
		// References (For JC)
		$userTypes = (config('larapen.core.item.id') == '18776089') ? Referrer::getUserTypes() : [];
		
		$coverTitle = trans('auth.register_cover_title');
		$coverDescription = trans('auth.register_cover_description');
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('register');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return view('auth.register.index', compact('userTypes', 'coverTitle', 'coverDescription'));
	}
	
	/**
	 * Register a new user account.
	 *
	 * @param \App\Http\Requests\Front\UserRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm(UserRequest $request): RedirectResponse
	{
		// Create new user
		$data = getServiceData($this->userService->store($request));
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		
		// Notification Message
		if (data_get($data, 'success')) {
			session()->put('message', $message);
		} else {
			flash($message)->error();
			
			return redirect()->back()->withErrors(['error' => $message])->withInput();
		}
		
		// Get User Resource
		$user = data_get($data, 'result');
		$userId = data_get($user, 'id');
		
		// Get the next URL
		$nextUrl = urlGen()->signUpFinished();
		
		// Get user's verification data
		$vEmailData = data_get($data, 'extra.sendEmailVerification');
		$vPhoneData = data_get($data, 'extra.sendPhoneVerification');
		$isUnverifiedEmail = (bool)(data_get($vEmailData, 'extra.isUnverifiedField') ?? false);
		$isUnverifiedPhone = (bool)(data_get($vPhoneData, 'extra.isUnverifiedField') ?? false);
		
		if ($isUnverifiedEmail || $isUnverifiedPhone) {
			session()->put('userNextUrl', $nextUrl);
			
			if ($isUnverifiedEmail) {
				// Create Notification Trigger
				$resendEmailVerificationData = data_get($vEmailData, 'extra');
				session()->put('resendEmailVerificationData', collect($resendEmailVerificationData)->toJson());
			}
			
			if ($isUnverifiedPhone) {
				// Create Notification Trigger
				$resendPhoneVerificationData = data_get($vPhoneData, 'extra');
				session()->put('resendPhoneVerificationData', collect($resendPhoneVerificationData)->toJson());
				
				// Go to Phone Number verification
				$nextUrl = urlGen()->phoneVerification('users');
			}
		} else {
			$authToken = data_get($data, 'extra.authToken');
			
			// Auto log-in the user
			if (!empty($userId)) {
				if (auth()->loginUsingId($userId)) {
					if (!empty($authToken)) {
						session()->put('authToken', $authToken);
					}
					$nextUrl = urlGen()->accountOverview();
				}
			}
		}
		
		return redirect()->to($nextUrl);
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function finished(): View|RedirectResponse
	{
		if (!session()->has('message')) {
			return redirect()->to('/');
		}
		
		// Meta Tags
		MetaTag::set('title', session('message'));
		MetaTag::set('description', session('message'));
		
		return view('auth.register.finished');
	}
}
