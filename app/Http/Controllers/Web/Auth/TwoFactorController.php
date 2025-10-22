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

use App\Http\Controllers\Web\Auth\Traits\Custom\CreateLoginSession;
use App\Http\Controllers\Web\Front\FrontController;
use App\Services\Auth\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class TwoFactorController extends FrontController
{
	use CreateLoginSession;
	
	protected TwoFactorService $twoFactorService;
	
	/**
	 * @param \App\Services\Auth\TwoFactorService $twoFactorService
	 */
	public function __construct(TwoFactorService $twoFactorService)
	{
		parent::__construct();
		
		$this->twoFactorService = $twoFactorService;
	}
	
	/**
	 * Display the 2FA challenge form
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showForm(): View
	{
		$coverTitle = trans('auth.security_cover_title');
		$coverDescription = trans('auth.security_cover_description');
		
		$title = trans('auth.two_factor_title') . ' - ' . config('app.name');
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', $title);
		
		return view('auth.two-factor.challenge', compact('coverTitle', 'coverDescription'));
	}
	
	/**
	 * Verify the submitted 2FA code and log the user in
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm(Request $request): RedirectResponse
	{
		$request->validate([
			'code' => ['required', 'numeric'],
		]);
		
		$authUserId = auth()->user()?->getAuthIdentifier() ?? session('twoFactorUserId', '-1');
		
		// Update the user's settings
		$json = $this->twoFactorService->verify($authUserId, $request);
		$data = getServiceData($json);
		
		// Parsing the API response
		$message = data_get($data, 'message');
		$success = data_get($data, 'success');
		
		// Notification Message
		if ($success) {
			if (!empty($message)) {
				flash($message)->success();
			}
			
			// Remove temporary session key
			session()->forget('twoFactorUserId');
			
			// Mark 2FA as completed
			session()->put('twoFactorAuthenticated', true);
			
			// Create new session
			return $this->createNewSession($data);
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			if ($json->status() == Response::HTTP_UNAUTHORIZED) {
				return redirect()->to(urlGen()->signIn());
			}
			
			return redirect()->back()->withInput();
		}
	}
	
	/**
	 * Resend a new 2FA code to the user
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function resendCode(): RedirectResponse
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? session('twoFactorUserId', '-1');
		
		$json = $this->twoFactorService->resend($authUserId);
		$data = getServiceData($json);
		
		// Parsing the API response
		$message = data_get($data, 'message');
		$success = data_get($data, 'success');
		
		// Notification Message
		if ($success) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			if ($json->status() == Response::HTTP_UNAUTHORIZED) {
				return redirect()->to(urlGen()->signIn());
			}
			
			if (data_get($data, 'extra.sendCodeFailed') === true) {
				if (auth()->check()) {
					logoutSession(withNotification: false);
				}
				
				return redirect()->to(urlGen()->signIn());
			}
			
			return redirect()->back()->withInput();
		}
		
		return redirect()->back();
	}
}
