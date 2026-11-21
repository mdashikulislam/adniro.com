<?php

namespace App\Helpers\Common;

/*
 * Cookie consent (adniro). The banner and the gate on the footer tracking code are on when the
 * admin switched on the cookie banner (Admin > Settings > Other) and a tracking code is set:
 * without non-essential cookies there is nothing to ask.
 */

final class CookieConsent
{
	public const COOKIE = 'site_cookie_consent';
	public const MAX_AGE = 31536000; // a year
	public const POLICY_SLUG = 'cookie-policy';
	
	public static function required(): bool
	{
		return config('settings.other.cookie_consent_enabled') == '1'
			&& trim((string)config('settings.footer.tracking_code')) !== '';
	}
}
