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
 * CookieManager class for handling cookies in web projects.
 *
 * Usage Instructions:
 *
 * 1. Include the Script: Add cookieManager.js to your project via a <script> tag or import it as a module.
 *
 * <script src="cookieManager.js"></script>
 * Or in a module:
 * import CookieManager from './cookieManager.js';
 *
 * 2. Initialize
 *
 * const cookie = new CookieManager({
 *      expires: 60, // Default expiration: 60 minutes
 *      path: '/',
 *      secure: true,
 *      sameSite: 'Strict'
 * });
 *
 * 3. Examples
 *
 * // Set a cookie
 * cookie.setCookie('username', 'john_doe', 30);
 *
 * // Get a cookie
 * console.log(cookie.getCookie('username')); // 'john_doe'
 *
 * // Check if cookie exists
 * console.log(cookie.hasCookie('username')); // true
 *
 * // Update a cookie
 * cookie.updateCookie('username', 'jane_doe', 60);
 *
 * // Remove a cookie
 * cookie.removeCookie('username');
 *
 * // List all cookies
 * console.log(cookie.getAllCookies());
 *
 * // Clear all cookies
 * cookie.clearAllCookies();
 */
class CookieManager {
	/**
	 * Default cookie parameters.
	 * @private
	 */
	#defaults = {
		expires: null, // in minutes, null for session cookie
		path: '/',
		domain: '',
		secure: true,
		sameSite: 'Strict'
	};
	
	/**
	 * Initializes CookieManager with optional custom defaults.
	 * @param {Object} [customDefaults] - Custom default cookie parameters.
	 */
	constructor(customDefaults = {}) {
		this.#defaults = {...this.#defaults, ...customDefaults};
	}
	
	/**
	 * Validates if a value is non-empty (not null, undefined, or empty string).
	 * @private
	 * @param {*} value - Value to check.
	 * @returns {boolean} True if value is non-empty.
	 */
	#isFilled(value) {
		return value !== null && value !== undefined && value !== '';
	}
	
	/**
	 * Sets a cookie with the specified name, value, and options.
	 * @param {string} name - Cookie name.
	 * @param {string} value - Cookie value.
	 * @param {number|null} [expires] - Expiration in minutes (null for session cookie).
	 * @param {Object} [options] - Additional cookie options (path, domain, secure, sameSite).
	 */
	setCookie(name, value, expires = null, options = {}) {
		if (!this.#isFilled(name)) throw new Error('Cookie name is required.');
		
		const params = {...this.#defaults, ...options, expires};
		let cookieString = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`;
		
		if (this.#isFilled(params.expires)) {
			const date = new Date();
			date.setTime(date.getTime() + params.expires * 60 * 1000);
			cookieString += `; expires=${date.toUTCString()}`;
		}
		
		if (this.#isFilled(params.path)) cookieString += `; path=${params.path}`;
		if (this.#isFilled(params.domain)) cookieString += `; domain=${params.domain}`;
		if (params.secure) cookieString += '; secure';
		if (this.#isFilled(params.sameSite)) cookieString += `; SameSite=${params.sameSite}`;
		
		document.cookie = cookieString;
	}
	
	/**
	 * Gets the value of a cookie by name.
	 * @param {string} name - Cookie name.
	 * @returns {string|null} Cookie value or null if not found.
	 */
	getCookie(name) {
		if (!this.#isFilled(name)) return null;
		
		const encName = `${encodeURIComponent(name)}=`;
		const cookies = document.cookie.split(';');
		
		for (let cookie of cookies) {
			cookie = cookie.trim();
			if (cookie.startsWith(encName)) {
				return decodeURIComponent(cookie.substring(encName.length));
			}
		}
		
		return null;
	}
	
	/**
	 * Checks if a cookie exists and has a non-empty value.
	 * @param {string} name - Cookie name.
	 * @returns {boolean} True if cookie exists.
	 */
	hasCookie(name) {
		return this.#isFilled(this.getCookie(name));
	}
	
	/**
	 * Removes a cookie by setting its expiration to the past.
	 * @param {string} name - Cookie name.
	 * @param {Object} [options] - Options like path or domain to match the cookie.
	 */
	removeCookie(name, options = {}) {
		this.setCookie(name, '', -1, options);
	}
	
	/**
	 * Updates an existing cookie's value and/or options.
	 * @param {string} name - Cookie name.
	 * @param {string} newValue - New cookie value.
	 * @param {number|null} [expires] - New expiration in minutes.
	 * @param {Object} [options] - New cookie options.
	 * @returns {boolean} True if cookie was updated, false if it didn't exist.
	 */
	updateCookie(name, newValue, expires = null, options = {}) {
		if (!this.hasCookie(name)) return false;
		this.setCookie(name, newValue, expires, options);
		return true;
	}
	
	/**
	 * Lists all cookies as an object.
	 * @returns {Object} Object with cookie names as keys and values.
	 */
	getAllCookies() {
		const cookies = document.cookie.split(';');
		const result = {};
		
		for (let cookie of cookies) {
			cookie = cookie.trim();
			if (cookie) {
				const [name, value] = cookie.split('=').map(part => decodeURIComponent(part));
				result[name] = value;
			}
		}
		
		return result;
	}
	
	/**
	 * Clears all cookies.
	 * @param {Object} [options] - Options like path or domain to match cookies.
	 */
	clearAllCookies(options = {}) {
		const cookies = this.getAllCookies();
		for (const name in cookies) {
			this.removeCookie(name, options);
		}
	}
}

// Export for module-based projects or attach to window for global use
if (typeof module !== 'undefined' && module.exports) {
	module.exports = CookieManager;
} else {
	window.CookieManager = CookieManager;
}
