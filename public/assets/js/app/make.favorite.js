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

onDocumentReady((event) => {
	
	/* Save the Post */
	const makeFavoriteEls = document.querySelectorAll("a[id].make-favorite");
	if (makeFavoriteEls.length > 0) {
		makeFavoriteEls.forEach((element) => {
			element.addEventListener("click", (event) => {
				event.preventDefault(); /* Prevents submission or reloading */
				
				if (isLoggedUser !== true) {
					openLoginModal();
					return false;
				}
				
				return savePost(event.target);
			});
		});
	}
	
	/* Save the Search */
	const saveSearchEl = document.getElementById("saveSearch");
	if (saveSearchEl) {
		saveSearchEl.addEventListener("click", (event) => {
			event.preventDefault(); /* Prevents submission or reloading */
			
			if (isLoggedUser !== true) {
				openLoginModal();
				return false;
			}
			
			return saveSearch(event.target);
		});
	}
	
});

/**
 * Save Ad
 *
 * @param el
 * @returns {Promise<boolean>}
 */
async function savePost(el) {
	el = el.parentElement;
	
	/* Get element's icon */
	let iconEl = null;
	if (el.tagName.toLowerCase() === 'a') {
		iconEl = el.querySelector('i');
	}
	
	let postId = el.id ?? null;
	if (!postId) {
		console.error("Listing ID not found.");
		return false;
	}
	
	let url = `${siteUrl}/account/saved-posts/toggle`;
	let _tokenEl = document.querySelector('input[name=_token]');
	let data = {
		'post_id': postId,
		'_token': _tokenEl.value ?? null
	};
	
	showWaitingDialog();
	
	try {
		const json = await httpRequest('POST', url, data);
		
		hideWaitingDialog();
		
		/* console.log(json); */
		if (isNotDefined(json.isLoggedUser)) {
			return false;
		}
		
		const isNotLogged = (json.isLoggedUser !== true);
		const isUnauthorized = (json.status && (json.status === 401 || json.status === 419));
		
		if (isNotLogged || isUnauthorized) {
			openLoginModal();
			
			if (json.message) {
				jsAlert(json.message, 'error', false);
			}
			
			return false;
		}
		
		/* Logged Users - Notification */
		if (json.isSaved === true) {
			if (el.classList.contains('btn')) {
				const saveBtnEl = document.getElementById(json.postId);
				saveBtnEl.classList.remove('btn-outline-secondary');
				saveBtnEl.classList.add('btn-success');
			}
			
			const tooltip = 'data-bs-toggle="tooltip" title="' + lang.labelSavePostRemove + '"';
			el.innerHTML = '<i class="bi bi-heart-fill" ' + tooltip + '></i>';
			
			jsAlert(json.message, 'success');
		} else {
			if (el.classList.contains('btn')) {
				const saveBtnEl = document.getElementById(json.postId);
				saveBtnEl.classList.remove('btn-success');
				saveBtnEl.classList.add('btn-outline-secondary');
			}
			
			const tooltip = 'data-bs-toggle="tooltip" title="' + lang.labelSavePostSave + '"';
			el.innerHTML = '<i class="bi bi-heart" ' + tooltip + '></i>';
			
			jsAlert(json.message, 'success');
		}
		
		return false;
	} catch (error) {
		hideWaitingDialog();
		
		if (error.response && error.response.status) {
			const response = error.response;
			if (response.status === 401 || response.status === 419) {
				/*
				 * Since the modal login code is injected only for guests,
				 * the line below can be fired only for guests (i.e. when user is not logged in)
				 */
				openLoginModal();
				
				if (!isLoggedUser) {
					return false;
				}
			}
		}
		
		let message = getErrorMessage(error);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
		
		return false;
	}
}

/**
 * Save Search
 * @param el
 * @returns {boolean}
 */
async function saveSearch(el) {
	if (el.tagName.toLowerCase() === 'i') {
		el = el.parentElement;
	}
	
	let searchUrl = el.dataset.searchUrl;
	let resultsCount = el.dataset.resultsCount;
	
	if (!searchUrl) {
		console.error("Search URL not found.");
		return false;
	}
	
	showWaitingDialog();
	
	const url = `${siteUrl}/account/saved-searches/store`;
	const _tokenEl = document.querySelector('input[name=_token]');
	const data = {
		'search_url': searchUrl,
		'results_count': resultsCount,
		'_token': _tokenEl.value ?? null
	};
	
	try {
		const json = await httpRequest('POST', url, data);
		
		hideWaitingDialog();
		
		/* console.log(json); */
		if (typeof json.isLoggedUser === 'undefined') {
			return false;
		}
		
		if (json.isLoggedUser !== true) {
			openLoginModal();
			return false;
		}
		
		/* Logged Users - Notification */
		jsAlert(json.message, 'success');
		
		return false;
	} catch (error) {
		hideWaitingDialog();
		
		if (error.response && error.response.status) {
			const response = error.response;
			if (response.status === 401 || response.status === 419) {
				openLoginModal();
				return false;
			}
		}
		
		const message = getErrorMessage(error);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
		
		return false;
	}
}
