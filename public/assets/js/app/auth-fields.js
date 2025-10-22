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

if (typeof isLoggedUser === 'undefined') {
	var isLoggedUser = false;
}
if (typeof defaultAuthField === 'undefined') {
	var defaultAuthField = 'email';
}

onDocumentReady((event) => {
	
	/* Get all forms elements */
	const formEls = document.querySelectorAll('form');
	
	/* Get all forms that have an auth field */
	const authFormEls = Array.from(formEls).filter(form => form.querySelector('.auth-field-item'));
	
	if (authFormEls.length > 0) {
		authFormEls.forEach(formEl => {
			
			/* Select an auth field */
			selectAuthField(formEl, null, defaultAuthField);
			
			/* Add event listener for click events on elements with class 'auth-field' */
			const authFieldLinkEls = formEl.querySelectorAll('a.auth-field');
			if (authFieldLinkEls.length > 0) {
				authFieldLinkEls.forEach(element => {
					element.addEventListener('click', e => {
						e.preventDefault();
						selectAuthField(formEl, e.target);
					});
				});
			}
			
			/* Add event listener for change events on elements with class 'auth-field-input' */
			const authFieldRadioBtnEls = formEl.querySelectorAll('input.auth-field-input');
			if (authFieldRadioBtnEls.length > 0) {
				authFieldRadioBtnEls.forEach(element => {
					element.addEventListener('change', e => {
						selectAuthField(formEl, e.target);
					});
				});
			}
			
		});
	}
	
});

/**
 * Select an auth field (email or phone)
 *
 * @param formEl
 * @param thisEl
 * @param defaultAuthField
 * @returns {boolean}
 */
function selectAuthField(formEl, thisEl = null, defaultAuthField = null) {
	defaultAuthField = defaultAuthField || 'email';
	
	/* Select default auth field */
	let authFieldTagName;
	let authField;
	if (thisEl) {
		authFieldTagName = thisEl.tagName.toLowerCase();
		authField = (authFieldTagName === 'input')
			? thisEl.value
			: thisEl.dataset.authField ?? defaultAuthField;
	} else {
		authField = defaultAuthField;
	}
	
	if (!authField || authField.length <= 0) {
		jsAlert('Impossible to get the auth field!', 'error', false);
		return false;
	}
	
	/* Update the 'auth_field' field value */
	if (authFieldTagName && authFieldTagName === 'a') {
		const authFieldEls = formEl.querySelectorAll("input[name='auth_field']:not([type=radio], [type=checkbox])");
		if (authFieldEls.length > 0) {
			authFieldEls.forEach(input => {
				input.value = authField;
			});
		}
	}
	
	/* Get the auth field items (email|phone) & the selected item elements */
	const itemsEls = formEl.querySelectorAll('.auth-field-item');
	const canBeHiddenItemsEls = formEl.querySelectorAll('.auth-field-item:not(.force-to-display)');
	
	let selectedItemParentEl;
	const selectedItemEl = formEl.querySelector("input[name='" + authField + "']");
	if (selectedItemEl) {
		selectedItemParentEl = selectedItemEl.closest('.auth-field-item');
	}
	
	/* Manage required '<sup>' tag in the auth field items' label */
	if (itemsEls.length > 0) {
		itemsEls.forEach(item => {
			item.classList.remove('required');
			let sup = item.querySelector('label sup');
			if (sup) {
				sup.remove();
			}
		});
	}
	
	if (selectedItemParentEl) {
		selectedItemParentEl.classList.remove('required');
		selectedItemParentEl.classList.add('required');
		const labelEl = selectedItemParentEl.querySelector('label');
		if (labelEl) {
			const labelRequireTagEl = labelEl.querySelector('span.text-danger');
			if (!labelRequireTagEl) {
				labelEl.innerHTML += ' <span class="text-danger ms-1">*</span>';
			}
		}
	}
	
	/* Manage auth field items display */
	if (typeof isLoggedUser !== 'undefined' && isLoggedUser !== true) {
		if (canBeHiddenItemsEls.length > 0) {
			canBeHiddenItemsEls.forEach(item => {
				item.classList.add('d-none');
			});
		}
		if (selectedItemParentEl) {
			selectedItemParentEl.classList.remove('d-none');
		}
	}
}
