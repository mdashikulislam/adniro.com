<?php

namespace App\Helpers\Common;

use Illuminate\Http\Request;

/*
 * adniro: whether a request came by the site's own address.
 *
 * Things kept in the cache per address (finished menus, guest pages) carry the address in their links. On a
 * live site only the site's own address (APP_URL, with or without "www") gets a kept copy, so made-up host
 * names cannot fill the cache. Anywhere else (local, tests) any address counts.
 */
class SiteHost
{
	public static function isOwn(?Request $request = null): bool
	{
		if (!app()->isProduction()) {
			return true;
		}

		$withoutWww = fn (?string $host) => preg_replace('/^www\./', '', strtolower((string)$host));
		$ownHost = $withoutWww(parse_url((string)config('app.url'), PHP_URL_HOST) ?: '');

		return $ownHost !== '' && $withoutWww(($request ?? request())->getHost()) === $ownHost;
	}
}
