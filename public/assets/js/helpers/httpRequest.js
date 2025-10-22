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
 * Makes an HTTP request using the Fetch API with configurable options.
 *
 * Example usage:
 * httpRequest('GET', 'https://api.example.com/data')
 *   .then((result) => console.log(result))
 *   .catch((error) => console.error(error.message, error.response));
 *
 * @param {string} method - HTTP method (e.g., 'GET', 'POST', 'PUT', 'DELETE').
 * @param {string} [url=''] - The URL to send the request to.
 * @param {Object|FormData} [data={}] - Request body data (object or FormData).
 * @param {Object} [headers={}] - Custom headers to include in the request.
 * @param {Object} [options={}] - Additional fetch options to override defaults.
 * @returns {Promise<any>} - Resolves with the response data or rejects with an error.
 */
async function httpRequest(method, url = '', data = {}, headers = {}, options = {}) {
	// Define HTTP methods that typically don’t include a body
	const readableRequestMethods = ['GET', 'HEAD'];
	// Define methods that should not be cached
	const nonCacheableRequestMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];
	
	// Normalize method to uppercase for consistency
	method = method.toUpperCase();
	
	// Prepare default headers
	const defaultHeaders = {
		'X-Requested-With': 'XMLHttpRequest',
		// Only set Content-Type if not overridden and not FormData
		...(data instanceof FormData ? {} : {'Content-Type': 'application/json'}),
	};
	
	// Optionally add CSRF token if available in a browser environment
	if (typeof document !== 'undefined') {
		const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
		if (csrfTokenEl && csrfTokenEl.getAttribute('content')) {
			defaultHeaders['X-CSRF-TOKEN'] = csrfTokenEl.getAttribute('content');
		}
	}
	
	// Merge default headers with custom headers (custom headers take precedence)
	const mergedHeaders = {...defaultHeaders, ...headers};
	
	// Prepare the body: skip serialization if FormData, otherwise stringify to JSON
	let body = (data instanceof FormData)
		? data
		: (
			(typeof data === 'object' && Object.keys(data).length > 0)
				? JSON.stringify(data)
				: undefined
		);
	
	// Set cache policy based on method
	const cache = nonCacheableRequestMethods.includes(method) ? 'no-cache' : 'default';
	
	// Default fetch options
	const defaultOptions = {
		method: method, // HTTP method: *GET, POST, PUT, DELETE, etc.
		mode: 'cors', // Cross-origin resource sharing mode: no-cors, *cors, same-origin
		cache: cache, // Cache control: *default, no-cache, reload, force-cache, only-if-cached
		credentials: 'same-origin', // Credentials mode: include, *same-origin, omit
		headers: mergedHeaders, // Combined headers
		redirect: 'follow', // Redirect behavior: manual, *follow, error
		/*
		 * Referrer policy
		 * Possible values:
		 * no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin,
		 * same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
		 */
		referrerPolicy: 'no-referrer',
		body, // Request body
	};
	
	// Remove body for methods that don’t support it
	if (readableRequestMethods.includes(method)) {
		delete defaultOptions.body;
	}
	
	// Merge default options with user-provided options
	const finalOptions = {...defaultOptions, ...options};
	
	try {
		// Execute the fetch request
		const response = await fetch(url, finalOptions);
		
		// Attempt to parse response as JSON, fall back to text if it fails
		let result;
		try {
			result = await response.json();
		} catch (e) {
			result = await response.text();
		}
		
		// Check if the response indicates an error
		if (!response.ok) {
			const defaultMessage = 'Network response was not OK';
			const message = (typeof result === 'object' && result.message) || response.statusText || defaultMessage;
			const errorData = {
				success: false,
				message,
				status: response.status || 500,
				...(typeof result === 'object' && result.error ? {error: result.error} : {}),
			};
			const error = new Error(message);
			error.response = errorData;
			throw error;
		}
		
		return result;
	} catch (error) {
		// Re-throw the error for downstream handling
		throw error;
	}
}

// Export for use in modules (optional, depending on environment)
if (typeof module !== 'undefined' && module.exports) {
	module.exports = httpRequest;
}
