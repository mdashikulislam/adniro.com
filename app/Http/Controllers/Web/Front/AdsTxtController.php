<?php

namespace App\Http\Controllers\Web\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/*
 * /ads.txt (adniro): the lines an admin pastes in Admin > Settings > SEO > ads.txt. Ad networks
 * (Google AdSense, Adsterra...) read it to confirm who may sell the site's ad space.
 * No lines: 404, as for a site without the file.
 */

class AdsTxtController extends Controller
{
	public function show(): Response
	{
		$lines = preg_split('/\R/u', (string)config('settings.seo.ads_txt'));
		$lines = array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));

		abort_if(empty($lines), 404);

		return response(implode("\n", $lines) . "\n", 200, [
			'Content-Type'  => 'text/plain; charset=UTF-8',
			'Cache-Control' => 'public, max-age=3600',
		]);
	}
}
