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

/*
 * Functionality:
 *
 * - Tracks form changes in real-time
 * - Prompts on browser tab/window close
 * - Prompts on link clicks (except links with target="_blank", data-bs-toggle="modal" or data-ignore-guard="true")
 *   i.e. Bypassing for links with target="_blank", data-bs-toggle="modal" or data-ignore-guard="true".
 * - Resets when form is submitted
 * - Works with any form input types
 *   Support for all form field types (input, select, checkbox, radio, textarea, hidden, etc.).
 * - Compares original vs current form data
 * - Provides methods to manually reset or check dirty state
 * - Automatic initialization for forms with class="unsaved-guard".
 * - notifyChange() method for programmatic changes (e.g., hidden fields).
 * - Handling of beforeunload and link click events with translated/custom prompts.
 */

class UnsavedFormGuard {
	constructor(form, options = {}) {
		this.form = form;
		this.isDirty = false;
		this.originalData = new FormData();
		this.translations = options.translations || {};
		this.language = options.language || 'en';
		this.texts = options.texts || {};
		
		if (!this.form) {
			console.error(this.t('error_form_not_found', 'Form not found'));
			return;
		}
		
		this.init();
	}
	
	// Translation function with custom texts and fallback
	t(key, defaultText) {
		// Check custom texts first
		if (this.texts[key]) {
			return this.texts[key];
		}
		// Fall back to language-specific translation
		const langTranslations = this.translations[this.language] || {};
		return langTranslations[key] || defaultText;
	}
	
	init() {
		// Store initial form data
		this.saveOriginalData();
		
		// Track form changes
		this.form.addEventListener('change', () => {
			this.isDirty = this.hasChanges();
		});
		
		// Track input events for real-time updates
		this.form.addEventListener('input', () => {
			this.isDirty = this.hasChanges();
		});
		
		// Handle form submission (reset dirty state)
		this.form.addEventListener('submit', () => {
			this.isDirty = false;
		});
		
		// Handle beforeunload event
		window.addEventListener('beforeunload', (e) => {
			if (this.isDirty) {
				e.preventDefault();
				const message = this.t('unsaved_changes_prompt', 'You have unsaved changes. Are you sure you want to leave?');
				e.returnValue = message;
				return message;
			}
		});
		
		// Handle link clicks
		document.addEventListener('click', (e) => {
			if (
				this.isDirty &&
				e.target.tagName === 'A' &&
				!e.target.dataset.ignoreGuard &&
				!(e.target.dataset.bsToggle && e.target.dataset.bsToggle === 'modal') &&
				e.target.target !== '_blank'
			) {
				if (!confirm(this.t('unsaved_changes_prompt', 'You have unsaved changes. Are you sure you want to leave?'))) {
					e.preventDefault();
				}
			}
		});
	}
	
	saveOriginalData() {
		const formData = new FormData(this.form);
		for (const [key, value] of formData) {
			this.originalData.set(key, value);
		}
	}
	
	hasChanges() {
		const currentData = new FormData(this.form);
		
		// Compare current form data with original
		for (const [key, value] of this.originalData) {
			if (currentData.get(key) !== value) {
				return true;
			}
		}
		
		// Check for new fields
		for (const [key] of currentData) {
			if (!this.originalData.has(key)) {
				return true;
			}
		}
		
		return false;
	}
	
	// Method to manually reset dirty state
	reset() {
		this.isDirty = false;
		this.saveOriginalData();
	}
	
	// Method to check if form is dirty
	isFormDirty() {
		return this.isDirty;
	}
	
	// Static method to initialize all forms with the guard class
	static initAll(guardClass = 'unsaved-guard', options = {}) {
		const forms = document.querySelectorAll(`form.${guardClass}`);
		const guards = [];
		forms.forEach(form => {
			guards.push(new UnsavedFormGuard(form, options));
		});
		return guards;
	}
}

/* Automatically initialize all forms with class 'unsaved-guard' on DOM load */
document.addEventListener('DOMContentLoaded', () => {
	let texts, language;
	
	if (typeof langLayout !== 'undefined' && typeof langLayout.unsavedFormGuard !== 'undefined') {
		texts = langLayout.unsavedFormGuard;
	}
	
	if (typeof languageCode !== 'undefined') {
		language = languageCode;
		language = language.split('-')[0];
		language = language.split('_')[0];
	}
	
	UnsavedFormGuard.initAll('unsaved-guard', {
		texts: texts || {},
		translations: fgTranslations || {},
		language: language || navigator.language.split('-')[0] || 'en'
	});
});
