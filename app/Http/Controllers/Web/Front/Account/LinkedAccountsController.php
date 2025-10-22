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

use App\Services\UserService;
use App\Services\UserSocialLoginService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Http\RedirectResponse;

class LinkedAccountsController extends AccountBaseController
{
	protected UserSocialLoginService $socialLoginService;
	
	/**
	 * @param \App\Services\UserService $userService
	 * @param \App\Services\UserSocialLoginService $socialLoginService
	 */
	public function __construct(UserService $userService, UserSocialLoginService $socialLoginService)
	{
		parent::__construct($userService);
		
		$this->socialLoginService = $socialLoginService;
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		$providers = socialLogin()->providersForDisconnection(strict: true);
		
		// Breadcrumb
		BreadcrumbFacade::add(trans('auth.linked_accounts'));
		
		return view('front.account.linked-accounts', compact('providers'));
	}
	
	/**
	 * @param string $provider
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function disconnect(string $provider): RedirectResponse
	{
		// Get Posts
		$queryParams = ['embed' => 'user'];
		$data = getServiceData($this->socialLoginService->getEntry($provider, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		$socialLogin = $apiResult;
		if (empty($socialLogin)) {
			$message = $apiMessage ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back();
		}
		
		$authUserId = auth()->user()?->getAuthIdentifier() ?? '-1';
		$userId = data_get($socialLogin, 'user_id');
		
		if ($authUserId != $userId) {
			$message = trans('auth.unauthorized_access_for_action');
			flash($message)->error();
			
			return redirect()->back();
		}
		
		$socialLoginId = data_get($socialLogin, 'id');
		
		// Delete the entry
		$data = getServiceData($this->socialLoginService->destroy($socialLoginId));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
		}
		
		return redirect()->back();
	}
}
