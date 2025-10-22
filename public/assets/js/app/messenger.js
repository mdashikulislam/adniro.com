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

if (typeof loadingImage === 'undefined') {
	var loadingImage = '/images/spinners/fading-line.gif';
}
if (typeof loadingErrorMessage === 'undefined') {
	var loadingErrorMessage = 'Threads could not be loaded.';
}
if (typeof actionErrorMessage === 'undefined') {
	var actionErrorMessage = 'This action could not be done.';
}
if (typeof actionText === 'undefined') {
	var actionText = 'Action';
}

onDocumentReady((event) => {
	
	$('ul.dropdown-menu-sort li a.dropdown-item').click(function (e) {
		$('ul.dropdown-menu-sort li a.dropdown-item').removeClass('active');
		$(this).addClass('active');
		let selectedText = $(this).text();
		$('.dropdown-menu-sort-selected').text(selectedText);
	});
	
	$('.markAllAsRead').click(function () {
		Swal.fire({
			text: langLayout.confirm.message.question,
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: langLayout.confirm.button.yes,
			cancelButtonText: langLayout.confirm.button.no
		}).then((result) => {
			if (result.isConfirmed) {
				
				markAllAsRead();
				
			}
		});
	});
	
	/* Check all entries */
	$('#form-check-all').click(function (e) {
		e.stopPropagation();
		$('.message-list input:checkbox').not(this).prop('checked', this.checked);
	});
	
	
	/* ====== */
	
	
	/* AJAX data loading & pagination */
	$(document).on('click', '#linksThreads a', function (e) {
		e.preventDefault();
		
		/* $('#linksThreads a').css('color', '#dfecf6'); */
		
		let url = $(this).attr('href');
		getThreads(url);
		window.history.pushState('', '', url);
	});
	
	/* Confirm Actions */
	$(document).on('click', '.list-box-action a, #groupedAction a, .call-xhr-action a', function (e) {
		e.preventDefault();
		
		let clickedEl = $(this);
		
		Swal.fire({
			text: langLayout.confirm.message.question,
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: langLayout.confirm.button.yes,
			cancelButtonText: langLayout.confirm.button.no
		}).then((result) => {
			if (result.isConfirmed) {
				
				let url = clickedEl.attr('href');
				let currentPageFilter = urlBuilder(url).getParameter('filter');
				
				if (clickedEl.closest('#groupedAction').length) {
					let checkedCheckboxes = getCheckedCheckboxes();
					makeAction(currentPageFilter, url, false, checkedCheckboxes);
				} else {
					if (url !== 'undefined') {
						makeAction(currentPageFilter, url, clickedEl);
					}
				}
				
			}
		});
		
		return false;
	});
	
	/* Refresh Threads */
	$(document).on('click', '#btnRefresh', function (e) {
		e.preventDefault();
		
		let url = window.location.href;
		getThreads(url);
	});
	
});

/**
 * Function of AJAX data loading & pagination
 * @param url
 */
function getThreads(url) {
	showWaitingDialog();
	
	let ajax = $.ajax({
		method: 'GET',
		url: url
	});
	ajax.done(function (xhr) {
		hideWaitingDialog();
		if (typeof xhr.threads === 'undefined' || typeof xhr.links === 'undefined') {
			return false;
		}
		
		$('#listThreads').html(xhr.threads);
		$('#linksThreads').html(xhr.links);
		
		/* Check Threads with New Messages */
		checkNewMessages();
		
		/* Clear all alert message */
		try {
			sleep(6000).then(() => {
				$('#successMsg').empty().removeClass('d-none').addClass('d-none');
				$('#errorMsg').empty().removeClass('d-none').addClass('d-none');
			});
		} catch (error) {
			$('#successMsg').empty().removeClass('d-none').addClass('d-none');
			$('#errorMsg').empty().removeClass('d-none').addClass('d-none');
		}
	});
	ajax.fail(function () {
		hideWaitingDialog();
		jsAlert(loadingErrorMessage, 'error', false);
	});
}

/**
 * Get checked checkboxes
 * @returns {*[]}
 */
function getCheckedCheckboxes() {
	let checkedList = [];
	
	$('.message-list input[type=checkbox]:checked').each(function () {
		checkedList.push($(this).val());
	});
	
	return checkedList;
}

/**
 * Make action
 * @param currentPageFilter
 * @param url
 * @param clickedEl
 * @param checkedCheckboxes
 */
function makeAction(currentPageFilter, url, clickedEl, checkedCheckboxes) {
	let options = {
		method: 'GET',
		url: url
	};
	
	if (checkedCheckboxes) {
		options = {
			method: 'POST',
			url: url,
			data: {
				'entries': checkedCheckboxes,
				'_token': $('input[name=_token]').val()
			}
		};
	}
	
	let ajax = $.ajax(options);
	ajax.done(function (xhr) {
		if (typeof xhr.type === 'undefined' || typeof xhr.success === 'undefined' || typeof xhr.msg === 'undefined') {
			return false;
		}
		
		let currentUrl = window.location.href;
		let titleIs, newActionUrl, actionType;
		
		if (clickedEl) {
			const clickedElLine = clickedEl.closest('#listThreads > div.row');
			
			if (xhr.type === 'markAsRead' || xhr.type === 'markAsUnread') {
				if (currentPageFilter === 'unread') {
					
					clickedEl.tooltip('hide');
					getThreads(currentUrl);
					
				} else {
					const isSeen = !(
						clickedElLine
						&& (clickedElLine.hasClass('bg-warning-subtle') && clickedElLine.hasClass('fw-bold'))
					);
					
					titleIs = isSeen ? title.seen : title.notSeen;
					clickedEl.attr('title', titleIs).attr('data-original-title', titleIs).tooltip('hide');
					
					if (typeof xhr.baseUrl !== 'undefined') {
						actionType = isSeen ? 'markAsRead' : 'markAsUnread';
						newActionUrl = urlBuilder(xhr.baseUrl, true).setParameters({type: actionType}).value();
						
						clickedEl.attr('href', newActionUrl);
					}
					
					clickedEl.find('i').toggleClass('fa-envelope-open fa-envelope');
					if (clickedElLine) {
						clickedElLine.toggleClass('bg-warning-subtle');
						clickedElLine.toggleClass('fw-bold');
					}
					
					checkNewMessages();
				}
			}
			if (xhr.type === 'markAsImportant' || xhr.type === 'markAsNotImportant') {
				if (currentPageFilter === 'important') {
					
					clickedEl.tooltip('hide');
					getThreads(currentUrl);
					
				} else {
					let isImportant = clickedEl.hasClass('markAsNotImportant');
					
					titleIs = isImportant ? title.important : title.notImportant;
					clickedEl.attr('title', titleIs).attr('data-original-title', titleIs).tooltip('hide');
					
					clickedEl.find('i').toggleClass('fa-solid fa-regular');
					clickedEl.toggleClass('markAsNotImportant markAsImportant');
					
					if (typeof xhr.baseUrl !== 'undefined') {
						actionType = isImportant ? 'markAsImportant' : 'markAsNotImportant';
						newActionUrl = urlBuilder(xhr.baseUrl, true).setParameters({type: actionType}).value();
						clickedEl.attr('href', newActionUrl);
					}
				}
			}
			if (xhr.type === 'delete') {
				
				clickedEl.tooltip('hide');
				getThreads(currentUrl);
				
			}
		} else {
			/* Close the grouped actions dropdown menu */
			$('#groupedAction').trigger('click.bs.dropdown');
			/* Uncheck the 'check all' checkbox */
			$('#form-check-all').prop('checked', false);
			
			/* Refresh Data */
			getThreads(currentUrl);
			
			$('.dropdown-menu-sort-selected').text(actionText);
		}
		
		if (xhr.success) {
			$('#errorMsg').empty().removeClass('d-none').addClass('d-none');
			$('#successMsg').html(xhr.msg).removeClass('d-none');
		} else {
			$('#successMsg').empty().removeClass('d-none').addClass('d-none');
			$('#errorMsg').html(xhr.msg).removeClass('d-none');
		}
	});
	ajax.fail(function (xhr) {
		jsAlert(actionErrorMessage, 'error', false);
	});
}

/**
 * Mark all as read
 */
function markAllAsRead() {
	let ajax = $.ajax({
		method: 'POST',
		url: `${siteUrl}/account/messages/actions?type=markAllAsRead`,
		data: {
			'_token': $('input[name=_token]').val()
		}
	});
	ajax.done(function (xhr) {
		if (typeof xhr.success === 'undefined' || typeof xhr.msg === 'undefined') {
			return false;
		}
		
		let url = window.location.href;
		getThreads(url);
		
		if (xhr.success) {
			$('#errorMsg').empty().removeClass('d-none').addClass('d-none');
			$('#successMsg').html(xhr.msg).removeClass('d-none');
		} else {
			$('#successMsg').empty().removeClass('d-none').addClass('d-none');
			$('#errorMsg').html(xhr.msg).removeClass('d-none');
		}
	});
	ajax.fail(function (xhr) {
		jsAlert(actionErrorMessage, 'error', false);
	});
}
