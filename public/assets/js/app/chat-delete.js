/*
 * adniro: deleting in the chat (assets/js/app/chat.js): taking back a message I sent (both people then see
 * "Message deleted"), and deleting a conversation from my own list (the other person keeps it).
 */
(function (window) {
	'use strict';

	/**
	 * @param {{T: Object, URLS: Object, els: Object, store: Object, kit: Object}} ctx
	 * @param {{log: Object, inbox: Object, close: Function}} parts
	 */
	window.AdnChatDelete = function (ctx, parts) {
		var T = ctx.T;
		var URLS = ctx.URLS;
		var store = ctx.store;
		var kit = ctx.kit;

		function ask(title, text, confirmText) {
			if (typeof window.Swal === 'undefined') {
				return Promise.resolve(false);
			}
			return window.Swal.fire({
				icon: 'warning',
				title: title,
				text: text,
				showCancelButton: true,
				confirmButtonText: confirmText,
				cancelButtonText: T.cancel,
				reverseButtons: true
			}).then(function (result) {
				return result.isConfirmed;
			});
		}

		/** Puts "Message deleted" in the place of a message of the open conversation. */
		function markUnsent(payload) {
			var open = store.open;
			if (!open || !open.thread || open.thread.id !== payload.thread_id) {
				return;
			}
			var message = open.messages.filter(function (item) {
				return item.id === payload.id;
			})[0];
			if (!message) {
				return;
			}
			if (message.photo && message.image) {
				URL.revokeObjectURL(message.image.url);
			}
			Object.assign(message, payload, {photo: null, state: 'sent', error: null});
			parts.log.redraw(message);
		}

		function unsend(key) {
			var open = store.open;
			var message = open ? open.messages.filter(function (item) {
				return (item.client_uuid || ('m' + item.id)) === key;
			})[0] : null;
			if (!message || !message.id) {
				return;
			}

			ask(T.unsend_title, T.unsend_text, T.unsend_confirm).then(function (confirmed) {
				if (!confirmed) {
					return;
				}
				var url = kit.urlFor(URLS.unsend, open.thread.id).replace('__MESSAGE__', message.id);
				return kit.request('DELETE', url).then(function (data) {
					markUnsent(data.message);
				});
			}).catch(function (error) {
				kit.toast(error.message, 'error');
			});
		}

		function deleteConversation() {
			var open = store.open;
			if (!open || !open.thread) {
				return;
			}
			var threadId = open.thread.id;

			ask(kit.fill(T.delete_chat_title, {name: open.other.name}), T.delete_chat_text, T.delete_chat_confirm).then(function (confirmed) {
				if (!confirmed) {
					return;
				}
				return kit.request('DELETE', kit.urlFor(URLS.hide, threadId)).then(function (data) {
					parts.inbox.remove(threadId);
					if (kit.live()) {
						kit.live().setUnread(data.unread_total);
					}
					parts.close();
					kit.toast(T.delete_chat_done);
				});
			}).catch(function (error) {
				kit.toast(error.message, 'error');
			});
		}

		/** From a catch-up answer: messages of the open conversation that were taken back while this tab was away. */
		function markUnsentIds(ids) {
			var open = store.open;
			(ids || []).forEach(function (id) {
				var message = open ? open.messages.filter(function (item) {
					return item.id === id && !item.unsent;
				})[0] : null;
				if (message) {
					markUnsent({id: id, thread_id: open.thread.id, unsent: true, body: '', image: null, reply_to: null, caution: false, offer: null, auto: false});
				}
			});
		}

		ctx.els.messages.addEventListener('click', function (event) {
			var button = event.target.closest('[data-chat-unsend]');
			if (button) {
				unsend(button.dataset.chatUnsend);
			}
		});
		ctx.els.deleteChat.addEventListener('click', deleteConversation);

		// The other person took a message back, or I did in another tab
		window.addEventListener('adn:chat:unsent', function (event) {
			parts.inbox.patch(event.detail.thread.id, {last: event.detail.thread.last, unread: event.detail.thread.unread});
			markUnsent(event.detail.message);
		});

		return {markUnsentIds: markUnsentIds};
	};
})(window);
