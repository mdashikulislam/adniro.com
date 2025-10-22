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

use App\Helpers\Services\Referrer;
use App\Http\Controllers\Web\Front\Account\Traits\DashboardTrait;
use App\Http\Requests\Front\AvatarRequest;
use App\Http\Requests\Front\UserRequest;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ProfileController extends AccountBaseController
{
	use DashboardTrait;
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index(): View
	{
		$genders = Referrer::getGenders();
		
		$appName = config('settings.app.name', 'Site Name');
		$title = trans('auth.profile') . ' - ' . $appName;
		$description = t('my_account_on', ['appName' => config('settings.app.name')]);
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		
		// Breadcrumb
		BreadcrumbFacade::add(trans('auth.profile'));
		
		return view('front.account.profile', compact('genders'));
	}
	
	/**
	 * Update the user's details
	 *
	 * @param \App\Http\Requests\Front\UserRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function updateDetails(UserRequest $request): RedirectResponse
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Update the user's data
		$data = getServiceData($this->userService->update($authUserId, $request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back()->withInput($request->except(['photo_path']));
		}
		
		// Get User Resource
		$user = data_get($data, 'result');
		
		// Don't log out the User (See the User model's file)
		if (data_get($data, 'extra.emailOrPhoneChanged')) {
			session()->put('emailOrPhoneChanged', true);
		}
		
		// Get the next URL
		$nextUrl = urlGen()->accountProfile();
		
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
		}
		
		// Mail Notification Message
		if (data_get($data, 'extra.mail.message')) {
			$mailMessage = data_get($data, 'extra.mail.message');
			if (data_get($data, 'extra.mail.success')) {
				flash($mailMessage)->success();
			} else {
				flash($mailMessage)->error();
			}
		}
		
		return redirect()->to($nextUrl);
	}
	
	/**
	 * Update the user's photo
	 *
	 * @param \App\Http\Requests\Front\AvatarRequest $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function updatePhoto(AvatarRequest $request): JsonResponse|RedirectResponse
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Update the user's photo
		$data = getServiceData($this->userService->updatePhoto($authUserId, $request));
		
		// Parsing the API response
		return $this->handlePhotoData($data);
	}
	
	/**
	 * Delete the user's photo
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function deletePhoto(): JsonResponse|RedirectResponse
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Delete the user's photo
		$data = getServiceData($this->userService->removePhoto($authUserId));
		
		// Parsing the API response
		return $this->handlePhotoData($data);
	}
}
