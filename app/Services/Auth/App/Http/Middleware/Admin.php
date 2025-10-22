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

namespace App\Services\Auth\App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Admin
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$message = trans('admin.unauthorized');
		
		$guard = getAuthGuard();
		$authUser = auth($guard)->check() ? auth($guard)->user() : null;
		
		$currentUrl = urlBuilder($request->fullUrl())->removeAllParameters();
		$loginUrl = urlGen()->signIn();
		
		if (empty($authUser)) {
			// Block access if user is guest (not logged in)
			if (isFromAjax($request)) {
				return ajaxResponse()->text($message, Response::HTTP_UNAUTHORIZED);
			} else {
				if ($currentUrl != $loginUrl) {
					notification(message: $message, level: 'error', targetUrl: $loginUrl);
					
					return redirect()->guest($loginUrl);
				}
			}
		} else {
			$versionIntroducingAcl = (config('larapen.core.item.slug') == 'jobclass') ? '4.0.0' : '5.2.0';
			if (currentVersionIsLt($versionIntroducingAcl)) {
				return $next($request);
			}
			
			$user = User::query()->count();
			if (!($user == 1)) {
				// If user does //not have this permission
				if (!doesUserHaveStaffPermission($authUser)) {
					if (isFromAjax($request)) {
						return ajaxResponse()->text($message, Response::HTTP_UNAUTHORIZED);
					} else {
						auth($guard)->logout();
						notification(message: $message, level: 'error', targetUrl: $loginUrl);
						
						return redirect()->guest($loginUrl);
					}
				}
			}
		}
		
		return $next($request);
	}
}
