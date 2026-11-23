/*
 * adniro: the live chat inbox (assets/js/app/chat.js): the list of conversations, its filter, and keeping a
 * conversation's row in step with new messages and read receipts.
 */
(function (window) {
	'use strict';

	/** @param {{T: Object, URLS: Object, els: Object, store: Object, kit: Object}} ctx */
	window.AdnChatInbox = function (ctx) {
		var T = ctx.T;
		var URLS = ctx.URLS;
		var els = ctx.els;
		var store = ctx.store;
		var el = ctx.kit.el;
		var icon = ctx.kit.icon;
		var fill = ctx.kit.fill;
		var request = ctx.kit.request;
		var shortTime = ctx.kit.shortTime;
		var avatarInto = ctx.kit.avatarInto;

		function openThreadId() {
			return store.open && store.open.thread ? store.open.thread.id : null;
		}

		function rowFor(thread) {
			var row = el('a', 'adn-chat-row');
			row.href = thread.url;
			row.dataset.threadId = thread.id;
			row.classList.toggle('is-active', thread.id === openThreadId());
			row.classList.toggle('is-unread', thread.unread > 0);

			var avatar = el('span', 'adn-chat-avatar');
			avatar.setAttribute('aria-hidden', 'true');
			avatarInto(avatar, thread.other, false);
			row.appendChild(avatar);

			var text = el('span', 'adn-chat-row-text');
			text.appendChild(el('span', 'adn-chat-row-name', thread.other.name));
			if (thread.post) {
				text.appendChild(el('span', 'adn-chat-row-listing', thread.post.title));
			}
			if (thread.last) {
				var snippet = thread.last.type === 'image' ? T.photo : thread.last.text;
				text.appendChild(el('span', 'adn-chat-row-last', (thread.last.mine ? T.you + ': ' : '') + snippet));
			}
			row.appendChild(text);

			var meta = el('span', 'adn-chat-row-meta');
			if (thread.last) {
				meta.appendChild(el('time', '', shortTime(thread.last.at)));
			}
			if (thread.unread > 0) {
				var unread = el('span', 'adn-chat-row-unread', thread.unread > 99 ? '99+' : String(thread.unread));
				unread.setAttribute('aria-label', fill(T.unread_count, {count: thread.unread}));
				meta.appendChild(unread);
			}
			row.appendChild(meta);

			return row;
		}

		function emptyInbox() {
			var box = el('div', 'adn-chat-empty');
			var badge = el('span', 'adn-chat-empty-icon');
			badge.appendChild(icon('chat-dots'));
			box.appendChild(badge);
			if (store.filter === 'unread') {
				box.appendChild(el('strong', '', T.empty_unread));
				return box;
			}
			box.appendChild(el('strong', '', T.empty_title));
			box.appendChild(el('span', '', T.empty_text));
			var browse = el('a', 'btn btn-primary', T.browse);
			browse.href = URLS.browse;
			box.appendChild(browse);
			return box;
		}

		function renderInbox() {
			els.list.textContent = '';
			els.filters.forEach(function (button) {
				var on = button.dataset.chatFilter === store.filter;
				button.classList.toggle('is-on', on);
				button.setAttribute('aria-pressed', on ? 'true' : 'false');
			});

			if (!store.threads.length) {
				els.list.appendChild(emptyInbox());
				return;
			}
			store.threads.forEach(function (thread) {
				els.list.appendChild(rowFor(thread));
			});
			if (store.nextCursor) {
				var more = el('button', 'adn-chat-more', T.load_more);
				more.type = 'button';
				more.dataset.chatMore = '1';
				els.list.appendChild(more);
			}
		}

		function inboxMessage(text, retry) {
			els.list.textContent = '';
			var box = el('div', 'adn-chat-empty');
			box.appendChild(el('strong', '', text));
			var button = el('button', 'btn btn-outline-primary', T.try_again);
			button.type = 'button';
			button.addEventListener('click', retry);
			box.appendChild(button);
			els.list.appendChild(box);
		}

		var requestNumber = 0;

		/**
		 * Loads the inbox: "replace" (a new filter), "append" (the next page) or "refresh" (the first page
		 * again, merged over what is listed, so pages already loaded stay). An answer that arrives after a
		 * newer request was made is ignored.
		 */
		function loadInbox(mode) {
			var number = ++requestNumber;
			var append = mode === 'append';
			var url = URLS.threads + '?filter=' + encodeURIComponent(store.filter) +
				(append && store.nextCursor ? '&cursor=' + encodeURIComponent(store.nextCursor) : '');

			return request('GET', url).then(function (data) {
				if (number !== requestNumber) {
					return;
				}
				if (mode === 'refresh' && store.pagesLoaded > 1) {
					var fresh = data.threads.map(function (thread) {
						return thread.id;
					});
					store.threads = data.threads.concat(store.threads.filter(function (thread) {
						return fresh.indexOf(thread.id) === -1;
					}));
				} else {
					store.threads = append ? store.threads.concat(data.threads) : data.threads;
					store.nextCursor = data.next_cursor;
					store.pagesLoaded = append ? store.pagesLoaded + 1 : 1;
				}
				renderInbox();
			});
		}

		function showSkeleton() {
			els.list.textContent = '';
			for (var i = 0; i < 3; i++) {
				els.list.appendChild(el('div', 'adn-chat-skeleton'));
			}
		}

		/** Puts a conversation at the top of the inbox with its new state. */
		function upsertThread(thread) {
			var rest = store.threads.filter(function (item) {
				return item.id !== thread.id;
			});
			var hiddenByFilter = store.filter === 'unread' && thread.unread === 0 && rest.length === store.threads.length;
			store.threads = hiddenByFilter ? rest : [thread].concat(rest);
			renderInbox();
		}

		function patchThread(threadId, changes) {
			store.threads = store.threads.map(function (thread) {
				return thread.id === threadId ? Object.assign({}, thread, changes) : thread;
			});
			if (store.open && store.open.thread && store.open.thread.id === threadId) {
				store.open.thread = Object.assign({}, store.open.thread, changes);
			}
			renderInbox();
		}

		return {
			render: renderInbox,
			message: inboxMessage,
			load: loadInbox,
			skeleton: showSkeleton,
			upsert: upsertThread,
			patch: patchThread
		};
	};
})(window);
