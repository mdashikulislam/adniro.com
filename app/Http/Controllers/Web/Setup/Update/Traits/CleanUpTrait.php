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

namespace App\Http\Controllers\Web\Setup\Update\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

trait CleanUpTrait
{
	/**
	 * Perform comprehensive application cleanup
	 *
	 * Removes robots.txt, clears all caches, and deletes log files.
	 * This method is typically used during deployment or maintenance tasks.
	 *
	 * @return void
	 */
	private function performApplicationCleanup(): void
	{
		$this->removeRobotsTxtFile();
		
		// Clear all Laravel cache store
		$this->clearAllCacheStores();
		
		// Clear Laravel view cache
		$this->clearViewCache();
		
		File::delete(File::glob(storage_path('logs') . DIRECTORY_SEPARATOR . '*.log'));
	}
	
	/**
	 * Flush the default cache store programmatically
	 *
	 * Uses cache()->flush() to clear only the default cache store.
	 * Silently handles any exceptions that may occur during the flush operation.
	 *
	 * @return void
	 */
	private function flushDefaultCacheStore(): void
	{
		try {
			cache()->flush();
		} catch (Throwable $e) {
		}
	}
	
	/**
	 * Clear all cache stores using Artisan command
	 *
	 * Executes 'cache:clear' command which clears all configured cache stores
	 * and performs additional cleanup operations.
	 *
	 * @param int $sleepTime Seconds to sleep after clearing cache (default: 2)
	 * @return void
	 */
	private function clearAllCacheStores(int $sleepTime = 2): void
	{
		Artisan::call('cache:clear');
		if ($sleepTime > 0) {
			sleep($sleepTime);
		}
	}
	
	/**
	 * Clear compiled view cache
	 *
	 * Removes all compiled Blade templates from storage/framework/views
	 *
	 * @param int $sleepTime Seconds to sleep after clearing views (default: 1)
	 * @return void
	 */
	private function clearViewCache(int $sleepTime = 1): void
	{
		Artisan::call('view:clear');
		if ($sleepTime > 0) {
			sleep($sleepTime);
		}
	}
	
	/**
	 * Remove the robots.txt file (It will be re-generated automatically)
	 *
	 * @return void
	 */
	private function removeRobotsTxtFile(): void
	{
		$robotsFilePath = public_path('robots.txt');
		if (File::exists($robotsFilePath)) {
			File::delete($robotsFilePath);
		}
	}
	
	/**
	 * Clear Laravel data cache
	 *
	 * @param int $sleepTime
	 * @return void
	 */
	private function clearDataCache(int $sleepTime = 0): void
	{
		$cacheDirectory = storage_path('framework/cache/data/');
		$this->purgeCacheDirectoryWithFallback('cache:clear', $cacheDirectory, $sleepTime);
	}
	
	/**
	 * Manually purge compiled view files from filesystem
	 *
	 * Directly removes all compiled Blade template files from the
	 * storage/framework/views directory without using Artisan commands.
	 * This is a low-level alternative to the 'view:clear' Artisan command.
	 *
	 * @param int $sleepTime Seconds to sleep after purging files (default: 0)
	 * @return void
	 */
	private function manuallyPurgeCompiledViews(int $sleepTime = 0): void
	{
		$cacheDirectory = storage_path('framework/views/');
		$this->purgeCacheDirectoryWithFallback('view:clear', $cacheDirectory, $sleepTime);
	}
	
	/**
	 * Forcefully purge cache directory and recreate it
	 *
	 * Performs a low-level cache directory cleanup by:
	 * 1. Removing the entire cache directory using system 'rm -rf' command
	 * 2. Recreating the empty cache directory structure
	 * 3. Falls back to Artisan command if directory operations fail
	 *
	 * This method bypasses Laravel's cache clearing mechanisms for performance
	 * in scenarios where direct filesystem operations are preferred.
	 *
	 * @param string $fallbackCommand Artisan command to use as fallback (e.g., 'view:clear')
	 * @param string $cacheDirectory Absolute path to the cache directory to purge
	 * @param int $sleepTime Seconds to sleep after operations (default: 0)
	 * @return void
	 */
	private function purgeCacheDirectoryWithFallback(string $fallbackCommand, string $cacheDirectory, int $sleepTime = 0): void
	{
		try {
			if (File::isDirectory($cacheDirectory)) {
				// Remove the cache directory (Using a fast method or algorithm)
				system('rm -rf ' . escapeshellarg($cacheDirectory));
				if (is_int($sleepTime) && $sleepTime > 0) {
					sleep($sleepTime);
				}
			}
			
			// Re-create the cache directory (If not exists)
			$this->createCacheDirectory($cacheDirectory);
		} catch (Throwable $e) {
			// Re-create the cache directory (If not exists)
			$result = $this->createCacheDirectory($cacheDirectory);
			if (!$result) {
				Artisan::call($fallbackCommand);
				if (is_int($sleepTime) && $sleepTime > 0) {
					sleep($sleepTime);
				}
			}
		}
	}
	
	/**
	 * Re-create the cache directory (If not exists)
	 *
	 * @param $cacheDirectory
	 * @return bool
	 */
	private function createCacheDirectory($cacheDirectory): bool
	{
		$result = false;
		
		// Re-create the cache directory (If not exists)
		clearstatcache(); // <= Clears file status cache
		if (!File::isDirectory($cacheDirectory)) {
			File::makeDirectory($cacheDirectory, 0777, false, true);
			$result = true;
		}
		
		// Check if the .gitignore file exists in the root directory to prevent its removal
		clearstatcache(); // <= Clears file status cache
		$gitIgnoreFilePath = $cacheDirectory . '.gitignore';
		if (!File::exists($gitIgnoreFilePath)) {
			$content = '*' . "\n";
			$content .= '!.gitignore' . "\n";
			File::put($gitIgnoreFilePath, $content);
		}
		
		return $result;
	}
}
