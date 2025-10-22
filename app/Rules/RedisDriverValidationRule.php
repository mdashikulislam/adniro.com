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
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

/*
 * Validate Redis cache driver availability and connection
 */

class RedisDriverValidationRule implements ValidationRule
{
	private string $errorMessage = 'The Redis cache driver is not available or the server is unreachable.';
	
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
	 * Determine if the Redis connection is valid.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		try {
			// Check if PhpRedis extension is installed
			if (!extension_loaded('redis')) {
				$this->errorMessage = 'The PhpRedis extension is not installed.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// Check if Redis class exists
			if (!class_exists('\Redis')) {
				$this->errorMessage = 'The Redis class is not available.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// Test Laravel Redis connection
			// And attempt to ping the Redis server
			// $connection = Redis::connection('default');
			$connection = Redis::connection();
			$pingResult = $connection->ping();
			
			// Attempt to ping the Redis server (without explicit connection)
			// $pingResult = Redis::ping();
			
			// Validate ping response (different Redis versions return different responses)
			if (!in_array($pingResult, ['+PONG', 'PONG', true, 1])) {
				$this->errorMessage = 'Redis server ping failed.';
				logger()->error($this->errorMessage);
				
				return false;
			}
			
			// If the ping has been done through a Redis::connection(),
			// then use that connection for set/set operations test
			if (!empty($connection) && $connection instanceof Connection) {
				// Test basic set/get operations
				$testKey = 'laravel_redis_test_' . time();
				$testValue = 'test_connection';
				
				$connection->set($testKey, $testValue, 'EX', 10); // 10 seconds expiry
				$retrievedValue = $connection->get($testKey);
				$connection->del($testKey);
				
				if ($retrievedValue !== $testValue) {
					$this->errorMessage = 'Redis set/get operations failed.';
					logger()->error($this->errorMessage);
					
					return false;
				}
			}
			
			return true;
			
		} catch (Exception $e) {
			$message = $e->getMessage();
			$this->errorMessage = 'Redis connection error: ' . $message;
			logger()->error($this->errorMessage, ['exception' => $e]);
			
			return false;
		}
	}
}
