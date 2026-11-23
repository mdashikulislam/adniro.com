/*
 * adniro: "Chat with seller" as a pop-up on a listing's page (front/post/show/partials/chat-quick.blade.php).
 *
 * - Opens only when the page is reached from a listing card's chat button (the address ends in #chat). The
 *   page's own "Chat with seller" buttons are not touched: they go to the chat, as before. A guest gets the
 *   sign-in pop-up instead.
 * - Sends the first message about the listing, then offers to open the chat or to keep browsing. The message
 *   has its own id, so a second tap or a retry never sends it twice.
 * Text goes in through textContent and value: nothing a member wrote is treated as HTML.
 */
(function (window, document) {
	'use strict';

	var HASH = '#chat';
	var SESSION_ENDED = [401, 419];
	var TOO_FAST = 429;

	var quick = document.getElementById('adnChatQuick'); // members
	var signIn = document.getElementById('quickLogin'); // guests

	function show(dialog) {
		if (dialog && window.bootstrap) {
			window.bootstrap.Modal.getOrCreateInstance(dialog).show();
		}
	}

	/** Reached from a listing card's chat button: the pop-up opens, and the address is put back as it was. */
	function openFromAddress() {
		if (window.location.hash !== HASH) {
			return;
		}
		window.history.replaceState(null, '', window.location.pathname + window.location.search);
		show(quick || signIn);
	}

	function uuid() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			return window.crypto.randomUUID();
		}
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (char) {
			var random = Math.random() * 16 | 0;
			return (char === 'x' ? random : (random & 0x3 | 0x8)).toString(16);
		});
	}

	function firstError(data) {
		var errors = data && data.errors ? data.errors : {};
		var keys = Object.keys(errors);
		if (keys.length && errors[keys[0]].length) {
			return errors[keys[0]][0];
		}
		return data && typeof data.message === 'string' ? data.message : '';
	}

	function wire() {
		var form = quick.querySelector('[data-quick-form]');
		var input = quick.querySelector('[data-quick-input]');
		var send = quick.querySelector('[data-quick-send]');
		var error = quick.querySelector('[data-quick-error]');
		var sent = quick.querySelector('[data-quick-sent]');
		var openChat = quick.querySelector('[data-quick-open]');
		var messageId = uuid();
		var isSending = false;

		function showError(text) {
			error.textContent = text || '';
			error.hidden = !text;
		}

		function refresh() {
			send.disabled = isSending || !input.value.trim();
			// What is typed sets its own direction; empty, the box follows the page
			if (input.value) {
				input.setAttribute('dir', 'auto');
			} else {
				input.removeAttribute('dir');
			}
		}

		function showSent(threadUrl) {
			if (threadUrl) {
				openChat.href = threadUrl;
			}
			form.hidden = true;
			sent.hidden = false;
			sent.focus();
			input.value = '';
			messageId = uuid(); // the next message is a new one
			refresh();
		}

		function submit() {
			var text = input.value.trim();
			if (!text || isSending) {
				return;
			}
			var csrf = document.querySelector('meta[name="csrf-token"]');
			var body = new FormData();
			body.append('body', text);
			body.append('client_uuid', messageId);

			isSending = true;
			showError('');
			quick.classList.add('is-sending');
			refresh();

			fetch(quick.dataset.startUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''}
			}).then(function (response) {
				return response.json().catch(function () {
					return {};
				}).then(function (data) {
					if (response.ok) {
						showSent(data.thread ? data.thread.url : '');
						return;
					}
					if (SESSION_ENDED.indexOf(response.status) !== -1) {
						window.location.reload(); // sign in again
						return;
					}
					showError(response.status === TOO_FAST ? quick.dataset.tooFast : (firstError(data) || quick.dataset.failed));
				});
			}, function () {
				showError(quick.dataset.failed);
			}).then(function () {
				isSending = false;
				quick.classList.remove('is-sending');
				refresh();
			});
		}

		quick.querySelectorAll('[data-quick-chip]').forEach(function (chip) {
			chip.addEventListener('click', function () {
				input.value = chip.textContent.trim();
				refresh();
				input.focus();
			});
		});
		input.addEventListener('input', refresh);
		input.addEventListener('keydown', function (event) {
			var isComputer = !window.matchMedia('(pointer: coarse)').matches;
			if (event.key === 'Enter' && !event.shiftKey && !event.isComposing && isComputer) {
				event.preventDefault();
				submit();
			}
		});
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			submit();
		});

		// On a computer the message box is ready to type in; on a phone the keyboard waits for a tap
		quick.addEventListener('shown.bs.modal', function () {
			if (!form.hidden && !window.matchMedia('(pointer: coarse)').matches) {
				input.focus();
			}
		});
		// Closed: next time it starts again with the message box
		quick.addEventListener('hidden.bs.modal', function () {
			form.hidden = false;
			sent.hidden = true;
			showError('');
		});
	}

	if (quick) {
		wire();
	}
	openFromAddress();
	window.addEventListener('hashchange', openFromAddress);
})(window, document);
