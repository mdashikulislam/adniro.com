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

use App\Services\Auth\Traits\Custom\Verification\CheckIfAuthFieldIsVerified;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IsVerifiedUser
{
	use CheckIfAuthFieldIsVerified;
	
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$guard = getAuthGuard();
		
		if (!auth($guard)->check()) {
			return $next($request);
		}
		
		// Is user has verified login?
		$vData = $this->userHasVerifiedAuthField(auth($guard)->user());
		$isSuccess = array_key_exists('success', $vData) && $vData['success'];
		
		// User has verified login, then skip error displaying
		if ($isSuccess) {
			return $next($request);
		}
		
		// User has not verified login, then get the right error message
		$errorMessage = $vData['message'] ?? 'Unauthorized';
		$fMethod = $vData['extra']['flashMethod'] ?? null;
		
		// Display an (unauthorized) error message
		if (isFromApi()) {
			$data = [
				'success' => false,
				'message' => $errorMessage,
				'extra'   => $vData['extra'] ?? [],
			];
			
			return apiResponse()->json($data, Response::HTTP_FORBIDDEN);
		} else {
			if ($request->expectsJson()) {
				abort(Response::HTTP_FORBIDDEN, $errorMessage);
			} else {
				$isForAuthenticate = ($request->url() == urlGen()->signIn());
				$isForPhoneVerification = (
					str_contains($request->url(), 'verify/')
					&& str_contains($request->url(), '/phone')
				);
				
				if (!empty($fMethod)) {
					flash($errorMessage)->$fMethod();
				} else {
					if ($isForPhoneVerification) {
						flash($errorMessage)->warning();
					} else {
						flash($errorMessage)->error();
					}
				}
				
				if (!$isForAuthenticate && !$isForPhoneVerification) {
					return redirect()->to(urlGen()->signIn());
				}
			}
		}
		
		return $next($request);
	}
}
