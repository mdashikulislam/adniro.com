/*
 * adniro: tidying my own list of chats (assets/js/app/chat.js): archive, mute, mark as unread or read. The
 * same three choices are in each row's menu (chat-inbox.js draws it) and in the open conversation's menu.
 * The other person sees none of it.
 */
(function (window) {
	'use strict';

	/**
	 * @param {{T: Object, URLS: Object, root: Element, els: Object, store: Object, kit: Object}} ctx
	 * @param {{inbox: Object, close: Function}} parts
	 */
	window.AdnChatTidy = function (ctx, parts) {
		var T = ctx.T;
		var store = ctx.store;
		var kit = ctx.kit;
		var headButtons = ctx.els.open.querySelectorAll('.adn-chat-head [data-chat-tidy]');
		// What to say once it is done: [turned on, turned off]
		var DONE = {
			archived: [T.archived_done, T.unarchived_done],
			muted: [T.muted_done, T.unmuted_done],
			unread: [T.marked_unread, T.marked_read]
		};

		function openThread() {
			return store.open && store.open.thread ? store.open.thread : null;
		}

		/** The open conversation's menu says what each choice would do now. */
		function refresh() {
			var thread = openThread();
			var choices = thread ? parts.inbox.tidyChoices(thread) : [];
			headButtons.forEach(function (button) {
				var choice = choices.filter(function (item) {
					return item.name === button.dataset.chatTidy;
				})[0];
				button.parentNode.hidden = !choice;
				if (choice) {
					button.dataset.value = choice.value ? '1' : '0';
					button.querySelector('i').className = 'bi bi-' + choice.icon;
					button.querySelector('span').textContent = choice.label;
				}
			});
		}

		function apply(threadId, name, value, fromRow) {
			var payload = {};
			payload[name] = value;

			kit.request('PUT', kit.urlFor(ctx.URLS.tidy, threadId), payload).then(function (data) {
				var open = openThread();
				var isOpen = !!open && open.id === threadId;
				parts.inbox.patch(threadId, data.thread);
				if (kit.live()) {
					kit.live().setUnread(data.unread_total);
				}
				if (isOpen && name === 'unread' && value) {
					parts.close(threadId); // on screen it would be read again at once
				} else if (isOpen) {
					refresh();
				}
				kit.toast(DONE[name][value ? 0 : 1]);
				// The keyboard goes back to the row's menu button; if the row has left the list, to the list's
				// first chat, or to the search box
				if (fromRow && !parts.inbox.focus(threadId, true)) {
					(ctx.root.querySelector('.adn-chat-row') || ctx.root.querySelector('[data-chat-search]')).focus();
				}
			}).catch(function (error) {
				kit.toast(error.message, 'error');
			});
		}

		// The open conversation changes under its menu (it is read, a message arrives): the choices are worked
		// out again each time the menu opens
		ctx.els.open.querySelector('[data-chat-menu-btn]').addEventListener('show.bs.dropdown', refresh);

		ctx.root.addEventListener('click', function (event) {
			var button = event.target.closest('[data-chat-tidy]');
			if (!button) {
				return;
			}
			var row = button.closest('.adn-chat-row-wrap');
			var open = openThread();
			var threadId = row ? Number(row.dataset.threadId) : (open ? open.id : 0);
			if (threadId) {
				apply(threadId, button.dataset.chatTidy, button.dataset.value === '1', !!row);
			}
		});

		return {refresh: refresh};
	};
})(window);
