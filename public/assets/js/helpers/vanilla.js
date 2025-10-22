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

/* Polyfill (https://en.wikipedia.org/wiki/Polyfill_(programming)) */
/* Array.isArray() */
if (!Array.isArray) {
	Array.isArray = function (arg) {
		return Object.prototype.toString.call(arg) === '[object Array]';
	};
}

/* Number.isNaN() */
Number.isNaN = Number.isNaN || function (value) {
	return typeof value === 'number' && isNaN(value);
}

/* Number.isInteger() */
Number.isInteger = Number.isInteger || function (value) {
	return typeof value === 'number' && isFinite(value) && Math.floor(value) === value;
};

/* Number.isSafeInteger() */
if (!Number.MAX_SAFE_INTEGER) {
	Number.MAX_SAFE_INTEGER = 9007199254740991; /* Math.pow(2, 53) - 1; */
}
Number.isSafeInteger = Number.isSafeInteger || function (value) {
	return Number.isInteger(value) && Math.abs(value) <= Number.MAX_SAFE_INTEGER;
};

/* str.endsWith() */
if (!String.prototype.endsWith) {
	String.prototype.endsWith = function (searchString, position) {
		let subjectString = this.toString();
		if (typeof position !== 'number' || !isFinite(position) || Math.floor(position) !== position || position > subjectString.length) {
			position = subjectString.length;
		}
		position -= searchString.length;
		let lastIndex = subjectString.lastIndexOf(searchString, position);
		return lastIndex !== -1 && lastIndex === position;
	};
}

/* --- */

/**
 * Submit a form on click its submit button
 * @param formSelector
 * @param submitButtonSelector
 */
function setupFormSubmit(formSelector, submitButtonSelector) {
	const form = document.querySelector(formSelector);
	const submitButton = document.querySelector(submitButtonSelector);
	
	if (form && submitButton) {
		submitButton.addEventListener('click', (event) => {
			event.preventDefault();
			form.submit();
		});
	} else {
		console.error('Form or submit button not found');
	}
}

/**
 * During or after typing, Check if an input field changed
 * @param inputElement
 * @param callback
 */
function addInputChangeListeners(inputElement, callback) {
	// The 'input' event is triggered immediately whenever the value of the input field changes
	inputElement.addEventListener('input', (event) => callback(event));
	
	// The 'change' event is triggered when the input field loses focus after its value has been changed
	inputElement.addEventListener('change', (event) => callback(event));
}

/**
 * Prevent the page to load in IFRAME by redirecting it to the top-level window
 */
function preventPageLoadingInIframe() {
	try {
		if (window.top.location !== window.location) {
			window.top.location.replace(siteUrl);
		}
	} catch (e) {
		console.error(e);
	}
}

/**
 * Redirect URL
 * @param url
 */
function redirect(url) {
	window.location.replace(url);
	window.location.href = url;
}

/**
 * Raw URL encode
 * @param value
 * @returns {string}
 */
function rawurlencode(value) {
	value = (value + '').toString();
	
	return encodeURIComponent(value)
		.replace(/!/g, '%21')
		.replace(/'/g, '%27')
		.replace(/\(/g, '%28')
		.replace(/\)/g, '%29')
		.replace(/\*/g, '%2A');
}

/**
 * Check if variable is defined
 * @param value
 * @returns {boolean}
 */
function isDefined(value) {
	return (typeof value !== 'undefined');
}

/**
 * Check if variable is not defined
 * @param value
 * @returns {boolean}
 */
function isNotDefined(value) {
	return (typeof value === 'undefined');
}

/**
 * Check if pure JS DOM element is found (defined)
 * @param el
 * @returns {boolean}
 */
function isElDefined(el) {
	return (typeof el !== 'undefined' && el !== null);
}

/**
 * Check if pure JS DOM element is not found (not defined)
 * @param el
 * @returns {boolean}
 */
function isElNotDefined(el) {
	return !isElDefined(el);
}

/**
 * Check if variable is undefined, null, 0, or blank
 * @param value
 * @returns {boolean}
 */
function isEmpty(value) {
	if (isBlank(value)) {
		return true;
	}
	
	if (isNumeric(value, true)) {
		return value === 0 || value === '0';
	}
	
	return false;
}

/**
 * Check if variable is blank
 * Support: undefined, null, array, object, date, number and string
 *
 * @param value
 * @returns {boolean}
 */
function isBlank(value) {
	if (!isDefined(value) || value === null) {
		return true;
	}
	
	if (isArray(value)) {
		return value.length === 0;
	}
	
	if (value instanceof Date) {
		return false;
	}
	
	if (isObject(value)) {
		/* 'value' is a JS HTML element */
		if (isDefined(value.nodeName)) {
			return value.nodeName.length === 0;
		}
		
		/* 'value' is a jQuery HTML element */
		if (isDefined(value.get)) {
			return value.get(0).tagName.length === 0;
		}
		
		/* Classic JSON object */
		return Object.keys(value).length === 0;
	}
	
	return [''].includes(value);
}

/**
 * Check if variable is filled
 * @param value
 * @returns {boolean}
 */
function isFilled(value) {
	return !isBlank(value);
}

/**
 * Check if variable is blank or null
 *
 * @param value
 * @returns {boolean}
 */
function isBlankString(value) {
	return (isEmpty(value) || /^\s*$/.test(value));
}

/**
 * Check if variable is a string
 * @param value
 * @returns {boolean}
 */
function isString(value) {
	if (isDefined(value)) {
		return (typeof value === 'string' || value instanceof String);
	}
	
	return false;
}

/**
 * Check if variable is an array
 *
 * @param value
 * @returns {arg is any[]}
 */
function isArray(value) {
	return Array.isArray(value);
}

/**
 * Check if variable is an object
 * Note: Since 'null' is an object in JS, exclude it
 *
 * @param value
 * @returns {boolean}
 */
function isObject(value) {
	return (typeof value === 'object' && value !== null);
}

/**
 * Check if an element is a DOM element
 * @param value
 * @returns {boolean}
 */
function isDomElement(value) {
	return (isElDefined(value) && (value instanceof HTMLElement || value instanceof Element));
}

/**
 * Check if variable is a jQuery object
 * @param value
 * @returns {boolean}
 */
function isJQueryObject(value) {
	return (typeof jQuery !== 'undefined' && value instanceof jQuery);
}

/**
 * Check if variable is a JSON object
 * @param value
 * @returns {boolean}
 */
function isJsonObject(value) {
	return (
		typeof isObject(value)
		&& !isArray(value)
		&& !isJQueryObject(value)
		&& !isDomElement(value)
	);
}

/**
 * Check if variable is numeric (Integer or Float)
 * Note: Second argument to check if string containing an integer
 *
 * @param value
 * @param checkIfStringContainingAnInteger
 * @returns {boolean}
 */
function isNumeric(value, checkIfStringContainingAnInteger = false) {
	let isNumeric = (typeof value === 'number' && !Number.isNaN(value));
	
	if (checkIfStringContainingAnInteger) {
		let parsedValue;
		if (!isNumeric) {
			parsedValue = parseInt(value, 10);
			isNumeric = (value == parsedValue && !Number.isNaN(parsedValue));
		}
		if (!isNumeric) {
			parsedValue = parseFloat(value);
			isNumeric = (value == parsedValue && !Number.isNaN(parsedValue));
		}
	}
	
	return isNumeric;
}

/**
 * Check if variable is an integer (strictly)
 * @param value
 * @returns {boolean}
 */
function isInt(value) {
	return isNumeric(value) && Number.isSafeInteger(value);
}

/**
 * Check if variable is a float number (strictly)
 * @param value
 * @returns {boolean}
 */
function isFloat(value) {
	return isNumeric(value) && !Number.isInteger(value);
}

/**
 * Check if variable is string of JSON or not
 * @param value
 * @returns {boolean}
 */
function isJsonString(value) {
	if (isString(value)) {
		try {
			JSON.parse(value);
			return true;
		} catch (e) {
		}
	}
	return false;
}

/**
 * Check if variable is array of JSON objects
 * @param value
 * @returns {*}
 */
function isArrayOfJsonObjects(value) {
	return isArray(value) && value.every(item => isJsonObject(item));
}

/**
 * Check if variable is array of DOM Elements
 * @param value
 * @returns {*}
 */
function isArrayOfDomElements(value) {
	return isArray(value) && value.every(item => isDomElement(item));
}

/**
 * Get the DOM HTML element
 * @returns {HTMLElement}
 */
function getHtmlElement() {
	return document.documentElement;
}

/**
 * Convert a string to lowercase
 * @param value
 * @returns {string}
 */
function strToLower(value) {
	if (isString(value)) {
		value = value.toLowerCase();
	}
	
	return value;
}

/**
 * Convert a string to uppercase
 * @param value
 * @returns {string}
 */
function strToUpper(value) {
	if (isString(value)) {
		value = value.toUpperCase();
	}
	
	return value;
}

/**
 * sleep() version in JS
 * https://stackoverflow.com/a/39914235
 *
 * Usage:
 * await sleep(2000);
 * or
 * sleep(2000).then(() => {
 *     // Do something after the sleep!
 * });
 *
 * @param ms
 * @returns {Promise<unknown>}
 */
function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Array each
 *
 * Usage:
 * forEach(array, function(item, i) {});
 *
 * @param array
 * @param fn
 */
function forEach(array, fn) {
	for (let i = 0; i < array.length; i++) {
		fn(array[i], i);
	}
}

/**
 * Array map
 *
 * Usage:
 * map(array, function(value, index) {});
 *
 * @param array
 * @param fn
 * @returns {*[]}
 */
function map(array, fn) {
	let results = [];
	for (let i = 0; i < array.length; i++) {
		results.push(fn(array[i], i));
	}
	return results;
}

/**
 * Get a DOM element coordinates
 * @param el
 * @returns {{top: *, left: *, bottom: *, width, right: *, height}|null}
 */
function getElementCoords(el) {
	if (!isDomElement(el)) {
		return null;
	}
	
	const scrollY = window.scrollY;
	const scrollX = window.scrollX;
	const rect = el.getBoundingClientRect();
	
	return {
		top: rect.top + scrollY,
		right: rect.right + scrollX,
		bottom: rect.bottom + scrollY,
		left: rect.left + scrollX,
		width: rect.width,
		height: rect.height,
	};
}

/**
 * Extract error message
 * @param value
 * @param defaultMessage
 * @returns {*|null}
 */
function getErrorMessage(value, defaultMessage = null) {
	if (!isDefined(value)) {
		return defaultMessage;
	}
	
	let message = getErrorMessageFromXhr(value);
	if (isEmpty(message)) {
		message = getErrorMessageFromJson(value);
	}
	if (isEmpty(message)) {
		message = isString(value) ? value : null;
	}
	
	return !isEmpty(message) ? message : defaultMessage;
}

/**
 * Extracts a human-readable error message from various error formats, including those from AJAX, fetch, jQuery, Axios, and standard JavaScript errors.
 *
 * This utility is intended to standardize error message extraction for displaying to users or logging.
 *
 * 💡 Supported error formats:
 * - `string`: Raw string errors (`"Something went wrong"`)
 * - `Error`: Standard JS errors (`new Error("...")`)
 * - `Response`: fetch() `Response` objects (`throw response`)
 * - `jqXHR`: jQuery.ajax() error object with `.responseText` and/or `.responseJSON`
 * - `AxiosError`: Axios error with `.response.data.message` or similar
 * - `object`: Custom or nested error objects (e.g., `{ message: "...", detail: "..." }`)
 *
 * ✅ Extraction logic (in order):
 * 1. Direct string values
 * 2. Error object `.message`
 * 3. Known properties: `message`, `error`, `error_message`, `err`, `detail`, `description`
 * 4. Nested response objects: `.response`, `.responseJSON`, `.responseText` (auto-parsed)
 * 5. HTTP status fallback: `error.status` + `error.statusText`
 * 6. `.toString()` if meaningful
 * 7. Developer-provided `defaultMessage`
 * 8. Final fallback: `'An unknown error occurred. Request failed.'`
 *
 * ⚠️ This function is `async` because `fetch()` response bodies must be read asynchronously.
 *
 * @async
 * @function extractAjaxErrorMessage
 * @param {*} error - The error value from a failed AJAX call or exception. Can be of any type.
 * @param {string|null} [defaultMessage=null] - Optional developer-defined fallback error message. If not provided, a built-in fallback is used.
 * @returns {Promise<string>} A user-friendly, trimmed error message string.
 *
 * @example
 * try {
 *   const res = await fetch('/api/user');
 *   if (!res.ok) throw res;
 * } catch (err) {
 *   const message = await extractAjaxErrorMessage(err);
 *   alert(message); // "Unauthorized" or "HTTP error 401: Unauthorized"
 * }
 *
 * @example
 * $.ajax({
 *   url: '/api/user',
 *   method: 'GET',
 *   error: async function(jqXHR) {
 *     const message = await extractAjaxErrorMessage(jqXHR);
 *     console.error(message); // Logs parsed message from responseText or responseJSON
 *   }
 * });
 *
 * @example (Without await usage)
 * fetch('/api/user')
 *   .then(res => {
 *     if (!res.ok) throw res;
 *     return res.json();
 *   })
 *   .then(data => {
 *     console.log('Success:', data);
 *   })
 *   .catch(error => {
 *     extractAjaxErrorMessage(error).then(function (message) {
 *       console.error('Fetch error:', message);
 *     });
 *   });
 *
 * @example (Without await usage)
 * $.ajax({
 *   url: '/api/user',
 *   method: 'GET',
 *   error: function (jqXHR) {
 *     extractAjaxErrorMessage(jqXHR).then(function (message) {
 *       console.error('Error:', message);
 *       alert(message);
 *     });
 *   }
 * });
 */
async function extractAjaxErrorMessage(error, defaultMessage = null) {
	// const fallbackMessage = 'Something went wrong. Please try again.';
	const fallbackMessage = 'An unknown error occurred. Request failed.';
	
	if (!error) return defaultMessage ?? fallbackMessage;
	
	// String error (direct message)
	if (typeof error === 'string') {
		return error.trim() || (defaultMessage ?? fallbackMessage);
	}
	
	// JavaScript Error instance
	if (error instanceof Error) {
		return error.message?.trim() || (defaultMessage ?? fallbackMessage);
	}
	
	// fetch() Response object
	if (typeof Response !== 'undefined' && error instanceof Response) {
		try {
			const json = await error.clone().json();
			const msg = await extractAjaxErrorMessage(json, defaultMessage);
			if (msg) return msg;
		} catch {
			try {
				const text = await error.clone().text();
				if (text.trim()) return text.trim();
			} catch {
				// Ignore
			}
		}
		
		return `HTTP error ${error.status}: ${error.statusText}`.trim();
	}
	
	// Structured objects (Axios, jQuery.ajax, etc.)
	if (typeof error === 'object') {
		// Recursively try common nested paths
		if (error.response) {
			const msg = await extractAjaxErrorMessage(error.response, defaultMessage);
			if (msg) return msg;
		}
		
		if (error.responseJSON) {
			const msg = await extractAjaxErrorMessage(error.responseJSON, defaultMessage);
			if (msg) return msg;
		}
		
		if (typeof error.responseText === 'string') {
			try {
				const parsed = JSON.parse(error.responseText);
				const msg = await extractAjaxErrorMessage(parsed, defaultMessage);
				if (msg) return msg;
			} catch {
				return error.responseText.trim() || (defaultMessage ?? fallbackMessage);
			}
		}
		
		// Try common message keys at current level
		const messageKeys = ['message', 'error', 'error_message', 'err', 'detail', 'description'];
		for (const key of messageKeys) {
			if (typeof error[key] === 'string' && error[key].trim()) {
				return error[key].trim();
			}
		}
		
		// HTTP status fallback
		if (error.status || error.statusText) {
			return `HTTP error ${error.status || ''}: ${error.statusText || ''}`.trim();
		}
	}
	
	// Fallback: error.toString()
	try {
		if (typeof error.toString === 'function') {
			const str = error.toString();
			if (str && str !== '[object Object]') return str.trim();
		}
	} catch {
		// Ignore
	}
	
	return defaultMessage ?? fallbackMessage;
}

/**
 * Get error message from a XHR object
 * @param value
 * @param defaultMessage
 * @returns {*|null}
 */
function getErrorMessageFromXhr(value, defaultMessage = null) {
	let message = null;
	
	if (typeof value.responseJSON !== 'undefined') {
		message = getErrorMessageFromJson(value.responseJSON);
	}
	
	if (isEmpty(message)) {
		let responseText;
		if (typeof value.responseText !== 'undefined') {
			if (!isObject(value.responseText)) {
				try {
					responseText = JSON.parse(value.responseText);
				} catch (e) {
					responseText = parseHtml(value.responseText);
				}
			} else {
				responseText = parseHtml(value.responseText);
			}
			
			message = getErrorMessageFromJson(responseText);
		} else {
			if (typeof value.statusText !== 'undefined') {
				message = value.statusText;
			}
		}
	}
	
	return !isEmpty(message) ? message : defaultMessage;
}

/**
 * Get error message from a JSON object
 * @param value
 * @param defaultMessage
 * @returns {*|null}
 */
function getErrorMessageFromJson(value, defaultMessage = null) {
	if (!isObject(value)) {
		if (isString(value)) {
			return value;
		}
		return defaultMessage;
	}
	
	let message = isDefined(value.message) ? value.message : null;
	if (isEmpty(message)) {
		message = isDefined(value.error) ? value.error : null;
	}
	
	message = isString(message) ? message : null;
	
	return !isEmpty(message) ? message : defaultMessage;
}

/**
 * Extracts text content from HTML string with configurable options
 *
 * @param {string} htmlString - The HTML string to process
 * @param {Object} options - Configuration options
 * @param {boolean} options.bodyOnly - Whether to extract only body content (default: true)
 * @param {boolean} options.stripTags - Whether to remove HTML tags (default: true)
 * @param {boolean} options.normalizeWhitespace - Whether to normalize whitespace (default: true)
 * @returns {string} Processed content
 */
function parseHtml(htmlString, options = {}) {
	// Define default options
	const defaultOptions = {
		bodyOnly: true,
		stripTags: false,
		normalizeWhitespace: false
	};
	
	// Merge provided options with defaults
	const config = { ...defaultOptions, ...(options || {}) };
	const { stripTags, bodyOnly, normalizeWhitespace } = config;
	
	// Create a temporary DOM parser
	const parser = new DOMParser();
	const doc = parser.parseFromString(htmlString, 'text/html');
	
	// Get content based on bodyOnly option
	let content;
	if (bodyOnly) {
		const bodyElement = doc.querySelector('body');
		content = bodyElement ? bodyElement.innerHTML : htmlString;
	} else {
		content = htmlString;
	}
	
	// Strip HTML tags if requested
	if (stripTags) {
		const tempDiv = document.createElement('div');
		tempDiv.innerHTML = content;
		content = tempDiv.textContent || tempDiv.innerText || '';
	}
	
	// Normalize whitespace if requested
	if (normalizeWhitespace && stripTags) {
		content = content.replace(/\s+/g, ' ').trim();
	}
	
	return content;
}

/**
 * Check if string is an email address
 * @param str
 * @returns {boolean}
 */
function isEmailAddress(str) {
	/* Regular expression to match email addresses */
	const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
	return emailPattern.test(str);
}

/**
 * Find all email addresses containing in a string
 * @param str
 * @returns {*|*[]}
 */
function findEmailAddresses(str) {
	/* Regular expression to match email addresses */
	const emailPattern = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;
	const matches = str.match(emailPattern);
	return matches || [];
}

/**
 * Resolves a selector, DOM element, or an array of selectors/DOM elements
 * into an array of DOM elements.
 *
 * This function accepts a string representing a CSS selector, a single DOM element,
 * or an array of such selectors/elements, including nested arrays. It returns an
 * array of the matched DOM elements. If an invalid input is provided, the function
 * will log a warning to the console and return an empty array.
 *
 * @param {string | Element | Array<string | Element | Array>} selectors - A CSS selector, DOM element,
 * or an array of selectors/DOM elements, including nested arrays.
 * @returns {Element[]} An array of DOM elements corresponding to the provided selectors.
 * If no matches are found or if invalid inputs are provided, an empty array is returned.
 *
 * @example
 * // Get elements by CSS selector
 * resolveDomElements('.example-class');
 *
 * @example
 * // Get a single DOM element
 * const element = document.querySelector('#example-id');
 * resolveDomElements(element);
 *
 * @example
 * // Get elements by an array of selectors
 * resolveDomElements(['.example-class', '#example-id']);
 *
 * @example
 * // Handle nested arrays of selectors
 * resolveDomElements(['.example-class', ['#another-id', element]]);
 */
function resolveDomElements(selectors) {
	if (!selectors || (Array.isArray(selectors) && selectors.length === 0)) {
		return [];
	}
	
	if (!Array.isArray(selectors)) {
		selectors = [selectors];
	}
	
	return selectors.flatMap(selector => {
		if (Array.isArray(selector)) {
			return resolveDomElements(selector); // Recursive call
		} else if (typeof selector === 'string') {
			return Array.from(document.querySelectorAll(selector));
		} else if (selector instanceof Element) {
			return [selector];
		} else {
			console.warn(`Invalid selector: "${selector}". Use a string, a DOM element, or an array of them.`);
			return [];
		}
	});
}

/**
 * Set one or more elements' visibility (by passing their selector in argument)
 * Note: @action can be: hide or show
 * @param action
 * @param selectors
 */
function setElementsVisibility(action, selectors) {
	const elements = resolveDomElements(selectors);
	if (elements.length <= 0) return;
	
	elements.forEach((element) => {
		if (action === 'show') {
			element.style.display = ''; /* Default to empty string to show element */
		} else if (action === 'hide') {
			element.style.display = 'none';
		} else {
			console.warn(`Invalid action: "${action}". Use 'show' or 'hide'.`);
		}
	});
}

/**
 * Toggle one or more elements' classes by adding/removing a specified class
 * Note: @action can be: add or remove
 * @param selectors
 * @param action
 * @param className
 */
function toggleElementsClass(selectors, action, className) {
	const elements = resolveDomElements(selectors);
	if (elements.length <= 0) return;
	
	elements.forEach(function (element) {
		if (action === 'add') {
			element.classList.add(className);
		} else if (action === 'remove') {
			element.classList.remove(className);
		} else {
			console.warn(`Invalid action: "${action}". Use 'add' or 'remove'.`);
		}
	});
}

/**
 * Updates a select box with options from a JSON object and optionally sets a default selected option
 *
 * Example usage:
 * const options = {
 * 	"option1": "Option 1",
 * 	"option2": "Option 2",
 * 	"option3": "Option 3"
 * };
 *
 * Update the select box with ID 'mySelectBox' and set 'option2' as the default selected option
 * updateSelectOptions('#mySelectBox', options, 'option2');
 *
 * @param {string|Element} selectElement
 * @param {object} optionsJson
 * @param {null|string} defaultValue
 */
function updateSelectOptions(selectElement, optionsJson, defaultValue = null) {
	/* Get the select box element with selector */
	const selectBox = isString(selectElement) ? document.querySelector(selectElement) : selectElement;
	
	/* If the select box does not exist, log an error and exit the function */
	if (!isDomElement(selectBox)) {
		if (isString(selectElement)) {
			console.error(`Select box with selector "${selectElement}" not found.`);
		}
		return;
	}
	
	/* Clear the existing options */
	selectBox.innerHTML = '';
	
	/* Iterate through the JSON object and create option elements */
	for (const [value, text] of Object.entries(optionsJson)) {
		const option = document.createElement('option');
		option.value = value;
		option.text = text;
		
		/* If a default value is provided and matches the current option value, set it as selected */
		if (defaultValue !== null && value === defaultValue) {
			option.selected = true;
		}
		
		selectBox.appendChild(option);
	}
}

/**
 * Updates a Select2 select box with options from a JSON object and optionally sets a default selected option
 * Note: This is the select2 version of the updateSelectOptions() function
 *
 * @param {string|Element} selectElement
 * @param {object} optionsJson
 * @param {null|string} defaultValue
 */
function updateSelect2Options(selectElement, optionsJson, defaultValue = null) {
	if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
		console.error(`jQuery is not available.`);
		return;
	}
	
	/* Get the select box element with selector */
	let selectBox = (isString(selectElement) || isDomElement(selectElement))
		? $(selectElement)
		: selectElement;
	
	/* If the select box does not exist, log an error and exit the function */
	if (!isJQueryObject(selectBox)) {
		if (isString(selectElement)) {
			console.error(`Select box with selector "${selectElement}" not found.`);
		}
		return;
	}
	
	/* Clear the existing options */
	selectBox.empty();
	
	/* Iterate through the JSON object and create option elements */
	for (const [value, text] of Object.entries(optionsJson)) {
		if (value === defaultValue) {
			selectBox.append(`<option value="${value}" selected="selected">${text}</option>`);
		} else {
			selectBox.append(`<option value="${value}">${text}</option>`);
		}
	}
	
	/* Set the default value if provided */
	if (defaultValue !== null) {
		selectBox.val(defaultValue).trigger('change');
	}
}

/**
 * Convert associative JSON object to key:value object (for Select options for example)
 * @param jsonObject
 * @param {string} valueProperty
 * @param {string|null} keyProperty
 * @returns {{}}
 */
function assocObjectToKeyValue(jsonObject, valueProperty, keyProperty = null) {
	const newObject = {};
	
	for (const key in jsonObject) {
		if (jsonObject.hasOwnProperty(key)) {
			const newKey = !isEmpty(keyProperty) ? jsonObject[key][keyProperty] : key;
			newObject[newKey] = jsonObject[key][valueProperty];
		}
	}
	
	return newObject;
}

/**
 * Ensures that the given value is returned as a plain JSON object.
 *
 * - If the value is already a non-null object (and not an array), it is returned as-is.
 * - If the value is a valid JSON string that parses into an object, it is parsed and returned.
 * - Otherwise, an empty object is returned as a safe fallback.
 *
 * @param {unknown} value - The input value to check or parse.
 * @returns {Object} - A valid JSON object. Returns `{}` if the input is invalid or not an object.
 */
function ensureJsonObject(value) {
	// If it's already an object (and not null or array), return it
	if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
		return value;
	}
	
	// If it's a string, try to parse it
	if (typeof value === 'string') {
		try {
			const parsed = JSON.parse(value);
			// Check if parsed result is an object (not array or null)
			if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
				return parsed;
			}
		} catch (error) {
			// JSON.parse failed, fall through to create new object
		}
	}
	
	// For any other case, return an empty object or wrap the value
	return {};
}

/**
 * Add classes to DOM element(s)
 * @param selectors
 * @param classesToAdd
 */
function addClassesToElement(selectors, classesToAdd) {
	const elements = resolveDomElements(selectors);
	if (elements.length <= 0) return;
	
	// Split the class string into an array and filter out empty strings
	const classes = classesToAdd.split(/\s+/).filter(cls => cls.trim() !== '');
	
	// Add each class using classList.add, which prevents duplicates
	elements.forEach(element => {
		classes.forEach(cls => element.classList.add(cls));
	});
}

/**
 * Remove classes from DOM element(s)
 * @param selectors
 * @param classesToRemove
 */
function removeClassesFromElement(selectors, classesToRemove) {
	const elements = resolveDomElements(selectors);
	if (elements.length <= 0) return;
	
	// Split the class string into an array and filter out empty strings
	const classes = classesToRemove.split(/\s+/).filter(cls => cls.trim() !== '');
	
	// Remove each class using classList.remove
	elements.forEach(element => {
		classes.forEach(cls => element.classList.remove(cls));
	});
}

/**
 * Remove classes that start with a given prefix from DOM element(s)
 * @param selectors
 * @param prefix
 */
function removeClassesWithPrefix(selectors, prefix) {
	const elements = resolveDomElements(selectors);
	if (elements.length <= 0) return;
	
	elements.forEach(element => {
		// Get all classes on the element
		const classes = Array.from(element.classList);
		
		// Filter classes that start with the given prefix and remove them
		classes.filter(cls => cls.startsWith(prefix))
		.forEach(cls => element.classList.remove(cls));
	});
}

/**
 * Update/replace the element(s)' Bootstrap class starting by a given prefix
 * @param selectors
 * @param prefix
 * @param newClass
 */
function updateClassesWithPrefix(selectors, prefix, newClass) {
	// Remove all classes starting with 'bg-'
	removeClassesWithPrefix(selectors, prefix);
	
	// Add the new background class
	addClassesToElement(selectors, newClass);
}

/**
 * Checks if a value contains a transparent RGBA color
 * @param {string} value - The color value to check
 * @param {Object} options - Configuration options
 * @param {boolean} options.allowFullyTransparent - Include alpha = 0 (default: true)
 * @param {boolean} options.allowFullyOpaque - Include alpha = 1 (default: false)
 * @returns {boolean} True if the value is a transparent RGBA color
 */
function isTransparentRgba(value, options = {}) {
	const {
		allowFullyTransparent = true,
		allowFullyOpaque = false
	} = options;
	
	// Enhanced regex to handle various RGBA formats
	const rgbaRegex = /rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0*\.?\d+|1|0)\s*\)/i;
	const match = value.match(rgbaRegex);
	
	if (!match) {
		return false;
	}
	
	// Extract RGB values (for validation)
	const r = parseInt(match[1]);
	const g = parseInt(match[2]);
	const b = parseInt(match[3]);
	const alpha = parseFloat(match[4]);
	
	// Validate RGB values are in valid range (0-255)
	if (r > 255 || g > 255 || b > 255) {
		return false;
	}
	
	// Check alpha value based on options
	if (alpha === 0) {
		return allowFullyTransparent;
	} else if (alpha === 1) {
		return allowFullyOpaque;
	} else if (alpha > 0 && alpha < 1) {
		return true; // Semi-transparent values are always considered transparent
	}
	
	return false;
}
