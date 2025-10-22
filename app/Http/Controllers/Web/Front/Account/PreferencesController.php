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

use App\Http\Requests\Front\UserPreferencesRequest;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class PreferencesController extends AccountBaseController
{
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index(): View
	{
		$appName = config('settings.app.name', 'Site Name');
		$title = trans('auth.preferences') . ' - ' . $appName;
		$description = t('my_account_on', ['appName' => config('settings.app.name')]);
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		
		// Breadcrumb
		BreadcrumbFacade::add(trans('auth.preferences'));
		
		return view('front.account.preferences');
	}
	
	/**
	 * Update the user's preferences
	 *
	 * @param \App\Http\Requests\Front\UserPreferencesRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function updatePreferences(UserPreferencesRequest $request): RedirectResponse
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Update the user's settings
		$data = getServiceData($this->userService->updatePreferences($authUserId, $request));
		
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
		
		return redirect()->to(urlGen()->accountPreferences());
	}
	
	/**
	 * Save the theme preference for the logged-in user
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function saveThemePreference(Request $request): JsonResponse
	{
		$themePreference = $request->integer('theme');
		$userId = $request->input('user_id');
		
		$status = 200;
		$message = null;
		
		if (auth()->check()) {
			// Set the dark mode for the user
			$data = getServiceData($this->userService->saveThemePreference($userId, $request));
			
			// Parsing the API response
			$status = (int)data_get($data, 'status');
			$message = data_get($data, 'message');
			
			// Error Found
			if (!data_get($data, 'success')) {
				$message = $message ?? t('unknown_error');
				
				return ajaxResponse()->json(['message' => $message], $status);
			}
			
			// Get entry resource
			$user = data_get($data, 'result');
			$themePreference = (int)data_get($user, 'theme_preference', 0);
		}
		
		// Set or remove dark mode cookie
		if (!empty($themePreference)) {
			$message = !empty($message) ? $message : t('theme_preference_success');
		} else {
			$message = !empty($message) ? $message : t('theme_preference_empty');
		}
		
		// AJAX response data
		$result = [
			'userId'  => $request->integer('user_id'),
			'theme'   => $themePreference,
			'message' => $message,
		];
		
		return ajaxResponse()->json($result, $status);
	}
}
