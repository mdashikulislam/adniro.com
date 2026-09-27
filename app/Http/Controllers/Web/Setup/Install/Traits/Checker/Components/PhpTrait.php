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

namespace App\Http\Controllers\Web\Setup\Install\Traits\Checker\Components;

use App\Helpers\Common\Num;
use Throwable;

trait PhpTrait
{
	/**
	 * Get the composer.json required PHP version
	 *
	 * @return float|int|string|null
	 */
	public function getComposerRequiredPhpVersion(): float|int|string|null
	{
		$version = null;
		
		$filePath = base_path('composer.json');
		
		try {
			$content = file_get_contents($filePath);
			$array = json_decode($content, true);
			
			if (isset($array['require']['php'])) {
				$version = $array['require']['php'];
			}
		} catch (Throwable $e) {
		}
		
		if (empty($version)) {
			$version = config('version.php', '8.2');
		}
		
		return Num::getFloatRawFormat($version);
	}
	
	/**
	 * Get path of the PHP binary (PHP-cli) on the server
	 *
	 * @return string|null
	 */
	public function getPhpBinaryPath(): ?string
	{
		// Resolving the PHP binary spawns "whereis php" (a PATH-wide scan): memoize it per process
		// and keep it in the cache for a day instead of running it on every web request.
		static $memoPath = false;
		if ($memoPath !== false) {
			return $memoPath;
		}
		
		try {
			$cached = cache()->get('system.php_binary_path');
			if (is_string($cached) && $cached !== '') {
				return $memoPath = $cached;
			}
		} catch (Throwable $e) {
		}
		
		$path = $this->resolvePhpBinaryPath();
		
		if (!empty($path)) {
			try {
				cache()->put('system.php_binary_path', $path, 86400);
			} catch (Throwable $e) {
			}
		}
		
		return $memoPath = $path;
	}
	
	/**
	 * Resolve the PHP binary path on the server (uncached)
	 *
	 * @return string|null
	 */
	private function resolvePhpBinaryPath(): ?string
	{
		$path = null;
		
		if (defined(PHP_BINARY)) {
			$path = PHP_BINARY;
		}
		
		if (empty($path)) {
			try {
				$path = exec('whereis php');
			} catch (Throwable $e) {
			}
			
			if (empty($path)) {
				try {
					$path = exec('which php');
				} catch (Throwable $e) {
				}
			}
		}
		
		if ($path == trim($path) && str_contains($path, ' ')) {
			$tmp = explode(' ', $path);
			if (isset($tmp[1])) {
				$path = $tmp[1];
			}
		}
		
		$path = is_string($path) ? $path : null;
		
		if (!empty($path)) {
			$unwantedPrefix = 'php:';
			// $path = (trim($path) == $unwantedPrefix) ? 'php' : $path;
			if (str_starts_with($path, $unwantedPrefix)) {
				$tmp = explode($unwantedPrefix, $path, 1);
				$path = $tmp[1] ?? null;
				$path = is_string($path) ? trim($path) : null;
			}
		}
		
		return $path;
	}
	
	/**
	 * @return string|null
	 */
	public function getPhpBinaryVersion(): ?string
	{
		// "php --version" boots a full PHP CLI process: memoize per process and cache for a day.
		static $memoVersion = false;
		if ($memoVersion !== false) {
			return $memoVersion;
		}
		
		try {
			$cached = cache()->get('system.php_binary_version');
			if (is_string($cached) && $cached !== '') {
				return $memoVersion = $cached;
			}
		} catch (Throwable $e) {
		}
		
		$version = null;
		
		$phpBinaryPath = $this->getPhpBinaryPath();
		if (!empty($phpBinaryPath)) {
			try {
				exec($phpBinaryPath . ' --version', $version);
			} catch (Throwable $e) {
			}
		}
		
		if (is_array($version)) {
			$version = implode(' ', $version);
		}
		
		if (!empty($version) && is_string($version)) {
			$version = $this->parsePhpVersion($version);
		}
		
		if (!empty($version) && is_string($version)) {
			try {
				cache()->put('system.php_binary_version', $version, 86400);
			} catch (Throwable $e) {
			}
		}
		
		return $memoVersion = $version;
	}
	
	/**
	 * PHP: Extract version number for string
	 *
	 * @param $str
	 * @return string|null
	 */
	public function parsePhpVersion($str): ?string
	{
		preg_match("/(?:PHP|version|)\s*((?:\d+\.?)+)/i", $str, $matches);
		$value = $matches[1] ?? null;
		
		return is_string($value) ? $value : null;
	}
}
