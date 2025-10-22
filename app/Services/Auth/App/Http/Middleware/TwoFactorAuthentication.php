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

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * This middleware in only for web routes
 */

class TwoFactorAuthentication
{
	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\RedirectResponse|mixed
	 */
	public function handle(Request $request, Closure $next): mixed
	{
		if (isFromApi()) {
			return $next($request);
		}
		
		// Check if the 2FA is enabled globally
		if (!isTwoFactorEnabled()) {
			return $next($request);
		}
		
		$authUser = auth(getAuthGuard())->user();
		
		if (!empty($authUser)) {
			// Is the 2FA is enabled by the user?
			$isTwoFactorEnabled = $authUser->two_factor_enabled ?? false;
			$isTwoFactorUnauthenticated = ($isTwoFactorEnabled && !session('twoFactorAuthenticated'));
			
			if ($isTwoFactorUnauthenticated) {
				if ($request->expectsJson()) {
					abort(Response::HTTP_FORBIDDEN);
				} else {
					return redirect()->to(urlGen()->twoFactorChallenge());
				}
			}
		}
		
		return $next($request);
	}
}
