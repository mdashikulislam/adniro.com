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

use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Front\Page\ContactController;
use App\Http\Controllers\Web\Front\Page\PageController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\BaseController;
use App\Http\Controllers\Web\Front\Search\SearchController;
use App\Http\Controllers\Web\Front\SitemapController;
use Closure;
use Illuminate\Http\Request;

class TipsMessages
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		// Exception for Install & Upgrade Routes
		if (isFromInstallOrUpgradeProcess()) {
			return $next($request);
		}
		
		if (!config('settings.other.show_tips_messages')) {
			return $next($request);
		}
		
		// SHOW MESSAGE... (About Login) If user not logged
		if (
			!auth()->check()
			&& request()->segment(1) !== null
			&& !routeActionHas(getClassNamespaceName(RegisterController::class))
			&& !routeActionHas(getClassNamespaceName(BaseController::class, depth: 1))
			&& !routeActionHas(getClassNamespaceName(SearchController::class))
			&& !routeActionHas(SitemapController::class)
			&& !routeActionHas(PageController::class . '@show')
			&& !routeActionHas(ContactController::class . '@showForm')
		) {
			$messageKey = 'login_for_faster_access_to_the_best_deals';
			$loginInfo = t($messageKey, [
				'login_url'    => urlGen()->signIn(),
				'register_url' => urlGen()->signUp(),
			]);
		}
		
		// SHOW MESSAGE... (About Location)
		// - If we know the user IP country
		// - and if user visiting another country's website
		// - and if Geolocation is activated
		$countryCode = config('country.code');
		$ipCountryCode = config('ipCountry.code');
		$ipCountryName = config('ipCountry.name');
		if (config('settings.localization.geoip_activation')) {
			if (!empty($ipCountryCode) && !empty($countryCode)) {
				if ($ipCountryCode != $countryCode) {
					$messageKey = 'app_is_also_available_in_your_country';
					$siteCountryInfo = t($messageKey, [
						'appName' => config('settings.app.name'),
						'country' => getColumnTranslation($ipCountryName),
						'url'     => dmUrl($ipCountryCode, '/', true, true),
					]);
				}
			}
		}
		
		$message = $siteCountryInfo ?? $loginInfo ?? null;
		
		if (!empty($message)) {
			flash($message)->now()->warning();
		}
		
		return $next($request);
	}
}
