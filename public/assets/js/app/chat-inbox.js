/*
 * adniro: the live chat inbox (assets/js/app/chat.js): the list of conversations, its filters and its search,
 * each row's own menu (chat-tidy.js acts on it), and keeping a row in step with new messages and read receipts.
 *
 * The list is drawn again at every change. So that this is not felt: the keyboard focus goes back to the row
 * it was on, and the list waits while one of its menus is open.
 */
(function (window, document) {
	'use strict';

	var SEARCH_WAIT_MS = 300;
	var BADGE_MAX = 99;
	var FILTER_UNREAD = 'unread';
	var FILTER_ARCHIVED = 'archived';

	/** @param {{T: Object, URLS: Object, LIMITS: Object, root: Element, els: Object, store: Object, kit: Object}} ctx */
	window.AdnChatInbox = function (ctx) {
		var T = ctx.T;
		var URLS = ctx.URLS;
		var els = ctx.els;
		var store = ctx.store;
		var el = ctx.kit.el;
		var ownDir = ctx.kit.ownDir;
		var icon = ctx.kit.icon;
		var fill = ctx.kit.fill;
		var request = ctx.kit.request;
		var shortTime = ctx.kit.shortTime;
		var avatarInto = ctx.kit.avatarInto;
		var search = ctx.root.querySelector('[data-chat-search]');
		var searchClear = ctx.root.querySelector('[data-chat-search-clear]');
		var searchTimer = null;
		var isRenderWaiting = false;

		function openThreadId() {
			return store.open && store.open.thread ? store.open.thread.id : null;
		}

		/**
		 * What the member can do with a conversation in their own list, as it is now: each choice says what it
		 * would set. Used for a row's menu and for the open conversation's menu (chat-tidy.js).
		 */
		function tidyChoices(thread) {
			return [
				{name: 'unread', value: thread.unread === 0, icon: thread.unread === 0 ? 'envelope' : 'envelope-open', label: thread.unread === 0 ? T.mark_unread : T.mark_read},
				{name: 'muted', value: !thread.muted, icon: thread.muted ? 'bell' : 'bell-slash', label: thread.muted ? T.unmute : T.mute},
				{name: 'archived', value: !thread.archived, icon: thread.archived ? 'inbox' : 'archive', label: thread.archived ? T.unarchive : T.archive}
			];
		}

		function menuFor(thread) {
			var menu = el('ul', 'dropdown-menu dropdown-menu-end adn-chat-menu');
			tidyChoices(thread).forEach(function (choice) {
				var button = el('button', 'dropdown-item');
				button.type = 'button';
				button.dataset.chatTidy = choice.name;
				button.dataset.value = choice.value ? '1' : '0';
				button.appendChild(icon(choice.icon));
				button.appendChild(el('span', '', ' ' + choice.label));
				menu.appendChild(el('li')).appendChild(button);
			});
			return menu;
		}

		function rowFor(thread) {
			var row = el('a', 'adn-chat-row');
			row.href = thread.url;
			row.dataset.threadId = thread.id;
			row.classList.toggle('is-active', thread.id === openThreadId());
			row.classList.toggle('is-unread', thread.unread > 0);
			if (thread.id === openThreadId()) {
				row.setAttribute('aria-current', 'true');
			}

			var avatar = el('span', 'adn-chat-avatar');
			avatar.setAttribute('aria-hidden', 'true');
			avatarInto(avatar, thread.other, false);
			row.appendChild(avatar);

			var text = el('span', 'adn-chat-row-text');
			// The name, cut short if it must, and the "verified" mark after it, never cut
			var title = el('span', 'adn-chat-row-title');
			title.appendChild(ownDir(el('span', 'adn-chat-row-name', thread.other.name)));
			if (thread.other.verified) {
				title.appendChild(ctx.kit.verifiedBadge());
			}
			text.appendChild(title);
			if (thread.post) {
				text.appendChild(ownDir(el('span', 'adn-chat-row-listing', thread.post.title)));
			}
			if (thread.last) {
				// "You: " is the page's own word; what follows is the member's, with its own direction
				var last = el('span', 'adn-chat-row-last');
				if (thread.last.mine) {
					last.appendChild(el('span', 'adn-chat-row-you', T.you + ': '));
				}
				last.appendChild(ownDir(el('span', 'adn-chat-row-snippet', ctx.kit.lineText(thread.last.type, thread.last.text))));
				text.appendChild(last);
			}
			row.appendChild(text);

			var meta = el('span', 'adn-chat-row-meta');
			if (thread.last) {
				meta.appendChild(el('time', '', shortTime(thread.last.at)));
			}
			var marks = el('span', 'adn-chat-row-marks');
			if (thread.archived && store.search) {
				marks.appendChild(el('span', 'adn-chat-row-tag', T.archived_tag)); // found by a search, outside the list
			}
			if (thread.muted) {
				var muted = icon('bell-slash', 'adn-chat-row-muted');
				muted.removeAttribute('aria-hidden');
				muted.setAttribute('role', 'img');
				muted.setAttribute('aria-label', T.muted);
				marks.appendChild(muted);
			}
			if (thread.unread > 0) {
				var unread = el('span', 'adn-chat-row-unread', thread.unread > BADGE_MAX ? BADGE_MAX + '+' : String(thread.unread));
				unread.setAttribute('aria-label', fill(T.unread_count, {count: thread.unread}));
				marks.appendChild(unread);
			}
			meta.appendChild(marks);
			row.appendChild(meta);

			// The row and, beside it, its own menu: a button cannot sit inside a link
			var wrap = el('div', 'adn-chat-row-wrap dropdown');
			wrap.dataset.threadId = thread.id;
			wrap.appendChild(row);

			var more = el('button', 'adn-chat-row-more');
			more.type = 'button';
			more.dataset.bsToggle = 'dropdown';
			more.dataset.bsPopperConfig = '{"strategy":"fixed"}'; // the list scrolls: the menu must not be cut by it
			more.setAttribute('aria-expanded', 'false');
			more.setAttribute('aria-label', fill(T.row_actions, {name: thread.other.name}));
			more.appendChild(icon('three-dots'));
			wrap.appendChild(more);
			wrap.appendChild(menuFor(thread));

			return wrap;
		}

		function emptyInbox() {
			var box = el('div', 'adn-chat-empty');
			var badge = el('span', 'adn-chat-empty-icon');
			badge.appendChild(icon(store.search ? 'search' : (store.filter === FILTER_ARCHIVED ? 'archive' : 'chat-dots')));
			box.appendChild(badge);
			if (store.search) {
				box.appendChild(el('strong', '', T.empty_search));
				box.appendChild(el('span', '', T.empty_search_text));
				return box;
			}
			if (store.filter === FILTER_UNREAD || store.filter === FILTER_ARCHIVED) {
				box.appendChild(el('strong', '', store.filter === FILTER_UNREAD ? T.empty_unread : T.empty_archived));
				return box;
			}
			box.appendChild(el('strong', '', T.empty_title));
			box.appendChild(el('span', '', T.empty_text));
			var browse = el('a', 'btn btn-primary', T.browse);
			browse.href = URLS.browse;
			box.appendChild(browse);
			return box;
		}

		/** Where the keyboard is in the list, so it can be put back after the list is drawn again. */
		function focusPlace() {
			var focused = document.activeElement;
			var wrap = focused && els.list.contains(focused) ? focused.closest('.adn-chat-row-wrap') : null;
			return wrap ? {threadId: wrap.dataset.threadId, onMenu: focused.classList.contains('adn-chat-row-more')} : null;
		}

		function focusRow(threadId, onMenu) {
			var wrap = els.list.querySelector('.adn-chat-row-wrap[data-thread-id="' + threadId + '"]');
			var target = wrap ? wrap.querySelector(onMenu ? '.adn-chat-row-more' : '.adn-chat-row') : null;
			if (target) {
				target.focus({preventScroll: true});
			}
			return !!target;
		}

		function renderInbox() {
			// A row's menu is open: drawing the list now would close it under the member's hand
			if (els.list.querySelector('.dropdown-menu.show')) {
				isRenderWaiting = true;
				return;
			}
			isRenderWaiting = false;
			var place = focusPlace();

			els.list.textContent = '';
			els.filters.forEach(function (button) {
				// A search looks in every chat: no filter is on while it runs
				var on = !store.search && button.dataset.chatFilter === store.filter;
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
			if (place) {
				focusRow(place.threadId, place.onMenu);
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
		 * Loads the inbox: "replace" (a new filter or search), "append" (the next page) or "refresh" (the first
		 * page again, merged over what is listed, so pages already loaded stay). An answer that arrives after a
		 * newer request was made is ignored.
		 */
		function loadInbox(mode) {
			var number = ++requestNumber;
			var append = mode === 'append';
			var url = URLS.threads + '?filter=' + encodeURIComponent(store.search ? '' : store.filter) +
				(store.search ? '&q=' + encodeURIComponent(store.search) : '') +
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

		function isListed(threadId) {
			return store.threads.some(function (thread) {
				return thread.id === threadId;
			});
		}

		/** Whether a conversation has its place in the list being shown. A search result only keeps what it found. */
		function belongs(thread, wasListed) {
			if (store.search) {
				return wasListed;
			}
			if (store.filter === FILTER_UNREAD) {
				return thread.unread > 0 || wasListed; // not pulled away while it is being read
			}
			return store.filter === FILTER_ARCHIVED ? thread.archived : !thread.archived;
		}

		/** Puts a conversation at the top of the inbox with its new state. */
		function upsertThread(thread) {
			var wasListed = isListed(thread.id);
			var rest = store.threads.filter(function (item) {
				return item.id !== thread.id;
			});
			store.threads = belongs(thread, wasListed) ? [thread].concat(rest) : rest;
			renderInbox();
		}

		/** Changes a conversation where it is; it leaves the list when it no longer belongs there (archived). */
		function patchThread(threadId, changes) {
			store.threads = store.threads.map(function (thread) {
				return thread.id === threadId ? Object.assign({}, thread, changes) : thread;
			}).filter(function (thread) {
				return thread.id !== threadId || belongs(thread, true);
			});
			if (store.open && store.open.thread && store.open.thread.id === threadId) {
				store.open.thread = Object.assign({}, store.open.thread, changes);
			}
			renderInbox();
		}

		/** The member deleted the conversation from their list. */
		function removeThread(threadId) {
			store.threads = store.threads.filter(function (thread) {
				return thread.id !== threadId;
			});
			renderInbox();
		}

		/* ---------- Filters and search ---------- */
		/**
		 * Another filter or search: what was listed is forgotten first, so an answer that belongs to the old
		 * list (a refresh already on its way) is never merged into the new one.
		 */
		function reload(retry) {
			store.threads = [];
			store.nextCursor = null;
			store.pagesLoaded = 1;
			showSkeleton();
			return loadInbox('replace').catch(function () {
				inboxMessage(T.inbox_error, retry);
			});
		}

		function runSearch() {
			var words = search.value.trim();
			var wanted = words.length >= ctx.LIMITS.search_min ? words : '';
			if (wanted === store.search) {
				return;
			}
			store.search = wanted;
			reload(runSearch).then(function () {
				if (store.search === wanted && wanted) {
					ctx.kit.announce(store.threads.length ? fill(T.search_found, {count: store.threads.length}) : T.empty_search);
				}
			});
		}

		function clearSearch() {
			window.clearTimeout(searchTimer);
			search.value = '';
			searchClear.hidden = true;
			store.search = '';
		}

		els.filters.forEach(function (button) {
			button.addEventListener('click', function () {
				clearSearch();
				store.filter = button.dataset.chatFilter;
				reload(function () {
					button.click();
				});
			});
		});

		search.addEventListener('input', function () {
			searchClear.hidden = !search.value;
			window.clearTimeout(searchTimer);
			searchTimer = window.setTimeout(runSearch, SEARCH_WAIT_MS);
		});
		search.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && search.value) {
				event.preventDefault();
				search.value = '';
				searchClear.hidden = true;
				runSearch();
			}
		});
		searchClear.addEventListener('click', function () {
			search.value = '';
			searchClear.hidden = true;
			runSearch();
			search.focus();
		});

		/* ---------- The list: more pages, its menus, the arrow keys ---------- */
		els.list.addEventListener('click', function (event) {
			var more = event.target.closest('[data-chat-more]');
			if (!more) {
				return;
			}
			more.disabled = true;
			loadInbox('append').catch(function (error) {
				more.disabled = false;
				ctx.kit.toast(error.message, 'error');
			});
		});
		els.list.addEventListener('hidden.bs.dropdown', function () {
			if (isRenderWaiting) {
				renderInbox();
			}
		});
		els.list.addEventListener('keydown', function (event) {
			var row = event.target.closest ? event.target.closest('.adn-chat-row') : null;
			var step = {ArrowDown: 1, ArrowUp: -1}[event.key];
			if (!row || !step) {
				return;
			}
			var rows = Array.prototype.slice.call(els.list.querySelectorAll('.adn-chat-row'));
			var next = rows[rows.indexOf(row) + step];
			if (next) {
				event.preventDefault();
				next.focus();
			}
		});

		return {
			remove: removeThread,
			render: renderInbox,
			message: inboxMessage,
			load: loadInbox,
			skeleton: showSkeleton,
			upsert: upsertThread,
			patch: patchThread,
			focus: focusRow,
			tidyChoices: tidyChoices
		};
	};
})(window, document);
