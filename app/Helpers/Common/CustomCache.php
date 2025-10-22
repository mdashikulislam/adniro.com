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

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

class CustomCache
{
	/**
	 * Cache tags.
	 *
	 * @var array|null
	 */
	protected ?array $tags = null;
	
	public function __construct(?array $tags = null)
	{
		$this->tags = $tags;
	}
	
	/**
	 * Specify cache tags.
	 *
	 * @param array $tags
	 * @return $this
	 */
	public function tags(array $tags): self
	{
		$this->tags = $tags;
		
		return $this;
	}
	
	/**
	 * Store an item in the cache.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param \DateInterval|\DateTimeInterface|int|\Closure|null $ttl
	 * @return bool
	 */
	public function put(string $key, mixed $value, DateInterval|DateTimeInterface|int|Closure|null $ttl = null): bool
	{
		if ($this->supportsTags() && $this->tags) {
			return Cache::tags($this->tags)->put($key, $value, $ttl);
		} else {
			$key = $this->taggedKey($key);
			
			return Cache::put($key, $value, $ttl);
		}
	}
	
	/**
	 * Retrieve an item from the cache.
	 *
	 * @param string $key
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		if ($this->supportsTags() && $this->tags) {
			return Cache::tags($this->tags)->get($key, $default);
		}
		
		$key = $this->taggedKey($key);
		
		return Cache::get($key, $default);
	}
	
	/**
	 * Remember data with optional tags support.
	 *
	 * @param string $key
	 * @param \DateInterval|\DateTimeInterface|int|\Closure|null $ttl
	 * @param \Closure $callback
	 * @return mixed
	 */
	public function remember(string $key, DateInterval|DateTimeInterface|int|Closure|null $ttl, Closure $callback): mixed
	{
		if ($this->supportsTags()) {
			return Cache::tags($this->tags)->remember($key, $ttl, $callback);
		}
		
		// For drivers that don't support tags, include tags in the key
		$key = $this->taggedKey($key);
		
		return Cache::remember($key, $ttl, $callback);
	}
	
	/**
	 * Remove an item from the cache.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function forget(string $key): bool
	{
		if ($this->supportsTags() && $this->tags) {
			return Cache::tags($this->tags)->forget($key);
		}
		
		// For drivers that don't support tags, use the tagged key
		$key = $this->taggedKey($key);
		
		return Cache::forget($key);
	}
	
	/**
	 * Flush all cache or specific tags.
	 *
	 * @return void
	 */
	public function flush(): void
	{
		if ($this->supportsTags() && $this->tags) {
			Cache::tags($this->tags)->flush();
		} else {
			Cache::flush();
		}
	}
	
	/**
	 * Check if the current cache driver supports tags.
	 *
	 * @return bool
	 */
	public function supportsTags(): bool
	{
		$store = Cache::getStore();
		
		return method_exists($store, 'tags');
	}
	
	/**
	 * Generate a unique key for drivers without tag support.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function taggedKey(string $key): string
	{
		if (!$this->tags) {
			return $key;
		}
		
		$tagPrefix = implode('|', $this->tags);
		
		return "{$tagPrefix}:{$key}";
	}
}
