<?php

namespace App\Helpers\Common;

/*
 * Where ads may appear (adniro). Ad networks, Google AdSense first, refuse ads on pages without
 * content of their own: sign-in, account, the listing form, chat, contact, pricing. Search pages
 * without results drop their ads in the view (front/search/results.blade.php sets $hideAds).
 */

final class AdPlacement
{
	/** Route names (Route::is patterns) of pages that never carry ads */
	public const NO_AD_ROUTES = [
		'auth.*',
		'account', 'account.*',
		'listing.create.*', 'listing.edit.*', 'listing.form*', 'listing.assistant*', 'listing.report*',
		'chat', 'chat.*',
		'contact*',
		'pricing',
	];

	/** Pages kept out of Google's index: forms and private areas */
	public const NO_INDEX_ROUTES = [
		'auth.*',
		'account', 'account.*',
		'listing.create.*', 'listing.edit.*', 'listing.form*', 'listing.report*',
		'chat', 'chat.*',
	];

	public static function allowedOnThisPage(): bool
	{
		return !request()->routeIs(...self::NO_AD_ROUTES);
	}

	public static function noIndexThisPage(): bool
	{
		return request()->routeIs(...self::NO_INDEX_ROUTES);
	}
}
