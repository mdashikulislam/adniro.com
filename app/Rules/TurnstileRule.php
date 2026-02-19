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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurnstileRule implements ValidationRule
{
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail(trans('validation.turnstile'));
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 * Call out to Cloudflare Turnstile and process the response.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = strip_tags($value);
		
		$secretKey = config('settings.security.turnstile_secret_key');
		
		if (empty($secretKey)) {
			return false;
		}
		
		// Check if IP should be skipped
		if ($this->skipByIp()) {
			return true;
		}
		
		// Cloudflare Turnstile verification endpoint
		$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		
		$data = [
			'secret'   => $secretKey,
			'response' => $value,
			'remoteip' => request()->getClientIp(),
		];
		
		if (function_exists('curl_version')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			
			$buffer = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);
			if (!$buffer) {
				return false;
			}
		} else {
			$options = [
				'http' => [
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'  => 'POST',
					'content' => http_build_query($data),
					'timeout' => 10,
				],
			];
			$context = stream_context_create($options);
			$buffer = file_get_contents($url, false, $context);
		}
		
		if (empty($buffer)) {
			return false;
		}
		
		$response = json_decode(trim($buffer), true);
		
		return $response['success'] ?? false;
	}
	
	/**
	 * Get IP whitelist from config
	 *
	 * @return array
	 */
	protected function getIpWhitelist(): array
	{
		$whitelist = config('settings.security.turnstile_skip_ip', []);
		
		if (is_string($whitelist)) {
			$whitelist = explode(',', $whitelist);
		}
		
		return is_array($whitelist) ? $whitelist : [];
	}
	
	/**
	 * Check if the user IP should skip validation
	 *
	 * @return bool
	 */
	protected function skipByIp(): bool
	{
		return in_array(request()->ip(), $this->getIpWhitelist());
	}
}
