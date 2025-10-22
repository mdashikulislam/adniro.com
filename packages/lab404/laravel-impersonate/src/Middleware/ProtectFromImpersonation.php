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

namespace Larapen\Impersonate\Middleware;

use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Edit\PhotoController;
use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

class ProtectFromImpersonation
{
	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function handle(Request $request, Closure $next)
	{
		$impersonateManager = app()->make(ImpersonateManager::class);
		
		if ($impersonateManager->isImpersonating()) {
			$message = t('Can not be accessed by an impersonator');
			
			if (isFromAjax($request)) {
				$result = [
					'success' => false,
					'message' => $message,
				];
				
				// Add a specific json attributes for 'bootstrap-fileinput' plugin
				if (
					routeActionHas(PhotoController::class . '@postForm')
					|| routeActionHas(PhotoController::class . '@delete')
					|| routeActionHas(PhotoController::class . '@reorder')
				) {
					// NOTE: 'bootstrap-fileinput' need 'error' (text) element & the optional 'errorkeys' (array) element
					$result['error'] = $message;
				}
				
				return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
			} else {
				notification($message, 'error');
				
				return redirect()->back();
			}
		}
		
		return $next($request);
	}
}
