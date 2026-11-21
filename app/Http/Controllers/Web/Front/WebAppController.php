<?php

namespace App\Http\Controllers\Web\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/*
 * adniro: the installable app's offline page. The service worker (public/sw.js) keeps this one page
 * and shows it when a page cannot be loaded without a network; nothing else is kept offline.
 */
class WebAppController extends Controller
{
	private const BROWSER_CACHE_SECONDS = 3600;

	public function offline(): Response
	{
		return response()
			->view('front.web-app.offline', [
				'siteName'   => (string)config('settings.app.name', config('app.name')),
				'themeColor' => SiteIconController::themeColor(),
			])
			->header('Cache-Control', 'public, max-age=' . self::BROWSER_CACHE_SECONDS)
			->header('X-Robots-Tag', 'noindex');
	}
}
