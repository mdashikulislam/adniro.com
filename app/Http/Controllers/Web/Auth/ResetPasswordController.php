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
use App\Http\Controllers\Web\Auth\Traits\System\RedirectsUsers;
use App\Http\Controllers\Web\Front\FrontController;
use App\Services\Auth\App\Http\Requests\ResetPasswordRequest;
use App\Services\Auth\ResetPasswordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ResetPasswordController extends FrontController
{
	use RedirectsUsers;
	use CreateLoginSession;
	
	protected ResetPasswordService $resetPasswordService;
	
	// Where to redirect users after resetting their password
	protected string $redirectTo;
	
	/**
	 * @param \App\Services\Auth\ResetPasswordService $resetPasswordService
	 */
	public function __construct(ResetPasswordService $resetPasswordService)
	{
		parent::__construct();
		
		$this->resetPasswordService = $resetPasswordService;
		
		$this->redirectTo = urlGen()->accountOverview();
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
	 * Display the password reset view for the given token.
	 *
	 * If no token is present, display the link request form.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param string|null $token
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function showForm(Request $request, ?string $token = null): View|RedirectResponse
	{
		$email = $request->input('email');
		$phone = $request->input('phone');
		
		$coverTitle = trans('auth.security_cover_title');
		$coverDescription = trans('auth.security_cover_description');
		
		// Meta Tags
		MetaTag::set('title', trans('auth.reset_password'));
		MetaTag::set('description', trans('auth.reset_password'));
		
		// The $token is not provided, fill it manually then submit the form
		// From now, the future $token need to be verified before sending to this method argument
		if (empty($token)) {
			$nextUrl = !empty($phone)
				? urlGen()->phoneVerification('password')
				: urlGen()->emailVerification('password');
			
			return redirect()->to($nextUrl);
		}
		
		// Show the form to choose a new password
		return view('auth.password.reset', compact('coverTitle', 'coverDescription'))->with([
			'token' => $token,
			'email' => $email,
			'phone' => $phone,
		]);
	}
	
	/**
	 * Reset the given user's password.
	 *
	 * @param \App\Services\Auth\App\Http\Requests\ResetPasswordRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm(ResetPasswordRequest $request): RedirectResponse
	{
		// If the password was successfully reset,
		// we will redirect the user back to the application's home authenticated view.
		// If there is an error, we can redirect them back to where they came from with their error message.
		
		// Reset the user's password
		$data = getServiceData($this->resetPasswordService->reset($request));
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		$success = data_get($data, 'success');
		
		// Two-Factor Authentication Challenge Checking
		$user = data_get($data, 'result');
		$isTwoFactorEnabled = (isTwoFactorEnabled() && data_get($user, 'two_factor_enabled', false));
		if ($isTwoFactorEnabled) {
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
		
		if ($success) {
			return $this->createNewSession($data);
		}
		
		flash($message)->error();
		
		return redirect()->back()->withInput($request->only('email'));
	}
}
