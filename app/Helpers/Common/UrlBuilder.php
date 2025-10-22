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

class UrlBuilder
{
	protected ?string $url;
	protected array $parsedUrl;
	protected array $parameters;
	protected bool $allowNull;
	
	/**
	 * @param string|null $url
	 * @param bool $allowNull If true and $url is empty, allow null results instead of defaulting to request URL
	 * @param null $secure
	 */
	public function __construct(?string $url = null, bool $allowNull = false, $secure = null)
	{
		$this->allowNull = $allowNull;
		
		// Get URL (Accepts URL & URI|Path)
		if (empty($url)) {
			if ($allowNull) {
				$this->url = null;
				$this->parsedUrl = [];
				
				return; // Early exit: no further processing
			}
			$this->url = request()->fullUrl();
		} else {
			$this->url = str_starts_with(mb_strtolower($url), 'http') ? $url : url($url, [], $secure);
		}
		
		// Parse URL and parameters (Only if the URL is not empty)
		// ---
		// Parse the URL
		$parsedUrl = mb_parse_url($this->url);
		$this->parsedUrl = is_array($parsedUrl) ? $parsedUrl : [];
		
		// Parse the URL's parameters
		$this->parameters = [];
		if (isset($this->parsedUrl['query'])) {
			mb_parse_str($this->parsedUrl['query'], $this->parameters);
		}
		
		// Remove all empty query parameters
		$this->removeEmptyParameters();
		
		// Allow subclasses to add project-specific logic
		$this->applyProjectSpecificRules();
	}
	
	/**
	 * Apply project-specific logic (intentionally empty in base class).
	 *
	 * Extend this method in subclasses to customize cleanup behavior.
	 *
	 * @return void
	 */
	protected function applyProjectSpecificRules(): void
	{
		// No-op
	}
	
	/* ---------------------------------------------------------------------------
	 * PARAMETER METHODS
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Set (add or update) the given query parameters
	 *
	 * Note: Supports array dot notation
	 *
	 * @param array<string, string|array> $parameters
	 * @return $this
	 */
	public function setParameters(array $parameters): static
	{
		foreach ($parameters as $key => $value) {
			Arr::set($this->parameters, $key, $value);
		}
		
		// Remove all empty query parameters
		$this->removeEmptyParameters();
		
		return $this;
	}
	
	/**
	 * Remove a single parameter by key.
	 *
	 * @param string $parameterKey
	 * @return $this
	 */
	public function removeParameter(string $parameterKey): static
	{
		return $this->removeParameters([$parameterKey]);
	}
	
	/**
	 * Remove some query parameters
	 *
	 * Note: Supports array dot notation
	 *
	 * @param array<int, string> $parameters
	 * @return $this
	 */
	public function removeParameters(array $parameters): static
	{
		// Remove empty elements
		$parameters = array_filter($parameters);
		
		// Remove the parameters
		foreach ($parameters as $parameter) {
			Arr::forget($this->parameters, $parameter);
		}
		
		return $this;
	}
	
	/**
	 * Remove all the query parameters
	 *
	 * @return $this
	 */
	public function removeAllParameters(): static
	{
		$this->parameters = [];
		
		return $this;
	}
	
	/**
	 * Remove all the query parameters which value is empty
	 *
	 * @return void
	 */
	protected function removeEmptyParameters(): void
	{
		$this->parameters = $this->removeEmptyRecursive($this->parameters);
	}
	
	/**
	 * Remove all empty query parameters recursively
	 *
	 * Note: "Empty" means null, empty string, or empty array. But NOT 0, "0", or false.
	 *
	 * @param array $array
	 * @return array
	 */
	protected function removeEmptyRecursive(array $array): array
	{
		return array_filter($array, function ($value) {
			if (is_array($value)) {
				$value = $this->removeEmptyRecursive($value);
				
				return !empty($value); // Remove empty arrays
			}
			
			// Keep numeric zeros (0, 0.0, '0') but remove truly empty values
			return $value !== '' && $value !== null && $value !== [];
		}, ARRAY_FILTER_USE_BOTH);
	}
	
	/* ---------------------------------------------------------------------------
	 * PARAMETER CHECKS/GETTERS
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Check if a single parameter exists.
	 *
	 * @param string $parameterKey
	 * @return bool
	 */
	public function hasParameter(string $parameterKey): bool
	{
		return !empty($this->getParameter($parameterKey));
	}
	
	/**
	 * Check if ALL listed parameters exist.
	 *
	 * @param array $parameterKeys
	 * @return bool
	 */
	public function hasParameters(array $parameterKeys): bool
	{
		return !empty($this->getParameters($parameterKeys));
	}
	
	/**
	 * Throw an error if the parameter is missing; otherwise return its value.
	 *
	 * @param string $parameterKey
	 * @return array|string
	 * @throws \Exception
	 */
	public function requireParameter(string $parameterKey): array|string
	{
		$value = $this->getParameter($parameterKey);
		if (empty($value)) {
			throw new \Exception("Parameter '$parameterKey' is required but missing.");
		}
		
		return $value;
	}
	
	/**
	 * Get a single parameter's value or null if not found.
	 *
	 * @param string $parameterKey
	 * @return array|string|null
	 */
	public function getParameter(string $parameterKey): array|string|null
	{
		$value = $this->getParameters([$parameterKey]);
		
		return $value[$parameterKey] ?? null;
	}
	
	/**
	 * Get specific query parameters (if they exist)
	 *
	 * Note: Supports array dot notation
	 *
	 * @param array<int, string> $parameterKeys
	 * @return array<string, string|array>
	 */
	public function getParameters(array $parameterKeys): array
	{
		$result = [];
		foreach ($parameterKeys as $key) {
			$value = Arr::get($this->parameters, $key);
			if ($value !== null) {
				Arr::set($result, $key, $value);
			}
		}
		
		return $result;
	}
	
	/**
	 * Get query parameters by excluding some ones
	 *
	 * Note: Supports array dot notation
	 *
	 * @param array<int, string> $parameterKeys
	 * @return array<string, string|array>
	 */
	public function getParametersExcluding(array $parameterKeys): array
	{
		$filteredParameters = $this->parameters;
		
		foreach ($parameterKeys as $key) {
			Arr::forget($filteredParameters, $key);
		}
		
		return $filteredParameters;
	}
	
	/**
	 * Get all the query parameters
	 *
	 * @return array<string, string|array>
	 */
	public function getAllParameters(): array
	{
		return $this->parameters;
	}
	
	/* ---------------------------------------------------------------------------
	 * URL MANIPULATION
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Get the current path component of the URL.
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->parsedUrl['path'] ?? '';
	}
	
	/**
	 * Set the path component of the URL.
	 *
	 * @param string $path
	 * @return static
	 */
	public function setPath(string $path): static
	{
		$this->parsedUrl['path'] = $path;
		
		return $this;
	}
	
	/**
	 * Set the path component of the URL using only the first N segments.
	 *
	 * Truncate the URL path to only the first N segments.
	 * i.e. Keep only the first N segments of the URL path.
	 *
	 * Example:
	 * URL: https://example.com/foo/bar/baz
	 * $numberOfSegments = 2 → Path becomes "/foo/bar"
	 *
	 * @param int $numberOfSegments
	 * @return static
	 */
	public function keepFirstPathSegments(int $numberOfSegments): static
	{
		$path = $this->parsedUrl['path'] ?? '';
		
		$segments = explode('/', $path);
		$segments = collect($segments)
			->filter(fn ($item) => !empty($item))
			->toArray();
		$firstNSegments = array_slice($segments, 0, $numberOfSegments);
		
		// Build a clean normalized path
		$newPath = implode('/', $firstNSegments);
		$this->parsedUrl['path'] = '/' . ltrim($newPath, '/');
		
		return $this;
	}
	
	/**
	 * Get the host component of the URL.
	 *
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->parsedUrl['host'] ?? '';
	}
	
	/**
	 * Set the host component of the URL.
	 *
	 * @param string $host
	 * @return static
	 */
	public function setHost(string $host): static
	{
		$this->parsedUrl['host'] = $host;
		
		return $this;
	}
	
	/**
	 * Get the scheme component of the URL.
	 *
	 * @return string
	 */
	public function getScheme(): string
	{
		return $this->parsedUrl['scheme'] ?? '';
	}
	
	/**
	 * Set the scheme component of the URL.
	 *
	 * @param string $scheme
	 * @return static
	 */
	public function setScheme(string $scheme): static
	{
		// Remove any trailing '://' if provided
		$scheme = rtrim($scheme, ':/');
		
		if (!in_array(strtolower($scheme), ['http', 'https'])) {
			throw new \InvalidArgumentException("Invalid scheme: {$scheme}. Must be 'http' or 'https'.");
		}
		
		$this->parsedUrl['scheme'] = $scheme;
		
		return $this;
	}
	
	/**
	 * Force HTTPS scheme.
	 *
	 * @return static
	 */
	public function forceHttps(): static
	{
		//return $this->setScheme('https');
	}
	
	/**
	 * Force HTTP scheme.
	 *
	 * @return static
	 */
	public function forceHttp(): static
	{
		return $this->setScheme('http');
	}
	
	/**
	 * Get the port component of the URL.
	 *
	 * @return int|null
	 */
	public function getPort(): ?int
	{
		return isset($this->parsedUrl['port']) ? (int)$this->parsedUrl['port'] : null;
	}
	
	/**
	 * Set the port component of the URL.
	 *
	 * @param int $port
	 * @return static
	 */
	public function setPort(int $port): static
	{
		$this->parsedUrl['port'] = $port;
		
		return $this;
	}
	
	/**
	 * Remove the port component of the URL.
	 *
	 * @return static
	 */
	public function removePort(): static
	{
		unset($this->parsedUrl['port']);
		
		return $this;
	}
	
	/**
	 * Check if the URL has a custom port (not default 80/443).
	 *
	 * @return bool
	 */
	public function hasCustomPort(): bool
	{
		if (!isset($this->parsedUrl['port'])) {
			return false;
		}
		
		$scheme = $this->parsedUrl['scheme'] ?? 'http';
		$port = $this->parsedUrl['port'];
		
		// Check if port differs from default for the scheme
		return !(($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443));
	}
	
	/**
	 * Get the fragment/hash (without '#').
	 *
	 * @return string
	 */
	public function getFragment(): string
	{
		return $this->parsedUrl['fragment'] ?? '';
	}
	
	/**
	 * Set the fragment/hash (without '#').
	 *
	 * @param string $fragment
	 * @return static
	 */
	public function setFragment(string $fragment): static
	{
		// Just in case, strip any leading '#' characters
		$fragment = ltrim($fragment, '#');
		$this->parsedUrl['fragment'] = $fragment;
		
		return $this;
	}
	
	/**
	 * Remove the fragment/hash from the URL.
	 *
	 * @return static
	 */
	public function removeFragment(): static
	{
		unset($this->parsedUrl['fragment']);
		
		return $this;
	}
	
	/**
	 * Clone the current UrlQuery instance as a new object with the same data.
	 *
	 * @return static
	 */
	public function clone(): static
	{
		// Re-instantiate with the same URL (including current parameters)
		return new static($this->buildUrl());
	}
	
	/* ---------------------------------------------------------------------------
	 * URL BUILDING
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Build new URL with the updated query parameters
	 *
	 * @return string|null
	 */
	public function buildUrl(): ?string
	{
		if ($this->allowNull && $this->url === null) {
			return null;
		}
		
		$newQueryString = Arr::query($this->parameters);
		$modifiedUrl = $this->parsedUrl['scheme'] . '://' . $this->parsedUrl['host'];
		
		if (isset($this->parsedUrl['port'])) {
			$modifiedUrl .= ':' . $this->parsedUrl['port'];
		}
		
		if (isset($this->parsedUrl['path'])) {
			$modifiedUrl .= $this->parsedUrl['path'];
		}
		
		if ($newQueryString) {
			$modifiedUrl .= '?' . $newQueryString;
		}
		
		if (isset($this->parsedUrl['fragment'])) {
			$modifiedUrl .= '#' . $this->parsedUrl['fragment'];
		}
		
		return $modifiedUrl;
	}
	
	/**
	 * Get relative URL (path + query + fragment).
	 *
	 * @return string|null
	 */
	public function getRelativeUrl(): ?string
	{
		if ($this->allowNull && $this->url === null) {
			return null;
		}
		
		$path = $this->parsedUrl['path'] ?? '';
		$newQueryString = Arr::query($this->parameters);
		
		$relativeUrl = $path;
		
		if (!empty($newQueryString)) {
			$relativeUrl .= '?' . $newQueryString;
		}
		
		if (isset($this->parsedUrl['fragment'])) {
			$relativeUrl .= '#' . $this->parsedUrl['fragment'];
		}
		
		return $relativeUrl;
	}
	
	/* ---------------------------------------------------------------------------
	 * OBJECT TO STRING
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Build the URL string, or null if empty.
	 * Alias for buildUrl().
	 *
	 * @return string|null
	 */
	public function value(): ?string
	{
		return $this->buildUrl();
	}
	
	/**
	 * Get the string representation of the URL.
	 *
	 * @return string
	 */
	public function toString(): string
	{
		return (string)$this;  // Delegates to __toString()
	}
	
	/**
	 * Magic method to convert the object to a string.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		$url = $this->buildUrl();
		
		// Fallback to empty string to avoid __toString() fatal error
		return $url ?? '';
	}
}
