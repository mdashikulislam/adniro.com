/*
 * adniro: the "Email the seller" pop-up on a listing posted without a member account
 * (front/post/show/partials/email-seller.blade.php). Sends the message without leaving the page.
 */
(function (window, document) {
	'use strict';

	var TOO_MANY = 429;
	var form = document.querySelector('[data-email-seller]');
	if (!form) {
		return;
	}

	var message = form.querySelector('textarea[name="message"]');
	var send = form.querySelector('[data-email-seller-send]');
	var errorBox = form.querySelector('[data-email-seller-error]');

	function showError(text) {
		errorBox.textContent = text || '';
		errorBox.hidden = !text;
	}

	function csrfToken() {
		var meta = document.querySelector('meta[name="csrf-token"]');
		return meta ? meta.getAttribute('content') : '';
	}

	function firstError(data) {
		var errors = data && data.errors ? Object.keys(data.errors) : [];
		return errors.length ? data.errors[errors[0]][0] : (data && data.message) || '';
	}

	function closeDialog() {
		var modal = window.bootstrap ? window.bootstrap.Modal.getInstance(form.closest('.modal')) : null;
		if (modal) {
			modal.hide();
		}
	}

	function post(text) {
		return fetch(form.dataset.url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
				'X-CSRF-TOKEN': csrfToken()
			},
			body: JSON.stringify({message: text})
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (data) {
				if (response.ok) {
					return data;
				}
				throw new Error(response.status === TOO_MANY ? form.dataset.tooFast : (firstError(data) || form.dataset.failed));
			});
		});
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		var text = message.value.trim();
		// Checked here first: a message the server would refuse should not count against the sending limit
		if (text.length < Number(form.dataset.min)) {
			showError(form.dataset.tooShort);
			message.focus();
			return;
		}

		showError('');
		send.disabled = true;
		post(text).then(function () {
			message.value = '';
			closeDialog();
			if (window.Swal) {
				window.Swal.fire({toast: true, position: 'top', timer: 4000, showConfirmButton: false, icon: 'success', text: form.dataset.sent});
			}
		}).catch(function (error) {
			showError(error.message || form.dataset.failed);
		}).then(function () {
			send.disabled = false;
		});
	});
})(window, document);
