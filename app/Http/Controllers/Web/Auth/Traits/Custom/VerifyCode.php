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

use App\Services\VerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

trait VerifyCode
{
	/**
	 * URL: Verify user's Email Address or Phone Number
	 *
	 * Note: If the token argument is filled, the entity will be verified automatically, if not, the token form will be shown
	 *
	 * @param string $entityMetadataKey
	 * @param string $field
	 * @param string|null $token
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function verifyOrShowOtpVerificationForm(
		string $entityMetadataKey,
		string $field,
		?string $token = null
	): View|RedirectResponse
	{
		// Show the token/code verification form when the token hasn't filled
		if (empty($token)) {
			return $this->showOtpVerificationForm($entityMetadataKey, $field);
		}
		
		// Verify the entity
		$queryParams = [
			'deviceName' => 'Website',
		];
		$data = getServiceData((new VerificationService())->verifyCode($entityMetadataKey, $field, $token, $queryParams));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Get the Entity Object (User or Post model's entry)
		$entityObject = data_get($data, 'result');
		
		// Check the request status
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			if (empty($entityObject)) {
				return $this->showOtpVerificationForm($entityMetadataKey, $field, $message);
			}
		}
		
		$nextUrl = url('/?from=verification');
		
		// Remove Notification Trigger
		if (session()->has('emailOrPhoneChanged')) {
			session()->forget('emailOrPhoneChanged');
		}
		clearResendVerificationData();
		
		// users
		if ($entityMetadataKey == 'users') {
			$user = $entityObject;
			
			$userId = data_get($user, 'id');
			$authToken = data_get($data, 'extra.authToken');
			
			if (!empty($userId)) {
				// Auto log-in the user
				if (auth()->loginUsingId($userId)) {
					if (!empty($authToken)) {
						session()->put('authToken', $authToken);
					}
					$nextUrl = urlGen()->accountOverview();
				} else {
					if (session()->has('userNextUrl')) {
						$nextUrl = session('userNextUrl');
					} else {
						$nextUrl = urlGen()->signIn();
					}
				}
			}
			
			// Remove Next URL session
			if (session()->has('userNextUrl')) {
				session()->forget('userNextUrl');
			}
		}
		
		// posts
		if ($entityMetadataKey == 'posts') {
			$post = $entityObject;
			
			// Get Listing creation next URL
			if (session()->has('itemNextUrl')) {
				$nextUrl = session('itemNextUrl');
				if (str_contains($nextUrl, 'create') && !session()->has('postId')) {
					$nextUrl = urlGen()->post($post);
				}
			} else {
				$nextUrl = urlGen()->post($post);
			}
			
			// Remove Next URL session
			if (session()->has('itemNextUrl')) {
				session()->forget('itemNextUrl');
			}
		}
		
		// password (Forgot Password)
		if ($entityMetadataKey == 'password') {
			$nextUrl = url()->previous();
			if (session()->has('passwordNextUrl')) {
				$nextUrl = session('passwordNextUrl');
				
				// Remove Next URL session
				session()->forget('passwordNextUrl');
			}
		}
		
		return redirect()->to($nextUrl);
	}
	
	/**
	 * URL: Verify user's Email Address or Phone Number by submitting a token
	 *
	 * @param string $entityMetadataKey
	 * @param string $field
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postOtpVerificationForm(string $entityMetadataKey, string $field, Request $request): RedirectResponse
	{
		// If the token field is not filled, back to the token form
		$rules = [
			'code' => ['required', 'string'],
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return redirect()->back()->withErrors($validator)->withInput();
		}
		
		$token = $request->input('code');
		
		// If the token is submitted,
		// then add it in the URL and redirect users to that URL
		$nextUrl = ($field == 'phone')
			? urlGen()->phoneVerification($entityMetadataKey, $token)
			: urlGen()->emailVerification($entityMetadataKey, $token);
		
		return redirect()->to($nextUrl);
	}
	
	/**
	 * @param string $entityMetadataKey
	 * @param string $field
	 * @param string|null $errorMessage
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	private function showOtpVerificationForm(string $entityMetadataKey, string $field, ?string $errorMessage = null): View|RedirectResponse
	{
		$fieldHiddenValue = getResendVerificationDataFromSession()['fieldHiddenValue'] ?? null;
		$fieldHiddenValue = session('authFieldValue', $fieldHiddenValue);
		
		// The auth field value is needed to display where the OTP is sent
		// Note: For security reason, the field value need to be hidden partially
		if (empty($fieldHiddenValue)) {
			$message = !empty($errorMessage) ? $errorMessage . ' ' : '';
			$message .= trans('auth.login_to_retrieve_verification_data');
			flash($message)->error();
			
			return redirect()->to(urlGen()->signIn());
		} else {
			$flashNotification = getFlashNotificationData();
			if (!empty($flashNotification)) {
				$fMessage = $flashNotification['message'] ?? null;
				$fMethod = $flashNotification['method'] ?? 'info';
				
				if (!empty($fMessage)) {
					flash($fMessage)->$fMethod();
				}
			} else {
				if (!empty($errorMessage)) {
					flash($errorMessage)->error();
				}
			}
		}
		
		$coverTitle = trans('auth.security_cover_title');
		$coverDescription = trans('auth.security_cover_description');
		
		return view(
			'auth.verify',
			compact('entityMetadataKey', 'field', 'coverTitle', 'coverDescription', 'fieldHiddenValue')
		);
	}
}
