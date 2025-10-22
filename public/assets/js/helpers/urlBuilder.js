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

/**
 * UrlBuilder Helper Function
 *
 * @param {string|null} url
 * @param {boolean} allowNull
 * @param {boolean|null} secure
 * @returns {UrlBuilder}
 */
function urlBuilder(url = null, allowNull = false, secure = null) {
	return new UrlBuilder(url, allowNull, secure);
}

/*
 * UrlBuilder - URL Manipulation Helper (JavaScript version)
 *
 * DESCRIPTION
 * ------------
 * This JavaScript class replicates LaraClassifier's UrlBuilder helper for building,
 * parsing, and manipulating URLs and query parameters.
 *
 * NOTE:
 * - Uses standard browser `URL` and `URLSearchParams` APIs.
 * - Behaves similarly to the PHP version, with JS-native conventions.
 */
class UrlBuilder {
	/**
	 * @param {string|null} url     - The full (or partial) URL. If null, defaults to current browser URL.
	 * @param {boolean} allowNull   - If true and url is empty, allow null results instead of defaulting to current URL
	 * @param {boolean|null} secure - Whether to force https (not used in browser context)
	 */
	constructor(url = null, allowNull = false, secure = null) {
		this.allowNull = allowNull;
		this.url = null;
		this.parsedUrl = {};
		this.parameters = {};
		
		// Get URL (Accepts URL & URI|Path)
		if (!url) {
			if (allowNull) {
				this.url = null;
				this.parsedUrl = {};
				
				return; // Early exit: no further processing
			}
			// Default to current browser URL
			this.url = window.location.href;
		} else {
			this.url = url.toLowerCase().startsWith('http') ? url : this._toAbsoluteUrl(url, secure);
		}
		
		// Parse URL and parameters (Only if the URL is not empty)
		// ---
		// Parse the URL
		this.parsedUrl = this._parseUrl(this.url);
		
		// Parse the URL's parameters
		this.parameters = this._parseQuery(this.parsedUrl);
		
		// Remove empty parameters
		this._removeEmptyParameters();
		
		// Allow subclasses to add project-specific logic
		this._applyProjectSpecificRules();
	}
	
	/* ---------------------------------------------------------------------------
	 * PARAMETER METHODS
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Set (add or update) the given query parameters
	 *
	 * Note: Supports array dot notation
	 *
	 * @param parameters
	 * @returns {UrlBuilder}
	 */
	setParameters(parameters = {}) {
		for (const [key, value] of Object.entries(parameters)) {
			this._setDeepValue(this.parameters, key, value);
		}
		
		// Remove all empty query parameters
		this._removeEmptyParameters();
		
		return this;
	}
	
	/**
	 * Remove a single parameter by key (supports dot-notation)
	 *
	 * @param {string} parameterKey
	 * @returns {this}
	 */
	removeParameter(parameterKey) {
		return this.removeParameters([parameterKey]);
	}
	
	/**
	 * Remove some query parameters (supports dot notation)
	 *
	 * @param parameterKeys
	 * @returns {UrlBuilder}
	 */
	removeParameters(parameterKeys = []) {
		// Remove empty elements
		parameterKeys = parameterKeys.filter(p => p);
		
		// Remove the parameters
		for (const key of parameterKeys) {
			this._deleteDeepValue(this.parameters, key);
		}
		
		return this;
	}
	
	/**
	 * Remove all the query parameters
	 *
	 * @returns {this}
	 */
	removeAllParameters() {
		this.parameters = {};
		
		return this;
	}
	
	/* ---------------------------------------------------------------------------
	 * PARAMETER CHECKS/GETTERS
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Check if a single parameter exists.
	 *
	 * @param {string} parameterKey
	 * @returns {boolean}
	 */
	hasParameter(parameterKey) {
		const value = this.getParameter(parameterKey);
		
		return (value !== undefined && value !== null && value !== '');
	}
	
	/**
	 * Check if ALL listed parameters exist.
	 *
	 * @param parameterKeys
	 * @returns {boolean}
	 */
	hasParameters(parameterKeys = []) {
		for (const key of parameterKeys) {
			if (!this.hasParameter(key)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Throw an error if the parameter is missing; otherwise return its value.
	 *
	 * @param parameterKey
	 * @returns {Array|string}
	 * @throws {Error}
	 */
	requireParameter(parameterKey) {
		const value = this.getParameter(parameterKey);
		if (value === null) {
			throw new Error(`Parameter "${parameterKey}" is required but missing.`);
		}
		
		return value;
	}
	
	/**
	 * Get a single parameter's value or null if not found.
	 *
	 * @param {string} parameterKey
	 * @returns {*|null}
	 */
	getParameter(parameterKey) {
		const value = this._getDeepValue(this.parameters, parameterKey);
		
		return (value !== undefined) ? value : null;
	}
	
	/**
	 * Get specific query parameters (if they exist)
	 *
	 * Note: Supports array dot notation
	 *
	 * @param parameterKeys
	 * @returns {{}}
	 */
	getParameters(parameterKeys = []) {
		const result = {};
		for (const key of parameterKeys) {
			const value = this._getDeepValue(this.parameters, key);
			if (value !== undefined) {
				this._setDeepValue(result, key, value);
			}
		}
		
		return result;
	}
	
	/**
	 * Get query parameters by excluding some ones
	 *
	 * Note: Supports array dot notation
	 *
	 * @param parameterKeys
	 * @returns {*}
	 */
	getParametersExcluding(parameterKeys = []) {
		const filteredParameters = this._deepClone(this.parameters);
		
		for (const key of parameterKeys) {
			this._deleteDeepValue(filteredParameters, key);
		}
		
		return filteredParameters;
	}
	
	/**
	 * Get all the query parameters
	 *
	 * @returns {*|{}}
	 */
	getAllParameters() {
		return this.parameters;
	}
	
	/* ---------------------------------------------------------------------------
	 * URL MANIPULATION
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Get the current path component of the URL.
	 *
	 * @returns {string}
	 */
	getPath() {
		return this.parsedUrl.path ?? '';
	}
	
	/**
	 * Set the path component of the URL.
	 *
	 * @param {string} path
	 * @returns {this}
	 */
	setPath(path) {
		this.parsedUrl.path = path;
		
		return this;
	}
	
	/**
	 * Set the URL path (pathname) using only the first N segments.
	 *
	 * Truncate the URL path to only the first N segments.
	 * i.e. Keep only the first N segments of the URL path.
	 *
	 * Example:
	 * URL: https://example.com/foo/bar/baz
	 * $numberOfSegments = 2 → Path becomes "/foo/bar"
	 *
	 * @param {number} numberOfSegments - Number of path segments to keep
	 * @returns {UrlBuilder}
	 */
	keepFirstPathSegments(numberOfSegments) {
		const path = this.parsedUrl.path ?? '';
		
		// Split path by '/' and filter out empty strings
		const segments = path.split('/').filter(segment => segment !== '');
		
		// Take first N segments
		const firstNSegments = segments.slice(0, numberOfSegments);
		
		// Build a clean normalized path
		const newPath = firstNSegments.join('/');
		this.parsedUrl.path = '/' + newPath.replace(/^\/+/, '');
		
		return this;
	}
	
	/**
	 * Get the host component of the URL.
	 *
	 * @returns {string}
	 */
	getHost() {
		return this.parsedUrl.host ?? '';
	}
	
	/**
	 * Set the host component of the URL.
	 *
	 * @param {string} host
	 * @returns {this}
	 */
	setHost(host) {
		this.parsedUrl.host = host;
		
		return this;
	}
	
	/**
	 * Get the scheme component of the URL.
	 *
	 * @return {string}
	 */
	getScheme() {
		return this.parsedUrl.scheme ?? '';
	}
	
	/**
	 * Set the scheme component of the URL.
	 *
	 * @param {string} scheme
	 * @return {this}
	 */
	setScheme(scheme) {
		// Remove any trailing '://' if provided
		scheme = scheme.replace(/:?\/*$/, '');
		this.parsedUrl.scheme = scheme;
		
		if (!['http', 'https'].includes(this.parsedUrl.scheme.toLowerCase())) {
			throw new Error(`Invalid scheme: ${scheme}. Must be 'http' or 'https'.`);
		}
		
		return this;
	}
	
	/**
	 * Force HTTPS scheme.
	 *
	 * @return {this}
	 */
	forceHttps() {
		return this.setScheme('https');
	}
	
	/**
	 * Force HTTP scheme.
	 *
	 * @return {this}
	 */
	forceHttp() {
		return this.setScheme('http');
	}
	
	/**
	 * Get the port component of the URL.
	 *
	 * @return {number|null}
	 */
	getPort() {
		return this.parsedUrl.port !== undefined ? this.parsedUrl.port : null;
	}
	
	/**
	 * Set the port component of the URL.
	 *
	 * @param {number} port
	 * @return {this}
	 */
	setPort(port) {
		this.parsedUrl.port = parseInt(port);
		
		return this;
	}
	
	/**
	 * Remove the port component of the URL.
	 *
	 * @return {this}
	 */
	removePort() {
		delete this.parsedUrl.port;
		
		return this;
	}
	
	/**
	 * Check if the URL has a custom port (not default 80/443).
	 *
	 * @return {boolean}
	 */
	hasCustomPort() {
		if (this.parsedUrl.port === undefined) {
			return false;
		}
		
		const scheme = this.parsedUrl.scheme || 'http';
		const port = this.parsedUrl.port;
		
		// Check if port differs from default for the scheme
		return !((scheme === 'http' && port === 80) || (scheme === 'https' && port === 443));
	}
	
	/**
	 * Get the fragment/hash (without '#').
	 *
	 * @returns {string}
	 */
	getFragment() {
		return this.parsedUrl.fragment ?? '';
	}
	
	/**
	 * Set the fragment/hash (without '#').
	 *
	 * @param {string} fragment
	 * @returns {this}
	 */
	setFragment(fragment = '') {
		// Just in case, strip any leading '#' characters
		fragment = fragment.replace(/^#+/, '');
		this.parsedUrl.fragment = fragment;
		
		return this;
	}
	
	/**
	 * Remove the fragment/hash from the URL.
	 *
	 * @return {this}
	 */
	removeFragment() {
		delete this.parsedUrl.fragment;
		
		return this;
	}
	
	/**
	 * Clone the current UrlBuilder instance as a new object with the same data.
	 *
	 * @return {UrlBuilder}
	 */
	clone() {
		// Re-instantiate with the same URL (including current parameters)
		return new this.constructor(this.buildUrl());
	}
	
	/* ---------------------------------------------------------------------------
	 * URL BUILDING
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Build and return the absolute URL with current query parameters.
	 *
	 * @returns {string|null}
	 */
	buildUrl() {
		if (this.allowNull && this.url === null) {
			return null;
		}
		
		let modifiedUrl = `${this.parsedUrl.scheme}://${this.parsedUrl.host}`;
		
		if (this.parsedUrl.port) {
			modifiedUrl += `:${this.parsedUrl.port}`;
		}
		
		if (this.parsedUrl.path) {
			modifiedUrl += this.parsedUrl.path;
		}
		
		const query = new URLSearchParams();
		this._objectToSearchParams(this.parameters, query);
		
		const queryString = query.toString();
		if (queryString) {
			modifiedUrl += `?${queryString}`;
		}
		
		if (this.parsedUrl.fragment) {
			modifiedUrl += `#${this.parsedUrl.fragment}`;
		}
		
		return modifiedUrl;
	}
	
	/**
	 * Build and return a relative URL (pathname + ?query + #fragment).
	 *
	 * @returns {string|null}
	 */
	getRelativeUrl() {
		if (this.allowNull && this.url === null) {
			return null;
		}
		
		const path = this.parsedUrl.path ?? '';
		
		const query = new URLSearchParams();
		this._objectToSearchParams(this.parameters, query);
		
		let relativeUrl = path;
		
		const queryString = query.toString();
		if (queryString) {
			relativeUrl += `?${queryString}`;
		}
		
		if (this.parsedUrl.fragment) {
			relativeUrl += `#${this.parsedUrl.fragment}`;
		}
		
		return relativeUrl;
	}
	
	/* ---------------------------------------------------------------------------
	 * OBJECT TO STRING
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Build the URL string, or null if empty.
	 * Alias for buildUrl().
	 *
	 * @return {string|null}
	 */
	value() {
		return this.buildUrl();
	}
	
	/**
	 * Get the string representation of the URL.
	 *
	 * @return {string}
	 */
	toString() {
		const url = this.buildUrl();
		
		// Fallback to empty string to avoid errors
		return url ?? '';
	}
	
	/* ---------------------------------------------------------------------------
	 * UTILITY METHODS (Private)
	 * -------------------------------------------------------------------------*/
	
	/**
	 * Convert relative URL to absolute URL
	 *
	 * @param path
	 * @param secure
	 * @returns {string}
	 * @private
	 */
	_toAbsoluteUrl(path, secure = null) {
		const protocol = (secure === true) ? 'https:' : ((secure === false) ? 'http:' : window.location.protocol);
		const host = window.location.host;
		
		// Ensure path starts with '/'
		const normalizedPath = path.startsWith('/') ? path : '/' + path;
		
		return `${protocol}//${host}${normalizedPath}`;
	}
	
	/**
	 * Parse the URL
	 *
	 * @param url
	 * @returns {{scheme: string, host: string, port: number|undefined, path: string, query: string, fragment: string}|{}}
	 * @private
	 */
	_parseUrl(url) {
		let parsedUrl;
		
		try {
			const urlObj = new URL(url);
			parsedUrl = {
				scheme: urlObj.protocol.replace(':', ''),
				host: urlObj.hostname,
				port: urlObj.port ? parseInt(urlObj.port) : undefined,
				path: urlObj.pathname,
				query: urlObj.search.substring(1), // Remove leading '?'
				fragment: urlObj.hash.substring(1) // Remove leading '#'
			};
		} catch (e) {
			parsedUrl = {};
		}
		
		return parsedUrl;
	}
	
	/**
	 * Parse query string into object
	 *
	 * @private
	 */
	_parseQuery(parsedUrl) {
		const params = {};
		
		// Use the URLSearchParams's "searchParams" object
		if (parsedUrl.searchParams) {
			const searchParams = parsedUrl.searchParams;
			for (const [key, value] of searchParams.entries()) {
				this._setDeepValue(params, key, value);
			}
			
			return params;
		}
		
		// Use the URLSearchParams's "search" string (containing in parsedUrl.query)
		if (!parsedUrl.query) {
			return params;
		}
		
		const searchParams = new URLSearchParams(parsedUrl.query);
		for (const [key, value] of searchParams.entries()) {
			this._setDeepValue(params, key, value);
		}
		
		return params;
	}
	
	/**
	 * Remove all the query parameters which value is empty
	 *
	 * @private
	 */
	_removeEmptyParameters() {
		this.parameters = this._removeEmptyRecursive(this.parameters);
	}
	
	/**
	 * Remove all empty query parameters recursively
	 *
	 * Note: "Empty" means null, empty string, or empty array. But NOT 0, "0", or false.
	 *
	 * @param obj
	 * @returns {{}|*}
	 * @private
	 */
	_removeEmptyRecursive(obj) {
		if (Array.isArray(obj)) {
			return obj
			.map(item => typeof item === 'object' && item !== null ? this._removeEmptyRecursive(item) : item)
			.filter(item => {
				if (typeof item === 'object' && item !== null) {
					return Array.isArray(item) ? item.length > 0 : Object.keys(item).length > 0;
				}
				// Keep numeric zeros (0, 0.0, '0') but remove truly empty values
				return item !== '' && item !== null && item !== undefined;
			});
		}
		
		if (typeof obj === 'object' && obj !== null) {
			const filtered = {};
			for (const [key, value] of Object.entries(obj)) {
				if (typeof value === 'object' && value !== null) {
					const cleaned = this._removeEmptyRecursive(value);
					const isEmpty = Array.isArray(cleaned) ? cleaned.length === 0 : Object.keys(cleaned).length === 0;
					if (!isEmpty) {
						filtered[key] = cleaned;
					}
				} else if (value !== '' && value !== null && value !== undefined) {
					// Keep numeric zeros (0, 0.0, '0') but remove truly empty values
					filtered[key] = value;
				}
			}
			return filtered;
		}
		
		return obj;
	}
	
	/**
	 * Convert a nested object into URLSearchParams (like Laravel's Arr::query).
	 *
	 * @param obj
	 * @param searchParams
	 * @param parentKey
	 * @private
	 */
	_objectToSearchParams(obj, searchParams, parentKey = '') {
		if (obj && typeof obj === 'object' && !Array.isArray(obj)) {
			for (const [key, val] of Object.entries(obj)) {
				const newKey = parentKey ? `${parentKey}[${key}]` : key;
				this._objectToSearchParams(val, searchParams, newKey);
			}
		} else if (Array.isArray(obj)) {
			obj.forEach((val, index) => {
				const newKey = `${parentKey}[${index}]`;
				this._objectToSearchParams(val, searchParams, newKey);
			});
		} else {
			searchParams.append(parentKey, obj);
		}
	}
	
	/**
	 * Get nested value using dot notation
	 * e.g. "user.name" => obj.user.name
	 *
	 * @param obj
	 * @param keyPath
	 * @returns {undefined|*}
	 * @private
	 */
	_getDeepValue(obj, keyPath) {
		const keys = keyPath.split('.');
		let current = obj;
		for (const k of keys) {
			if (current === undefined || current === null || typeof current !== 'object') {
				return undefined;
			}
			current = current[k];
		}
		return current;
	}
	
	/**
	 * Set nested value using dot notation
	 * e.g. ("user.name", "Alice") => obj.user.name = "Alice"
	 *
	 * @param obj
	 * @param keyPath
	 * @param value
	 * @private
	 */
	_setDeepValue(obj, keyPath, value) {
		const keys = keyPath.split('.');
		let current = obj;
		while (keys.length > 1) {
			const k = keys.shift();
			if (current[k] === undefined || current[k] === null || typeof current[k] !== 'object') {
				current[k] = {};
			}
			current = current[k];
		}
		current[keys[0]] = value;
	}
	
	/**
	 * Delete nested value using dot notation
	 * e.g. "user.name" => delete obj.user.name
	 *
	 * @param obj
	 * @param keyPath
	 * @private
	 */
	_deleteDeepValue(obj, keyPath) {
		const keys = keyPath.split('.');
		let current = obj;
		while (keys.length > 1) {
			const k = keys.shift();
			if (current[k] === undefined || current[k] === null || typeof current[k] !== 'object') {
				return;
			}
			current = current[k];
		}
		delete current[keys[0]];
	}
	
	/**
	 * Simple deep clone via JSON (not suitable for functions, Dates, etc.).
	 *
	 * @param value
	 * @returns {any}
	 * @private
	 */
	_deepClone(value) {
		return JSON.parse(JSON.stringify(value));
	}
	
	/**
	 * Apply project-specific logic (intentionally empty in base class).
	 *
	 * Extend this method in subclasses to customize cleanup behavior.
	 *
	 * @return {void}
	 * @protected
	 */
	_applyProjectSpecificRules() {
		// No-op
	}
}

// Export for Node.js/ES6 modules
if (typeof module !== 'undefined' && module.exports) {
	// module.exports = UrlBuilder;
}

/*
 ========================================================================================
 Usage Examples
 ----------------------------------------------------------------------------------------
 Example 1: Basic Usage
 ----------------------------------------------------------------------------------------
 // Current page URL: https://example.com?distance=0&foo=bar
 const urlBuilder = new UrlBuilder();
 
 // Check if a parameter exists
 console.log(urlBuilder.hasParameter('foo'));         // true
 console.log(urlBuilder.getParameter('foo')); // "bar"
 
 // Remove a parameter
 urlBuilder.removeParameters(['foo']);
 console.log(urlBuilder.buildUrl());
 // => "https://example.com?distance=0" (assuming hash/fragment was empty)
 
 ----------------------------------------------------------------------------------------
 Example 2: Dot Notation
 ----------------------------------------------------------------------------------------
 // Suppose the current URL is: https://example.com?user[name]=Alice&user[role]=admin
 const urlBuilder = new UrlBuilder();
 
 // Dot notation used for read/update
 console.log(urlBuilder.getParameter('user.name')); // "Alice"
 urlBuilder.setParameters({ 'user.name': 'Bob' });
 console.log(urlBuilder.getParameter('user.name')); // "Bob"
 
 ----------------------------------------------------------------------------------------
 Example 3: Forcing HTTPS, Relative URL, and Cloning
 ----------------------------------------------------------------------------------------
 // Start with an http URL
 const secureUrl = new UrlBuilder('http://example.org?foo=bar', false, true);
 console.log(secureUrl.toString());
 // => "https://example.org?foo=bar"
 
 // Using toString with allowEmpty
 const emptyUrl = new UrlBuilder(null);
 console.log(emptyUrl.toString()); // => current browser URL
 
 const emptyUrl = new UrlBuilder(null, true);
 console.log(emptyUrl.value());    // => null
 console.log(emptyUrl.toString()); // => "" (empty string)
 
 // Build a relative URL (pathname + ?query + #fragment)
 secureUrl.setPath('/products');
 secureUrl.setFragment('details');
 console.log(secureUrl.buildRelativeUrl());
 // => "/products?foo=bar#details"
 
 // Clone and modify the clone
 const cloneUrl = secureUrl.clone();
 cloneUrl.removeParameters(['foo']);
 
 or
 
 // Remove a single parameter by name
 urlBuilder.removeParameter('foo');
 
 console.log(cloneUrl.buildUrl());
 // => "https://example.org/products#details"
 // (original "secureUrl" still has ?foo=bar)
 
 // If you remove an unused or nonexistent parameter, it simply does nothing
 urlBuilder.removeParameter('unknownParam');
 
 ----------------------------------------------------------------------------------------
 Example 4: requireParameter
 ----------------------------------------------------------------------------------------
 // If the query is missing "token", throw an error:
 try {
 const token = urlBuilder.requireParameter('token');
 console.log('Token is:', token);
 } catch (err) {
 console.error(err.message);
 // => "Parameter "token" is required but missing."
 }
 ========================================================================================
 */
