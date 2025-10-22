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

namespace App\Helpers\Common;

use Illuminate\Http\Response;
use Throwable;

class Cookie
{
	/**
	 * Set cookie
	 *
	 * @param string $name
	 * @param string|null $value
	 * @param int|null $minutes
	 * @return void
	 */
	public static function set(string $name, ?string $value, ?int $minutes = 0): void
	{
		if (is_null($value)) return;
		
		$defaultMinutes = 1440;
		$defaultPath = null;
		$defaultDomain = getCookieDomain();
		$defaultSecure = null;
		$defaultHttpOnly = true;
		$defaultSameSite = null;
		
		$globalMinutes = (int)config('settings.other.cookie_expiration', $defaultMinutes);
		$globalMinutes = !empty($globalMinutes) ? $globalMinutes : $defaultMinutes;
		
		$path = config('session.path', $defaultPath);
		$domain = config('session.domain', $defaultDomain);
		$secure = config('session.secure', $defaultSecure);
		$httpOnly = config('session.http_only', $defaultHttpOnly);
		$sameSite = config('session.same_site', $defaultSameSite);
		
		$minutes = !empty($minutes) ? $minutes : $globalMinutes;
		$path = !empty($path) ? $path : $defaultPath;
		$domain = !empty($domain) ? $domain : $defaultDomain;
		
		// Type Verification
		$path = is_string($path) ? $path : $defaultPath;
		$domain = is_string($domain) ? $domain : $defaultDomain;
		$secure = is_bool($secure) ? $secure : $defaultSecure;
		$httpOnly = is_bool($httpOnly) ? $httpOnly : $defaultHttpOnly;
		$sameSite = is_string($sameSite) ? $sameSite : $defaultSameSite;
		
		try {
			/**
			 * @param  string  $name
			 * @param  string  $value
			 * @param  int  $minutes
			 * @param  string|null  $path
			 * @param  string|null  $domain
			 * @param  bool|null  $secure
			 * @param  bool  $httpOnly
			 * @param  bool  $raw
			 * @param  string|null  $sameSite
			 * @return \Symfony\Component\HttpFoundation\Cookie
			 */
			$cookieObj = cookie()->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly, false, $sameSite);
			cookie()->queue($cookieObj);
		} catch (Throwable $e) {
			abort(400, $e->getMessage());
		}
	}
	
	/**
	 * Get cookie
	 *
	 * @param string|null $name
	 * @param null $default
	 * @return array|string|null
	 */
	public static function get(?string $name = null, $default = null): array|string|null
	{
		return request()->cookie($name, $default);
	}
	
	/**
	 * Check if cookie exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function has(string $name): bool
	{
		return request()->hasCookie($name);
	}
	
	/**
	 * Delete cookie
	 *
	 * @param string $name
	 * @return void
	 */
	public static function forget(string $name): void
	{
		if (self::has($name)) {
			$defaultPath = null;
			$defaultDomain = getCookieDomain();
			
			$path = config('session.path', $defaultPath);
			$domain = config('session.domain', $defaultDomain);
			
			// Type Verification
			$path = is_string($path) ? $path : $defaultPath;
			$domain = is_string($domain) ? $domain : $defaultDomain;
			
			$cookieObj = cookie()->forget($name, $path, $domain);
			cookie()->queue($cookieObj);
		}
	}
	
	/**
	 * Delete all cookies (for current domain)
	 *
	 * @return void
	 */
	public static function forgetAll(): void
	{
		$cookies = request()->cookies->all();
		if (!empty($cookies)) {
			foreach ($cookies as $name => $value) {
				self::forget($name);
			}
		}
	}
	
	/**
	 * Send redirect and setting cookie in Laravel
	 *
	 * @param $url
	 * @param $cookie
	 * @param int $status
	 * @param array $headers
	 * @return \Illuminate\Http\Response
	 */
	public static function redirect($url, $cookie = null, int $status = 302, array $headers = []): Response
	{
		if (in_array($status, [301, 302])) {
			$status = 302;
		}
		
		$response = new Response('', $status);
		
		if (!empty($cookie)) {
			$response->withCookie($cookie);
		}
		if (!empty($headers)) {
			$response->withHeaders($headers);
		}
		$response->header('Location', $url);
		
		return $response;
	}
}
