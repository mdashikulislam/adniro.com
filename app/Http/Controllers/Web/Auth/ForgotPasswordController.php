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
use App\Services\Auth\App\Http\Requests\ForgotPasswordRequest;
use App\Services\Auth\ForgotPasswordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ForgotPasswordController extends FrontController
{
	protected ForgotPasswordService $forgotPasswordService;
	
	/**
	 * @param \App\Services\Auth\ForgotPasswordService $forgotPasswordService
	 */
	public function __construct(ForgotPasswordService $forgotPasswordService)
	{
		parent::__construct();
		
		$this->forgotPasswordService = $forgotPasswordService;
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = ['guest'];
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * Display the form to request a password reset link.
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showForm(): View
	{
		$coverTitle = trans('auth.password_forgot_cover_title');
		$coverDescription = trans('auth.password_forgot_cover_description');
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('password');
		MetaTag::set('title', $title ?? trans('auth.forgotten_password'));
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return view('auth.password.forgot', compact('coverTitle', 'coverDescription'));
	}
	
	/**
	 * Send a reset link or OTP code to the given user.
	 *
	 * @param ForgotPasswordRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm(ForgotPasswordRequest $request): RedirectResponse
	{
		// Send Reset Password Link
		$data = getServiceData($this->forgotPasswordService->sendResetLinkOrCode($request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		$success = data_get($data, 'success');
		
		// Error Found
		if (!$success) {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back()->withInput($request->only('email'));
		}
		
		// phone
		if (data_get($data, 'extra.codeSentTo') == 'phone') {
			// Save the password reset link (in session)
			$code = data_get($data, 'extra.code');
			$phone = data_get($data, 'extra.authFieldValue');
			$resetPasswordUrl = urlGen()->passwordReset($code);
			session()->put('passwordNextUrl', $resetPasswordUrl);
			session()->put('authFieldValue', $phone);
			
			// Phone Number verification
			// Get the OTP code verification form page URL
			// The user is supposed to have received this token|code by SMS
			$nextUrl = urlGen()->phoneVerification('password');
			
			// Go to the verification page
			return redirect()->to($nextUrl);
		}
		
		// email
		if (data_get($data, 'extra.isOtpEnabled') === true) {
			// Save the password reset link (in session)
			$code = data_get($data, 'extra.code');
			$email = data_get($data, 'extra.authFieldValue');
			$resetPasswordUrl = urlGen()->passwordReset($code);
			session()->put('passwordNextUrl', $resetPasswordUrl);
			session()->put('authFieldValue', $email);
			
			// Email verification
			// Get the OTP code verification form page URL
			// The user is supposed to have received this token|code by SMS
			$nextUrl = urlGen()->emailVerification('password');
			
			// Go to the verification page
			return redirect()->to($nextUrl);
		}
		
		flash($message)->success();
		
		return redirect()->back();
	}
}
