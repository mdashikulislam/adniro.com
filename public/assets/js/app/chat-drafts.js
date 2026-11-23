/*
 * adniro: what a member has typed and not sent yet (assets/js/app/chat.js), kept per conversation in this
 * browser, so switching chats, reloading or signing in again does not lose it. Nothing is sent to the server.
 * Without browser storage (private windows, blocked site data) the chat works as before, without drafts.
 */
(function (window) {
	'use strict';

	var PREFIX = 'adnChatDraft:';
	var KEEP_DAYS = 30;
	var DAY_MS = 86400000;

	/** @param {{ME: number, LIMITS: Object}} ctx */
	window.AdnChatDrafts = function (ctx) {
		var ownPrefix = PREFIX + ctx.ME + ':';

		function storage() {
			try {
				return window.localStorage;
			} catch (error) {
				return null;
			}
		}

		/** A conversation that exists is known by its id; one not started yet by its listing. */
		function keyOf(open) {
			if (!open) {
				return null;
			}
			if (open.thread) {
				return ownPrefix + 't' + open.thread.id;
			}
			return open.draft && open.draft.post ? ownPrefix + 'p' + open.draft.post.id : null;
		}

		function read(key) {
			try {
				var saved = JSON.parse(storage().getItem(key) || 'null');
				return saved && typeof saved.text === 'string' ? saved : null;
			} catch (error) {
				return null;
			}
		}

		function remove(key) {
			try {
				storage().removeItem(key);
			} catch (error) {
				// no storage: nothing to remove
			}
		}

		function load(open) {
			var key = keyOf(open);
			var saved = key ? read(key) : null;
			return saved ? saved.text.slice(0, ctx.LIMITS.body_max) : '';
		}

		function save(open, text) {
			var key = keyOf(open);
			if (!key) {
				return;
			}
			if (!text.trim()) {
				remove(key);
				return;
			}
			try {
				storage().setItem(key, JSON.stringify({text: text, at: Date.now()}));
			} catch (error) {
				// storage full or blocked: the draft is simply not kept
			}
		}

		function clear(open) {
			var key = keyOf(open);
			if (key) {
				remove(key);
			}
		}

		/**
		 * Drafts nobody came back to are dropped after a month, and so are another member's as soon as someone
		 * else signs in on this browser: on a shared computer nobody's unsent text is left behind.
		 */
		function prune() {
			try {
				var store = storage();
				var oldest = Date.now() - KEEP_DAYS * DAY_MS;
				Object.keys(store).filter(function (key) {
					return key.indexOf(PREFIX) === 0;
				}).forEach(function (key) {
					var saved = key.indexOf(ownPrefix) === 0 ? read(key) : null;
					if (!saved || !(saved.at > oldest)) {
						store.removeItem(key);
					}
				});
			} catch (error) {
				// no storage
			}
		}

		prune();

		return {load: load, save: save, clear: clear};
	};
})(window);
