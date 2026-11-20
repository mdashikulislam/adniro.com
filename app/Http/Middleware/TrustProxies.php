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

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
	/**
	 * The trusted proxies for this application.
	 *
	 * @var array<int, string>|string|null
	 */
	protected $proxies = '*';
	
	/**
	 * From config('app.trusted_proxies') (TRUSTED_PROXIES): "*" trusts any proxy, a comma-separated
	 * list trusts only those addresses or ranges, and an empty value trusts none. Behind a known
	 * proxy, list it: trusting every proxy lets clients forge their IP address (adniro:ai).
	 *
	 * @return array<int, string>|string|null
	 */
	protected function proxies()
	{
		$value = trim((string)config('app.trusted_proxies', '*'));
		
		if ($value === '') {
			return null;
		}
		if (in_array($value, ['*', '**'], true)) {
			return $value;
		}
		
		return array_values(array_filter(array_map('trim', explode(',', $value))));
	}
	
	/**
	 * The headers that should be used to detect proxies.
	 *
	 * @var int
	 */
	protected $headers =
		Request::HEADER_X_FORWARDED_FOR |
		Request::HEADER_X_FORWARDED_HOST |
		Request::HEADER_X_FORWARDED_PORT |
		Request::HEADER_X_FORWARDED_PROTO |
		Request::HEADER_X_FORWARDED_AWS_ELB;
}
