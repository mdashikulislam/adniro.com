/*
 * adniro: chat safety (assets/js/app/chat.js): block or unblock the other person, and report the conversation
 * to the admins. Confirmations use SweetAlert2, the site's one dialog style.
 */
(function (window) {
	'use strict';

	/**
	 * @param {{T: Object, URLS: Object, els: Object, store: Object, kit: Object}} ctx
	 * @param {function(Object, Object): void} onBlockChanged called with the open conversation and its new state
	 */
	window.AdnChatSafety = function (ctx, onBlockChanged) {
		var T = ctx.T;
		var URLS = ctx.URLS;
		var els = ctx.els;
		var store = ctx.store;
		var fill = ctx.kit.fill;
		var urlFor = ctx.kit.urlFor;
		var request = ctx.kit.request;
		var toast = ctx.kit.toast;

		function setBlocked(blocked) {
			var open = store.open;
			var other = open.other;
			request(blocked ? 'POST' : 'DELETE', urlFor(URLS.block, other.id)).then(function () {
				return request('GET', urlFor(URLS.open, open.thread.id));
			}).then(function (data) {
				if (store.open !== open) {
					return;
				}
				onBlockChanged(open, data.thread);
				toast(fill(blocked ? T.blocked_done : T.unblocked_done, {name: other.name}));
			}).catch(function (error) {
				toast(error.message, 'error');
			});
		}

		function confirmBlock() {
			var name = store.open.other.name;
			if (typeof window.Swal === 'undefined') {
				if (window.confirm(fill(T.block_title, {name: name}))) {
					setBlocked(true);
				}
				return;
			}
			window.Swal.fire({
				title: fill(T.block_title, {name: name}),
				text: T.block_text,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: T.block_confirm,
				cancelButtonText: T.cancel
			}).then(function (result) {
				if (result.isConfirmed) {
					setBlocked(true);
				}
			});
		}

		function reportModal() {
			return window.bootstrap && els.reportModal ? window.bootstrap.Modal.getOrCreateInstance(els.reportModal) : null;
		}

		function submitReport(form) {
			var errorEl = form.querySelector('[data-chat-report-error]');
			var sendBtn = form.querySelector('[data-chat-report-send]');
			var reason = form.querySelector('input[name="reason"]:checked');
			errorEl.hidden = !!reason;
			errorEl.textContent = reason ? '' : T.report_pick;
			if (!reason || !store.open || !store.open.thread) {
				return;
			}

			sendBtn.disabled = true;
			request('POST', urlFor(URLS.report, store.open.thread.id), {reason: reason.value, note: form.elements.note.value}).then(function () {
				form.reset();
				reportModal().hide();
				toast(T.report_done);
			}).catch(function (error) {
				errorEl.textContent = error.message;
				errorEl.hidden = false;
			}).then(function () {
				sendBtn.disabled = false;
			});
		}

		return {
			setBlocked: setBlocked,
			confirmBlock: confirmBlock,
			reportModal: reportModal,
			submitReport: submitReport
		};
	};
})(window);
