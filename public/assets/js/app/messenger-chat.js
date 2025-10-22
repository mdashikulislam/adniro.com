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

// Define error message if not already defined
if (typeof updateChatErrorMessage === 'undefined') {
	var updateChatErrorMessage = 'The chat update could not be done.';
}

// Global flag to control auto-scrolling of chat history
var autoScrollEnabled = true;

// Initialize chat functionality when document is ready
onDocumentReady((event) => {
	// Cache DOM elements for performance
	const chatTextField = document.getElementById('body');
	const chatFileField = document.getElementById('addFile');
	const messageChatHistory = document.getElementById('messageChatHistory');
	const chatForm = document.getElementById('chatForm');
	
	// Automatically scroll chat history every 2 seconds
	setInterval(scrollChatHistoryToBottom, 2000);
	
	// Disable auto-scroll when user manually scrolls
	messageChatHistory.addEventListener('scroll', () => {
		autoScrollEnabled = false;
	});
	
	// Set initial focus on the chat text input
	chatTextField.focus();
	
	// Auto-refresh chat messages if a timer is defined
	if (typeof timerNewMessagesChecking !== 'undefined' && timerNewMessagesChecking > 0) {
		var showNewMsgTimer = setInterval(() => {
			fetchMessages(window.location.href, true);
		}, timerNewMessagesChecking);
	}
	
	// Handle pagination link clicks for loading more messages
	document.addEventListener('click', (e) => {
		const link = e.target.closest('#linksMessages a');
		if (link) {
			e.preventDefault();
			if (typeof showNewMsgTimer !== 'undefined') {
				clearInterval(showNewMsgTimer); // Stop auto-refresh on manual navigation
			}
			
			showWaitingDialog();
			
			fetchMessages(link.getAttribute('href'), false, false);
			
			return false;
		}
	});
	
	// Handle form submission for sending new messages
	const handleFormSubmit = (e) => {
		e.preventDefault(); // Prevent native form submission
		
		// Only proceed if there's text or a file
		if (chatTextField.value.trim() || chatFileField.files.length > 0) {
			// Get the form action URL and the fields data
			const formUrl = chatForm.getAttribute('action');
			const formData = new FormData(chatForm);
			
			// Clear fields immediately before sending AJAX
			clearTextField(chatTextField);
			clearFileInput(chatFileField);
			
			// Remove file icons if present
			const wrapperEl = chatFileField.closest('.button-wrap');
			if (wrapperEl) {
				const existingFileIcon = wrapperEl.querySelector('.bi-file-earmark');
				const existingFileButton = wrapperEl.querySelector('.bi-trash3');
				clearFileIconAndButton(existingFileIcon, existingFileButton);
			}
			
			updateChat(formUrl, formData); // Send the message
		}
	};
	
	// Attach submit handler to the form
	chatForm.addEventListener('submit', handleFormSubmit);
	
	// Handle keydown events for maxlength and Enter key submission
	chatTextField.addEventListener('keydown', (e) => {
		const key = e.which || e.keyCode;
		
		// Submit on Enter key (without Shift)
		if (key === 13 && !e.shiftKey) {
			e.preventDefault(); // Prevent newline in textarea
			handleFormSubmit(e);
		}
		// Enforce maxlength on other printable keys
		else if (key >= 33) {
			const maxLength = parseInt(chatTextField.getAttribute('maxlength'));
			const length = chatTextField.value.length;
			if (length >= maxLength) {
				e.preventDefault();
			}
		}
	});
	
	// Handle file input changes to update UI
	chatFileField.addEventListener('change', (e) => {
		appendIconsInFileInputWrapper(chatFileField);
	});
});

/**
 * Fetch chat messages from the server
 * @param url
 * @param firstLoading
 * @param canBeAutoScroll
 */
function fetchMessages(url, firstLoading = false, canBeAutoScroll = true) {
	httpRequest('GET', url)
	.then(data => {
		hideWaitingDialog();
		
		const successMsg = document.getElementById('successMsg');
		const errorMsg = document.getElementById('errorMsg');
		const messageChatHistory = document.getElementById('messageChatHistory');
		
		// Clear previous messages
		successMsg.innerHTML = '';
		if (!successMsg.classList.contains('d-none')) {
			successMsg.classList.add('d-none');
		}
		errorMsg.innerHTML = '';
		if (!errorMsg.classList.contains('d-none')) {
			errorMsg.classList.add('d-none');
		}
		
		if (typeof data.messages === 'undefined' || typeof data.links === 'undefined') return false;
		
		// Reset chat history on first load
		if (firstLoading) {
			messageChatHistory.innerHTML = '<div id="linksMessages" class="text-center"></div>';
		}
		
		// Ensure linksMessages is updated and attached before inserting messages
		const linksMessages = document.getElementById('linksMessages');
		if (linksMessages) {
			linksMessages.innerHTML = data.links;
			
			// Hide pagination links if no more links are available
			if (data.links === null || data.links.trim() === '') {
				linksMessages.style.display = 'none';
			} else {
				linksMessages.style.display = 'block'; // Ensure it’s visible if links exist
			}
			
			// Append new messages after the links
			linksMessages.insertAdjacentHTML('afterend', data.messages);
		}
		
		// Enable auto-scroll if allowed
		autoScrollEnabled = canBeAutoScroll;
	})
	.catch((reason) => {
		hideWaitingDialog();
		console.error(reason);
		jsAlert(loadingErrorMessage, 'error', false);
	});
}

/**
 * Send a new chat message to the server
 * @param formUrl
 * @param formData
 */
function updateChat(formUrl, formData) {
	showWaitingDialog();
	
	// Send the message via POST with FormData
	httpRequest('POST', formUrl, formData)
	.then(data => {
		fetchMessages(formUrl, true); // Refresh messages after successful send
		
		const chatTextField = document.getElementById('body');
		chatTextField.focus(); // Refocus after successful send
	})
	.catch(error => {
		hideWaitingDialog();
		
		const successMsg = document.getElementById('successMsg');
		const errorMsg = document.getElementById('errorMsg');
		
		// Clear previous messages
		successMsg.innerHTML = '';
		if (!successMsg.classList.contains('d-none')) {
			successMsg.classList.add('d-none');
		}
		errorMsg.innerHTML = '';
		
		// Display error message from server response
		if (error.response) {
			let appended = false;
			
			if (error.response.message) {
				errorMsg.innerHTML += error.response.message;
				appended = true;
			}
			
			if (error.response.data?.body) {
				if (Array.isArray(error.response.data.body)) {
					errorMsg.innerHTML += '<ul></ul>';
					error.response.data.body.forEach(item => {
						errorMsg.querySelector('ul').innerHTML += `<li>${item}</li>`;
					});
					appended = true;
				}
			}
			
			if (appended) {
				errorMsg.classList.remove('d-none');
			} else {
				if (!errorMsg.classList.contains('d-none')) {
					errorMsg.classList.add('d-none');
				}
			}
			
			if (!appended) errorMsg.innerHTML = updateChatErrorMessage;
		} else {
			errorMsg.innerHTML = updateChatErrorMessage;
			if (!errorMsg.classList.contains('d-none')) {
				errorMsg.classList.add('d-none');
			}
		}
	});
}

/**
 * Scroll chat history to the bottom with animation
 */
function scrollChatHistoryToBottom() {
	if (autoScrollEnabled) {
		const el = document.getElementById('messageChatHistory');
		if (el) {
			el.scrollTo({
				top: el.scrollHeight,
				behavior: 'smooth'
			});
		}
	}
}

/**
 * Add file icons to the file input wrapper
 * @param addedFileEl
 */
function appendIconsInFileInputWrapper(addedFileEl) {
	if (!(addedFileEl instanceof HTMLElement)) {
		return;
	}
	
	const wrapperEl = addedFileEl.closest('.button-wrap');
	if (!wrapperEl || !wrapperEl.querySelector) {
		return; // Exit if wrapper is invalid
	}
	
	// Add icons only if they don’t already exist
	if (!wrapperEl.querySelector('.bi-file-earmark')) {
		const fileIcon = document.createElement('i');
		fileIcon.className = 'bi bi-file-earmark fs-3 text-muted';
		
		const trashLink = document.createElement('a');
		trashLink.href = '#';
		trashLink.style.cursor = 'pointer';
		
		const trashIcon = document.createElement('i');
		trashIcon.className = 'bi bi-trash3 fs-3 text-danger me-2';
		
		// Clear file input and icons on trash click
		trashLink.addEventListener('click', (e) => {
			e.preventDefault();
			clearFileInput(document.getElementById('addFile'));
			clearFileIconAndButton(fileIcon, trashLink);
		});
		
		trashLink.appendChild(trashIcon);
		
		// Insert icons before existing content or append if empty
		if (wrapperEl.firstChild) {
			wrapperEl.insertBefore(fileIcon, wrapperEl.firstChild);
			wrapperEl.insertBefore(trashLink, fileIcon.nextSibling);
		} else {
			wrapperEl.appendChild(fileIcon);
			wrapperEl.appendChild(trashLink);
		}
	}
}

/**
 * Clear the text input field
 * @param chatTextField
 */
function clearTextField(chatTextField) {
	if (chatTextField) chatTextField.value = '';
}

/**
 * Clear the file input field
 * @param chatFileField
 */
function clearFileInput(chatFileField) {
	if (chatFileField) chatFileField.value = '';
}

/**
 * Remove file icon and trash button from the UI
 * @param fileIcon
 * @param trashLink
 */
function clearFileIconAndButton(fileIcon, trashLink) {
	if (fileIcon) fileIcon.remove();
	if (trashLink) trashLink.remove();
}
