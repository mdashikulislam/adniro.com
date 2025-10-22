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
 * Executes a callback when the document is ready, optionally waiting for full page load.
 *
 * Note:
 * - Added { once: true } option - Prevents memory leaks by automatically removing event listeners after they fire once.
 * - Used queueMicrotask() - For immediate but non-blocking execution.
 *                           More performant than setTimeout(callback, 500) and maintains proper execution order.
 *
 * @param {Function} callback - The function to execute when the document is ready
 * @param {boolean} [isFullyLoaded=true] - If true, waits for all resources (images, styles, scripts) to load.
 *                                         If false, executes when DOM is interactive (DOMContentLoaded).
 * @throws {TypeError} If callback is not a function
 * @example
 * onDocumentReady(() => console.log('DOM ready'));
 * onDocumentReady(() => console.log('Fully loaded'), false);
 */
if (typeof window.onDocumentReady !== 'function') {
	function onDocumentReady(callback, isFullyLoaded = true) {
		// Validate callback
		if (typeof callback !== 'function') {
			throw new TypeError('Callback must be a function');
		}
		
		switch (document.readyState) {
			case "loading":
				// Document is still loading, attach appropriate event listener
				if (isFullyLoaded) {
					// Wait for full load
					window.addEventListener("load", callback, {once: true});
				} else {
					// Wait for DOM to be ready
					document.addEventListener("DOMContentLoaded", callback, {once: true});
				}
				break;
			
			case "interactive":
				// DOM is ready, but resources may still be loading
				if (isFullyLoaded) {
					// Still need to wait for full load
					// (Wait for all resources to load)
					window.addEventListener("load", callback, {once: true});
				} else {
					// DOM is ready, execute immediately
					queueMicrotask(callback);
				}
				break;
			
			case "complete":
				// Everything is loaded, execute immediately
				queueMicrotask(callback);
				break;
			
			default:
				// Fallback for unknown state
				if (isFullyLoaded) {
					window.addEventListener("load", callback, {once: true});
				} else {
					document.addEventListener("DOMContentLoaded", callback, {once: true});
				}
		}
	}
}

/*
 * Polyfill of queueMicrotask()
 */
if (typeof self.queueMicrotask !== 'function') {
	self.queueMicrotask = function (callback) {
		Promise.resolve()
		.then(callback)
		.catch((e) =>
			setTimeout(() => {
				throw e;
			}),
		);
	};
}
