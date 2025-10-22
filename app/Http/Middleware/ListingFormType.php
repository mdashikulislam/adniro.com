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

namespace App\Http\Middleware;

use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\BaseController as MultiStepsBaseController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\BaseController as MultiStepsCreateBaseController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Edit\BaseController as MultiStepsEditBaseController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\SingleStep\BaseController as SingleStepBaseController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\SingleStep\CreateController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\SingleStep\EditController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ListingFormType
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\RedirectResponse|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$noCacheHeaders = config('larapen.core.noCacheHeaders');
		
		// MULTI-STEPS Form Type Detected
		if (routeActionHas(getClassNamespaceName(MultiStepsBaseController::class))) {
			// Creation Form Detected
			if (routeActionHas(getClassNamespaceName(MultiStepsCreateBaseController::class))) {
				// Check if the form type is 'Single-Step Form'
				// If so, make a (permanent) redirection to it.
				if (isSingleStepFormEnabled()) {
					$url = urlGen()->addPost();
					if ($url != request()->fullUrl()) {
						return redirect()->to($url, 301)->withHeaders($noCacheHeaders);
					}
				}
			}
			
			// Edit Form Detected
			if (routeActionHas(getClassNamespaceName(MultiStepsEditBaseController::class))) {
				$params = Route::current()->parameters();
				
				$postId = $params['id'] ?? null;
				if (!empty($postId)) {
					// Check if the form type is 'Single-Step Form'
					// If so, make a (permanent) redirection to it.
					if (isSingleStepFormEnabled()) {
						$url = urlGen()->editPost($postId);
						if ($url != request()->fullUrl()) {
							return redirect()->to($url, 301)->withHeaders($noCacheHeaders);
						}
					}
				}
			}
		}
		
		// SINGLE-STEP Form Type Detected
		if (routeActionHas(getClassNamespaceName(SingleStepBaseController::class))) {
			// Creation Form Detected
			if (routeActionHas(CreateController::class)) {
				// Check if the form type is 'Multi-Step Form'
				// If so, make a (permanent) redirection to it.
				if (isMultipleStepsFormEnabled()) {
					$url = urlGen()->addPost();
					if ($url != request()->fullUrl()) {
						return redirect()->to($url, 301)->withHeaders($noCacheHeaders);
					}
				}
			}
			
			// Edit Form Detected
			if (routeActionHas(EditController::class)) {
				$params = Route::current()->parameters();
				
				$postId = $params['id'] ?? null;
				if (!empty($postId)) {
					// Check if the form type is 'Multi-Step Form'
					// If so, make a (permanent) redirection to it.
					if (isMultipleStepsFormEnabled()) {
						$url = urlGen()->editPost($postId);
						if ($url != request()->fullUrl()) {
							return redirect()->to($url, 301)->withHeaders($noCacheHeaders);
						}
					}
				}
			}
		}
		
		return $next($request);
	}
}
