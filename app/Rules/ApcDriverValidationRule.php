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
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

/*
 * Validate APC/APCu cache driver availability
 */

class ApcDriverValidationRule implements ValidationRule
{
	private string $errorMessage = 'The APC/APCu cache driver is not available.';
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail($this->errorMessage);
		}
	}
	
	/**
	 * Determine if the APC/APCu cache driver is valid and functional.
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		try {
			$apcuAvailable = false;
			$apcAvailable = false;
			
			// Check for APCu (preferred)
			if (extension_loaded('apcu')) {
				if (function_exists('apcu_store') && ini_get('apc.enabled')) {
					// Test APCu functionality
					$testKey = 'laravel_apcu_test_' . time();
					$testValue = 'test_connection';
					
					if (apcu_store($testKey, $testValue, 10)) { // 10 seconds TTL
						$retrievedValue = apcu_fetch($testKey);
						apcu_delete($testKey);
						
						if ($retrievedValue === $testValue) {
							$apcuAvailable = true;
						}
					}
				}
			}
			
			// Check for legacy APC (fallback)
			if (!$apcuAvailable && extension_loaded('apc')) {
				if (function_exists('apc_store') && ini_get('apc.enabled')) {
					// Test APC functionality
					$testKey = 'laravel_apc_test_' . time();
					$testValue = 'test_connection';
					
					if (apc_store($testKey, $testValue, 10)) { // 10 seconds TTL
						$retrievedValue = apc_fetch($testKey);
						apc_delete($testKey);
						
						if ($retrievedValue === $testValue) {
							$apcAvailable = true;
						}
					}
				}
			}
			
			if (!$apcuAvailable && !$apcAvailable) {
				// Determine specific error message
				if (!extension_loaded('apcu') && !extension_loaded('apc')) {
					$this->errorMessage = 'Neither APCu nor APC extensions are installed.';
				} else if (!ini_get('apc.enabled')) {
					$this->errorMessage = 'APC/APCu is installed but not enabled (apc.enabled = Off).';
				} else if (extension_loaded('apcu') && !function_exists('apcu_store')) {
					$this->errorMessage = 'APCu extension is loaded but apcu_store function is not available.';
				} else if (extension_loaded('apc') && !function_exists('apc_store')) {
					$this->errorMessage = 'APC extension is loaded but apc_store function is not available.';
				} else {
					$this->errorMessage = 'APC/APCu set/get operations failed.';
				}
				
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			return true;
			
		} catch (Exception $e) {
			$message = $e->getMessage();
			$this->errorMessage = 'APC/APCu error: ' . $message;
			logger()->error($this->errorMessage, ['exception' => $e]);
			
			return false;
		}
	}
}
