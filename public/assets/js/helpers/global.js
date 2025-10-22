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

preventPageLoadingInIframe();

onDocumentReady((event) => {
	/* Confirm Actions Links */
	$(document).on('click', '.confirm-simple-action', function (e) {
		e.preventDefault(); /* Prevents submission or reloading */
		
		try {
			let showCancelInfo = false;
			if (isAdminPanel) {
				if (isDemoDomain()) {
					return false;
				}
				showCancelInfo = true;
			}
			
			confirmSimpleAction(this, showCancelInfo);
		} catch (e) {
			jsAlert(e, 'error', false);
		}
	});
});

/**
 * Open Login Modal
 */
function openLoginModal() {
	const quickLoginEl = document.getElementById('quickLogin');
	if (quickLoginEl) {
		const loginModal = new bootstrap.Modal(quickLoginEl, {});
		loginModal.show();
	}
}

/**
 * Displays a SweetAlert2 confirmation dialog that mimics the native JavaScript confirm() behavior.
 *
 * @example
 * // Basic usage
 * const userConfirmed = await swalConfirm('Are you sure you want to delete this item?');
 * if (userConfirmed) {
 *     // User clicked "Yes"
 *     deleteItem();
 * }
 *
 * @example
 * // Usage with default localized message
 * const confirmation = await swalConfirm();
 * if (confirmation) {
 *     console.log('User confirmed the action');
 * }
 *
 * @example
 * // Usage in a conditional block (similar to native confirm)
 * if (await swalConfirm('Save changes before closing?')) {
 *     saveChanges();
 * }
 *
 * @async
 * @function swalConfirm
 * @param {string} [text] - The confirmation message to display. Falls back to localized text or default message if not provided.
 * @returns {Promise<boolean>} A Promise that resolves to `true` if the user confirms (clicks "Yes"),
 *                            or `false` if the user cancels (clicks "No" or dismisses the dialog).
 */
async function swalConfirm(text) {
	const alertParams = {
		text: text || langLayout?.confirm?.message?.question || 'Do you want to proceed?',
		icon: 'question', /* question|warning */
		showCancelButton: true,
		confirmButtonText: langLayout?.confirm?.button?.yes || 'Yes',
		cancelButtonText: langLayout?.confirm?.button?.no || 'No',
		theme: isDarkThemeEnabled() ? 'dark' : 'light'
	};
	const result = await Swal.fire(alertParams);
	
	return result.isConfirmed;
}

/**
 * Confirm Simple Action (Links or forms without AJAX)
 * Usage: Add 'confirm-simple-action' in the element class attribute
 *
 * @param clickedEl
 * @param showCancelInfo
 * @param cancelInfoAutoDismiss
 * @returns {boolean}
 */
function confirmSimpleAction(clickedEl, showCancelInfo = true, cancelInfoAutoDismiss = true) {
	if (typeof Swal === 'undefined') {
		return false;
	}
	
	let alertParams = {
		text: langLayout?.confirm?.message?.question || 'Do you want to proceed?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: langLayout?.confirm?.button?.yes || 'Yes',
		cancelButtonText: langLayout?.confirm?.button?.no || 'No',
		theme: isDarkThemeEnabled() ? 'dark' : 'light'
	};
	
	Swal.fire(alertParams)
	.then((result) => {
		if (result.isConfirmed) {
			
			try {
				if ($(clickedEl).is('a')) {
					let actionUrl = $(clickedEl).attr('href');
					if (actionUrl !== 'undefined') {
						console.log(actionUrl);
						redirect(actionUrl);
					}
				} else {
					let actionForm = $(clickedEl).parents('form:first');
					$(actionForm).submit();
				}
			} catch (e) {
				console.log(e);
			}
			
		} else if (result.dismiss === Swal.DismissReason.cancel) {
			if (showCancelInfo === true) {
				jsAlert(langLayout.confirm.message.cancel, 'info', cancelInfoAutoDismiss);
			}
		}
	});
	
	return false;
}

/**
 * Show JS Alert Messages (Swal)
 * @param message
 * @param type
 * @param cancelAlertAutoDismiss
 * @param reloadPageIfConfirmed
 * @param blockUi
 * @returns {boolean}
 */
function jsAlert(message, type = 'info', cancelAlertAutoDismiss = true, reloadPageIfConfirmed = false, blockUi = false) {
	if (typeof Swal === 'undefined') {
		return false;
	}
	
	let alertParams = {
		html: message,
		icon: type,
		position: 'center',
		theme: isDarkThemeEnabled() ? 'dark' : 'light'
	};
	
	if (cancelAlertAutoDismiss === true) {
		alertParams.showCancelButton = false;
		alertParams.showConfirmButton = false;
		alertParams.timer = 3000;
	} else {
		alertParams.showCancelButton = true;
		if (reloadPageIfConfirmed === true) {
			alertParams.confirmButtonText = langLayout.refresh;
		} else {
			alertParams.confirmButtonText = langLayout.confirm.button.ok;
			alertParams.cancelButtonText = langLayout.confirm.button.cancel;
		}
	}
	if (blockUi) {
		alertParams.showCancelButton = false;
		alertParams.allowOutsideClick = false;
		alertParams.allowEscapeKey = false;
	}
	
	let alertObj = Swal.fire(alertParams);
	
	if (reloadPageIfConfirmed === true) {
		alertObj.then((result) => {
			if (result.isConfirmed) {
				/* Reload Page */
				/* JS 1.1 - Does not create a history entry */
				window.location.replace(window.location.pathname + window.location.search + window.location.hash);
				
				/* JS 1.0 - Creates a history entry */
				window.location.href = window.location.pathname + window.location.search + window.location.hash;
			}
		});
	}
}

/**
 * Show JS Alert Messages (PNotify)
 * PNotify: https://github.com/sciactive/pnotify
 *
 * @param message
 * @param type
 * @param icon
 * @returns {boolean}
 */
function pnAlert(message, type = 'notice', icon = null) {
	if (typeof PNotify === 'undefined') {
		return false;
	}
	
	if (type === 'warning') {
		type = 'notice';
	}
	
	if (typeof window.stackTopRight === 'undefined') {
		window.stackTopRight = new PNotify.Stack({
			dir1: 'down',
			dir2: 'left',
			firstpos1: 25,
			firstpos2: 25,
			spacing1: 10,
			spacing2: 25,
			modal: false,
			maxOpen: Infinity
		});
	}
	let alertParams = {
		text: message,
		type: type,
		stack: window.stackTopRight
	};
	if (icon !== null) {
		alertParams.icon = icon;
	}
	
	new PNotify.alert(alertParams);
}

/**
 * Show the waiting dialog
 */
function showWaitingDialog() {
	let alertParams = {
		title: langLayout.waitingDialog.loading.title,
		text: langLayout.waitingDialog.loading.text,
		timerProgressBar: true,
		allowOutsideClick: false,
		returnFocus: true,
		didOpen: () => {
			Swal.showLoading(); /* Show spinner */
		},
		didClose: () => {
			if (typeof document !== 'undefined') {
				document.activeElement.blur();
			}
		},
		theme: isDarkThemeEnabled() ? 'dark' : 'light'
	};
	
	Swal.fire(alertParams);
}

/**
 * Hide the waiting dialog
 */
function hideWaitingDialog() {
	Swal.close();
}

/**
 * Show complete waiting dialog
 * @param message
 * @param cancelAlertAutoDismiss
 * @returns {boolean}
 */
function completeWaitingDialog(message = null, cancelAlertAutoDismiss = true) {
	if (typeof Swal === 'undefined') {
		return false;
	}
	
	let alertParams = {
		icon: 'success',
		title: langLayout.waitingDialog.complete.title,
		text: message ?? langLayout.waitingDialog.complete.text,
		position: 'center',
		theme: isDarkThemeEnabled() ? 'dark' : 'light',
	};
	
	alertParams.showCancelButton = false;
	if (cancelAlertAutoDismiss === true) {
		alertParams.showConfirmButton = false;
		alertParams.timer = 3000;
	} else {
		alertParams.showConfirmButton = true;
		alertParams.confirmButtonText = langLayout.confirm.button.ok;
	}
	
	Swal.fire(alertParams);
}

/**
 * Show JS Alert Messages (Bootstrap Modal)
 * Note: Need to create an empty modal HTML code in the pages layout
 *
 * @param error
 * @param errorTitle
 * @returns {boolean}
 */
function bsModalAlert(error, errorTitle = null) {
	let message = getErrorMessage(error);
	let title = !isEmpty(errorTitle) ? errorTitle : null;
	
	if (isEmpty(message)) {
		return false;
	}
	
	const modalEl = document.getElementById("errorModal");
	const modalTitleEl = document.getElementById("errorModalTitle");
	const modalBodyEl = document.getElementById("errorModalBody");
	
	if (!isDomElement(modalEl) || !isDomElement(modalTitleEl) || !isDomElement(modalBodyEl)) {
		return false;
	}
	
	/* Set up the Modal */
	if (!isEmpty(title)) {
		modalTitleEl.innerHTML = title;
	}
	message = '<code>' + message + '</code>';
	modalBodyEl.innerHTML = message;
	
	/* Open the Modal */
	const myModal = new bootstrap.Modal(modalEl, {});
	myModal.show();
}

/**
 * Disable the field's Tooltip (Need to be hidden first)
 * @param tooltipTriggerEl
 */
function disableTooltipForElement(tooltipTriggerEl) {
	if (isElDefined(tooltipTriggerEl)) {
		const tooltip = new bootstrap.Tooltip(tooltipTriggerEl);
		tooltip.hide();
		tooltip.disable();
	}
}

/**
 * Enable the field's Tooltip
 * @param tooltipTriggerEl
 */
function enableTooltipForElement(tooltipTriggerEl) {
	if (isElDefined(tooltipTriggerEl)) {
		const tooltip = new bootstrap.Tooltip(tooltipTriggerEl);
		tooltip.enable();
	}
}

/**
 * Check user is on demo domain
 * @returns {boolean}
 */
function isDemoDomain() {
	try {
		if (demoMode) {
			jsAlert(demoMessage, 'error');
			
			return true;
		}
	} catch (e) {
		jsAlert(e, 'error', false);
		
		return true;
	}
	
	return false;
}

/**
 * Check|uncheck all checkboxes by checking|unchecking a main checkbox
 * @param mainCheckboxEl
 * @param mainCheckboxId
 * @param subCheckboxesName
 */
function checkAllBoxes(mainCheckboxEl, mainCheckboxId = 'checkAll', subCheckboxesName = 'entries[]') {
	if (!mainCheckboxEl) return;
	
	// Ensure that the main checkbox ID and the sub-checkboxes name are provided
	const isNotEmptyId = (typeof mainCheckboxId === 'string' && mainCheckboxId.trim().length > 0);
	const isNotEmptyName = (typeof subCheckboxesName === 'string' && subCheckboxesName.trim().length > 0);
	if (!isNotEmptyId || !isNotEmptyName) return;
	
	// Check|Uncheck the main checkbox
	if (mainCheckboxEl.tagName.toLowerCase() !== 'input' || mainCheckboxEl.type !== 'checkbox') {
		mainCheckboxEl = document.getElementById(mainCheckboxId);
		if (mainCheckboxEl) {
			mainCheckboxEl.checked = !mainCheckboxEl.checked;
		}
	}
	
	// Check|Uncheck all checkboxes
	const subCheckboxesSelector = `input[type="checkbox"][name="${subCheckboxesName}"]`;
	const subCheckboxesEls = document.querySelectorAll(subCheckboxesSelector);
	if (subCheckboxesEls.length > 0) {
		for (let i = 0; i < subCheckboxesEls.length; i++) {
			if (subCheckboxesEls[i].type === 'checkbox') {
				subCheckboxesEls[i].checked = mainCheckboxEl.checked;
			}
		}
	}
}

/**
 * Configure Select2 Options for Offcanvas Compatibility
 *
 * Automatically configures Select2 options to work properly within Bootstrap offcanvas components.
 * Detects if the select element is inside an offcanvas container and adds the appropriate
 * dropdownParent option to prevent focus and positioning issues.
 *
 * @param selectElement
 * @param options
 * @returns {{}}
 */
function getSelect2OptionsWithOffcanvas(selectElement, options = {}) {
	/* Ensure we have a jQuery object */
	const $select = selectElement instanceof jQuery ? selectElement : $(selectElement);
	
	/* Clone the options to avoid modifying the original object */
	const updatedOptions = { ...options };
	
	/* Check if the select element is inside an offcanvas */
	const $offcanvasParent = $select.closest('.offcanvas');
	
	if ($offcanvasParent.length > 0) {
		/* Element is inside an offcanvas */
		let offcanvasId = $offcanvasParent.attr('id');
		
		/* If no ID exists, generate one */
		if (!offcanvasId) {
			offcanvasId = 'offcanvas-auto-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
			$offcanvasParent.attr('id', offcanvasId);
		}
		
		/* Add dropdownParent to options */
		updatedOptions.dropdownParent = $offcanvasParent;
	}
	
	return updatedOptions;
}
