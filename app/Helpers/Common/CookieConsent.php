<?php

namespace App\Helpers\Common;

use App\Models\Advertising;
use Throwable;

/*
 * Cookie consent (adniro). The banner, and the gate on the footer tracking code and the ad slots,
 * are on when the admin switched on the cookie banner (Admin > Settings > Other) and there is
 * something that sets non-essential cookies: a tracking code or an active ad.
 *
 * Only visitors from REGIONS are asked (the UK, the EEA and Switzerland, where the GDPR needs
 * consent). Pages are cached at the edge for every country, so the browser decides from the
 * visitor's country (public/assets/js/app/consent.js); everyone else gets the scripts at once.
 */

final class CookieConsent
{
	public const COOKIE = 'site_cookie_consent';
	public const MAX_AGE = 31536000; // a year
	public const POLICY_SLUG = 'cookie-policy';

	/** Where the browser learns the visitor's country (Cloudflare, on every proxied site) */
	public const REGION_URL = '/cdn-cgi/trace';

	/** The EU, the other EEA countries (Iceland, Liechtenstein, Norway), the UK and Switzerland */
	public const REGIONS = [
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU',
		'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
		'IS', 'LI', 'NO',
		'GB', 'CH',
	];

	public static function required(): bool
	{
		if (config('settings.other.cookie_consent_enabled') != '1') {
			return false;
		}

		return trim((string)config('settings.footer.tracking_code')) !== '' || self::hasActiveAds();
	}

	/** An active ad slot with code in it (the Advertising model only finds active ones) */
	private static function hasActiveAds(): bool
	{
		try {
			return (bool)caching()->remember(Advertising::class, ['action' => 'consent.hasActiveAds'], function () {
				return Advertising::query()
					->where(function ($query) {
						$query->whereNotNull('tracking_code_large')->where('tracking_code_large', '!=', '');
					})
					->exists();
			});
		} catch (Throwable $e) {
			report($e);

			return false;
		}
	}
}
