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
 * Validate Memcached cache driver availability and connection
 */

class MemcachedDriverValidationRule implements ValidationRule
{
	private string $errorMessage = 'The Memcached cache driver is not available or the server is unreachable.';
	
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
	 * Determine if the Memcached cache driver is valid and connectable.
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		try {
			// Check if Memcached extension is installed
			if (!extension_loaded('memcached')) {
				$this->errorMessage = 'The Memcached extension is not installed.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// Check if Memcached class exists
			if (!class_exists('\Memcached')) {
				$this->errorMessage = 'The Memcached class is not available.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// Get Memcached configuration from Laravel config
			$servers = config('cache.stores.memcached.servers', [
				[
					'host'   => '127.0.0.1',
					'port'   => 11211,
					'weight' => 100,
				],
			]);
			
			if (empty($servers)) {
				$this->errorMessage = 'No Memcached servers configured.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// Create Memcached instance
			$memcached = new \Memcached();
			
			// Add servers
			foreach ($servers as $server) {
				$memcached->addServer(
					$server['host'],
					$server['port'],
					$server['weight'] ?? 0
				);
			}
			
			// Set connection timeout
			$memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000); // 1 second
			$memcached->setOption(\Memcached::OPT_RECV_TIMEOUT, 1000000); // 1 second
			$memcached->setOption(\Memcached::OPT_SEND_TIMEOUT, 1000000); // 1 second
			
			// Test connection by getting server stats
			$stats = $memcached->getStats();
			
			if (empty($stats)) {
				$this->errorMessage = 'Cannot connect to any Memcached servers.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// Check if at least one server is responsive
			$hasActiveServer = false;
			foreach ($stats as $server => $serverStats) {
				if (!empty($serverStats) && is_array($serverStats)) {
					$hasActiveServer = true;
					break;
				}
			}
			
			if (!$hasActiveServer) {
				$this->errorMessage = 'No active Memcached servers found.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// Test basic set/get operations
			$testKey = 'laravel_memcached_test_' . time();
			$testValue = 'test_connection';
			
			if (!$memcached->set($testKey, $testValue, 10)) { // 10 seconds expiry
				$this->errorMessage = 'Memcached set operation failed.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			$retrievedValue = $memcached->get($testKey);
			$memcached->delete($testKey);
			
			if ($retrievedValue !== $testValue) {
				$this->errorMessage = 'Memcached get operation failed.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			return true;
			
		} catch (Exception $e) {
			$message = $e->getMessage();
			$this->errorMessage = 'Memcached connection error: ' . $message;
			logger()->error($this->errorMessage, ['exception' => $e]);
			
			return false;
		}
	}
}

