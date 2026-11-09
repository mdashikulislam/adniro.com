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
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
	 * Signup form: live check of the username (valid & not taken).
	 * The same rules are applied again when the form is submitted.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function checkUsername(Request $request): JsonResponse
	{
		$username = UserRequest::normalizeUsername($request->query('username'));

		$validator = Validator::make(
			['username' => $username],
			['username' => UserRequest::signupUsernameRules()],
			UserRequest::signupUsernameMessages(),
			['username' => mb_strtolower(trans('auth.username'))]
		);

		$isAvailable = !$validator->fails();
		$isTaken = (!$isAvailable && array_key_exists('Unique', $validator->failed()['username'] ?? []));

		// Username taken: suggest a free one, built on it
		$suggestion = null;
		if ($isTaken) {
			$suggestion = User::generateUsername(substr($username, 0, UserRequest::USERNAME_MAX_LENGTH - 3));
		}

		return response()->json([
			'username'   => $username,
			'available'  => $isAvailable,
			'message'    => $isAvailable ? t('username_available') : $validator->errors()->first('username'),
			'suggestion' => $suggestion,
		]);
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
