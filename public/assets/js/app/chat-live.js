/*
 * adniro: the live side of the chat, on every page for signed-in members (front/layouts/partials/chat-live.blade.php).
 *
 * - Keeps the unread count up to date: on every badge ([data-adn-chat-badge]: the header's chat icon, the
 *   account menu, the phone app bar) and in front of the page title, so it shows on the browser tab.
 * - Shows a small pop-up when a message arrives while the member is on another page (the sound and the browser
 *   notification are in chat-alerts.js).
 * - When a live server is configured (Laravel Reverb), loads the two socket libraries after the page has
 *   finished, opens one connection, listens on the member's own private channel and passes what arrives on as
 *   window events for the chat page: adn:chat:message, adn:chat:read, adn:chat:unsent, adn:chat:state.
 * - A tab hidden for five minutes closes its connection and reconnects when it is shown again, unless the
 *   member turned browser notifications on: they asked to hear from a tab left in the background.
 * Without a live server nothing is loaded and nothing is polled here: the chat page checks by itself.
 */
(function (window, document) {
	'use strict';

	var configEl = document.getElementById('adnChatLive');
	if (!configEl) {
		return;
	}

	var config = JSON.parse(configEl.textContent);
	var IDLE_DISCONNECT_MS = 5 * 60 * 1000;
	var IDLE_START_TIMEOUT_MS = 3000;
	var BADGE_MAX = 99;
	var TOAST_MS = 7000;
	var TOAST_MAX = 3;

	var pageTitle = document.title.replace(/^\(\d+\+?\)\s+/, '');

	/** The unread total: on every badge, and in front of the page's title so it shows on the browser tab. */
	function setUnread(total) {
		var count = Math.max(0, Number(total) || 0);
		var label = count > BADGE_MAX ? BADGE_MAX + '+' : String(count);
		document.querySelectorAll('[data-adn-chat-badge]').forEach(function (badge) {
			badge.textContent = label;
			badge.hidden = count === 0;
		});
		document.title = count > 0 ? '(' + label + ') ' + pageTitle : pageTitle;
	}

	/** One line for a message: its text, or what it is (a photo, an offer, the answer to an offer). */
	function preview(message) {
		var text = config.text;
		if (message.type === 'image') {
			return text.photo;
		}
		if (message.type === 'offer') {
			return text.offer.replace(':amount', message.offer.amount);
		}
		if (message.type === 'offer_answer') {
			return (message.offer.status === 'accepted' ? text.offer_accepted : text.offer_declined).replace(':amount', message.offer.amount);
		}
		return message.body;
	}

	/* ---------- A pop-up when a message arrives on another page ---------- */
	function toastHolder() {
		var holder = document.getElementById('adnChatToasts');
		if (!holder) {
			holder = document.createElement('div');
			holder.id = 'adnChatToasts';
			holder.className = 'adn-chat-toasts';
			holder.setAttribute('role', 'status');
			holder.setAttribute('aria-live', 'polite');
			document.body.appendChild(holder);
		}
		return holder;
	}

	function avatarFor(person) {
		var avatar = document.createElement('span');
		avatar.className = 'adn-chat-toast-avatar';
		avatar.setAttribute('aria-hidden', 'true');
		if (person.photo) {
			var image = document.createElement('img');
			image.src = person.photo;
			image.alt = '';
			avatar.appendChild(image);
		} else {
			avatar.textContent = (person.name || '?').trim().charAt(0).toUpperCase();
		}
		return avatar;
	}

	/** "Sam: Is this still available?", linking to the conversation. Text goes in as text, never as HTML. */
	function showToast(detail) {
		// Not for my own messages, and not on the chat page: its inbox already shows the new message
		if (detail.message.user_id === config.user_id || document.getElementById('adnChat')) {
			return;
		}

		var holder = toastHolder();
		var toast = document.createElement('div');
		toast.className = 'adn-chat-toast';

		var link = document.createElement('a');
		link.className = 'adn-chat-toast-link';
		link.href = detail.thread.url;
		link.appendChild(avatarFor(detail.thread.other));
		var text = document.createElement('span');
		text.className = 'adn-chat-toast-text';
		var name = document.createElement('strong');
		name.textContent = detail.thread.other.name;
		var body = document.createElement('span');
		body.textContent = preview(detail.message);
		text.appendChild(name);
		text.appendChild(body);
		link.appendChild(text);
		toast.appendChild(link);

		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'adn-chat-toast-close';
		close.setAttribute('aria-label', config.text.dismiss);
		var closeIcon = document.createElement('i');
		closeIcon.className = 'bi bi-x-lg';
		closeIcon.setAttribute('aria-hidden', 'true');
		close.appendChild(closeIcon);
		toast.appendChild(close);

		var timer = null;
		var remove = function () {
			window.clearTimeout(timer);
			toast.remove();
		};
		var startTimer = function () {
			window.clearTimeout(timer);
			timer = window.setTimeout(remove, TOAST_MS);
		};
		close.addEventListener('click', remove);
		toast.addEventListener('mouseenter', function () {
			window.clearTimeout(timer);
		});
		toast.addEventListener('mouseleave', startTimer);

		holder.appendChild(toast);
		while (holder.children.length > TOAST_MAX) {
			holder.firstElementChild.remove();
		}
		// It waits for the tab to be looked at before it starts counting down
		if (document.hidden) {
			document.addEventListener('visibilitychange', startTimer, {once: true});
		} else {
			startTimer();
		}
	}

	function emit(name, detail) {
		window.dispatchEvent(new CustomEvent('adn:chat:' + name, {detail: detail}));
	}

	var api = {connected: false, setUnread: setUnread, preview: preview, join: null};
	window.AdnChatLive = api;
	setUnread(config.unread_total);

	if (!config.live) {
		return;
	}

	function loadScript(src) {
		return new Promise(function (resolve, reject) {
			var script = document.createElement('script');
			script.src = src;
			script.async = true;
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild(script);
		});
	}

	function refreshUnread() {
		fetch(config.unread_url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
			.then(function (response) {
				return response.ok ? response.json() : null;
			})
			.then(function (data) {
				if (data) {
					setUnread(data.unread_total);
				}
			})
			.catch(function () {
				// the next message event corrects the badge
			});
	}

	function connect() {
		var settings = config.live;
		var secure = settings.scheme === 'https';
		var csrf = document.querySelector('meta[name="csrf-token"]');

		var echo = new window.Echo.default({
			broadcaster: 'reverb',
			key: settings.key,
			wsHost: settings.host,
			wsPort: settings.port,
			wssPort: settings.port,
			forceTLS: secure,
			enabledTransports: ['ws', 'wss'],
			authEndpoint: settings.auth_url,
			auth: {headers: {'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''}}
		});
		var pusher = echo.connector.pusher;

		pusher.connection.bind('state_change', function (states) {
			api.connected = states.current === 'connected';
			emit('state', {connected: api.connected});
		});

		echo.private('chat.user.' + config.user_id)
			.listen('.chat.message', function (detail) {
				setUnread(detail.unread_total);
				showToast(detail);
				emit('message', detail);
			})
			.listen('.chat.read', function (detail) {
				setUnread(detail.unread_total);
				emit('read', detail);
			})
			.listen('.chat.unsent', function (detail) {
				setUnread(detail.unread_total); // a message taken back before it was read no longer counts
				emit('unsent', detail);
			});

		/**
		 * Joins a conversation's presence channel: who is online in it, and typing (sent browser to browser).
		 * @returns {{leave: function(): void, typing: function(): void}}
		 */
		api.join = function (threadId, handlers) {
			var name = 'chat.thread.' + threadId;
			var channel = echo.join(name)
				.here(function (members) {
					handlers.here(members.map(function (member) {
						return member.id;
					}));
				})
				.joining(function (member) {
					handlers.joining(member.id);
				})
				.leaving(function (member) {
					handlers.leaving(member.id);
				})
				.listenForWhisper('typing', function (data) {
					handlers.typing(data.id);
				});

			return {
				leave: function () {
					echo.leave(name);
				},
				typing: function () {
					if (api.connected) {
						channel.whisper('typing', {id: config.user_id});
					}
				}
			};
		};

		// A forgotten tab costs nothing: it disconnects after a while and comes back when shown
		var idleTimer = null;
		document.addEventListener('visibilitychange', function () {
			window.clearTimeout(idleTimer);
			if (document.hidden) {
				if (window.AdnChatAlerts && window.AdnChatAlerts.staysConnected()) {
					return;
				}
				idleTimer = window.setTimeout(function () {
					pusher.disconnect();
				}, IDLE_DISCONNECT_MS);
				return;
			}
			if (pusher.connection.state === 'disconnected') {
				pusher.connect();
				refreshUnread();
			}
		});
	}

	function start() {
		loadScript(config.live.pusher_src)
			.then(function () {
				return loadScript(config.live.echo_src);
			})
			.then(connect)
			.catch(function () {
				emit('state', {connected: false});
			});
	}

	// The chat page connects at once; other pages wait until the page is loaded and the browser is idle
	if (document.getElementById('adnChat')) {
		start();
		return;
	}
	window.addEventListener('load', function () {
		if (typeof window.requestIdleCallback === 'function') {
			window.requestIdleCallback(start, {timeout: IDLE_START_TIMEOUT_MS});
		} else {
			window.setTimeout(start, IDLE_START_TIMEOUT_MS);
		}
	});
})(window, document);
