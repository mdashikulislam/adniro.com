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
 * Apply the 'intl-tel-input' plugin to the phone field
 *
 * @param inputEl
 * @param options
 * @param placeholderType
 * @param customPlaceholder
 * @returns {*|null}
 */
function applyIntlTelInput(inputEl, options = {}, placeholderType = 'auto', customPlaceholder = '') {
	if (!inputEl) return null;
	
	// Ensure that 'onlyCountries' is defined and is not empty
	if (
		typeof options.onlyCountries === 'undefined' ||
		options.onlyCountries === null ||
		(Array.isArray(options.onlyCountries) && options.onlyCountries.length === 0)
	) {
		return null;
	}
	
	// Ensure that 'onlyCountries' is a JSON object
	options.onlyCountries = isJsonString(options.onlyCountries) ?
		JSON.parse(options.onlyCountries)
		: options.onlyCountries;
	
	if (!isObject(options.onlyCountries)) {
		return null;
	}
	
	// For formatting/placeholders etc.
	// Get the 'utils.js' file path
	let utilsFilePath = '/assets/plugins/intl-tel-input/25.3.1/js/utils.js';
	if (typeof siteUrl !== 'undefined' && siteUrl !== null) {
		utilsFilePath = siteUrl + utilsFilePath;
	}
	options.loadUtils = () => import(utilsFilePath);
	
	// Set the placeholder option
	if (placeholderType !== 'undefined' && placeholderType !== null && typeof placeholderType === 'string') {
		if (placeholderType === 'none') {
			options.autoPlaceholder = 'off';
		}
		if (placeholderType === 'custom' || placeholderType.startsWith('auto')) {
			options.customPlaceholder = function (selectedCountryPlaceholder, selectedCountryData) {
				if (placeholderType === 'custom') {
					return customPlaceholder;
				}
				if (placeholderType.endsWith('0')) {
					// Replace all digits (0-9) with '0'
					return selectedCountryPlaceholder.replace(/\d/g, '0');
				}
				if (placeholderType.endsWith('x')) {
					// Replace all digits (0-9) with 'x'
					return selectedCountryPlaceholder.replace(/\d/g, 'x');
				}
				return selectedCountryPlaceholder;
			}
		}
	}
	
	// Does 'initialCountry' exist?
	const doesInitialCountryExist = (typeof options.initialCountry !== 'undefined' && options.initialCountry !== null);
	if (doesInitialCountryExist) {
		// Is the current country's item/object?
		const isCurrPhoneCountryItem = (item) => {
			return (
				(typeof item !== 'undefined' && item !== null) &&
				(item.toLowerCase() === options.initialCountry.toLowerCase())
			);
		};
		
		// Check the (eventual) initial country exists in the countries list,
		// If not, set the initial country to null (so that will be deleted later).
		if (
			options.onlyCountries.filter((item) => isCurrPhoneCountryItem(item)).length <= 0
		) {
			options.initialCountry = null;
		}
		
		// Reorder the country list by putting first the initial country
		if (options.initialCountry) {
			options.countryOrder.push(options.initialCountry);
		}
		
		// Delete the initialCountry option if it is empty
		if (options.initialCountry === null) {
			delete options.initialCountry;
		}
	}
	
	// Initialization
	// --------------
	// NOTE:
	// - Implementation using a CDN:
	//   window.intlTelInput(...);
	// - Implementation using a bundler e.g. Webpack:
	//   import intlTelInput from 'intl-tel-input';
	//   intlTelInput(...);
	//
	// TIP: Store the intlTelInput object variable in 'window.iti',
	// so we can access it in the console e.g. window.iti.getNumber()
	const iti = window.intlTelInput(inputEl, options);
	
	// Populate phone hidden inputs
	const populatePhoneHiddenInputs = () => {
		// phone intl format
		const phoneHiddenInput = options.hiddenInput('telInputName').phone;
		const phoneIntlEls = document.querySelectorAll(`input[name="${phoneHiddenInput}"]`);
		if (phoneIntlEls.length) {
			const phoneIntl = iti.getNumber();
			phoneIntlEls.forEach((element) => element.value = phoneIntl);
		}
		
		// phone country code
		const countryHiddenInput = options.hiddenInput('telInputName').country;
		let countryCodeEls = document.querySelectorAll(`input[name="${countryHiddenInput}"]`);
		if (countryCodeEls.length) {
			const selectedCountryData = iti.getSelectedCountryData();
			countryCodeEls.forEach((element) => {
				if ((typeof selectedCountryData.iso2 !== 'undefined' && selectedCountryData.iso2 !== null)) {
					element.value = selectedCountryData.iso2;
				}
			});
		}
	};
	
	inputEl.addEventListener('focus', populatePhoneHiddenInputs);
	inputEl.addEventListener('blur', populatePhoneHiddenInputs);
	inputEl.addEventListener('change', populatePhoneHiddenInputs);
	inputEl.addEventListener('keyup', populatePhoneHiddenInputs);
	
	return iti;
}
