/*
 * adniro: who is online and who is typing in the open conversation (assets/js/app/chat.js). Both come from the
 * conversation's presence channel (chat-live.js); without a live connection neither is shown.
 * A member who turned "online" off in their privacy choices does not join it: they are not shown, and see nobody.
 */
(function (window) {
	'use strict';

	var TYPING_SEND_MS = 2500;
	var TYPING_SHOW_MS = 4000;

	/**
	 * @param {{T: Object, ME: number, els: Object, store: Object, kit: Object}} ctx
	 * @param {{isNearBottom: Function, scrollToBottom: Function}} log
	 */
	window.AdnChatPresence = function (ctx, log) {
		var T = ctx.T;
		var ME = ctx.ME;
		var els = ctx.els;
		var store = ctx.store;

		function setStatus(kind) {
			var online = !!store.open && store.open.online;
			var state = kind === 'typing' ? 'typing' : (online ? 'online' : '');
			els.status.textContent = state ? T[state] : '';
			els.status.classList.toggle('is-online', state === 'online');
			els.status.classList.toggle('is-typing', state === 'typing');
			els.avatar.classList.toggle('is-online', online);
		}

		function hideTyping() {
			window.clearTimeout(store.typingTimer);
			els.typing.hidden = true;
			if (store.open) {
				setStatus('');
			}
		}

		function showTyping() {
			var stick = log.isNearBottom();
			els.typing.hidden = false;
			setStatus('typing');
			if (stick) {
				log.scrollToBottom();
			}
			window.clearTimeout(store.typingTimer);
			store.typingTimer = window.setTimeout(hideTyping, TYPING_SHOW_MS);
		}

		function leave() {
			if (store.presence) {
				store.presence.leave();
			}
			store.presence = null;
		}

		function join() {
			leave();
			var open = store.open;
			var connection = ctx.kit.live();
			if (!open || !open.thread || !open.thread.can_send || !connection || !connection.join || !ctx.PRIVACY.online) {
				return;
			}

			store.presence = connection.join(open.thread.id, {
				here: function (ids) {
					open.online = ids.some(function (id) {
						return id !== ME;
					});
					setStatus('');
				},
				joining: function (id) {
					if (id !== ME) {
						open.online = true;
						setStatus('');
					}
				},
				leaving: function (id) {
					if (id !== ME) {
						open.online = false;
						hideTyping();
					}
				},
				typing: function (id) {
					if (id !== ME && store.open === open) {
						showTyping();
					}
				}
			});
		}

		/** Tells the other person "typing", at most once every few seconds. */
		function whisper() {
			var now = Date.now();
			if (store.presence && now - store.typingSentAt > TYPING_SEND_MS) {
				store.typingSentAt = now;
				store.presence.typing();
			}
		}

		return {setStatus: setStatus, hideTyping: hideTyping, join: join, leave: leave, whisper: whisper};
	};
})(window);
