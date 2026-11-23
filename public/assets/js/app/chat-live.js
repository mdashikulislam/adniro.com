/*
 * adniro: the live side of the chat, on every page for signed-in members (front/layouts/partials/chat-live.blade.php).
 *
 * - Keeps the unread badge ([data-adn-chat-badge]) in the header menu and the phone app bar up to date.
 * - When a live server is configured (Laravel Reverb), loads the two socket libraries after the page has
 *   finished, opens one connection, listens on the member's own private channel and passes what arrives on as
 *   window events for the chat page: adn:chat:message, adn:chat:read, adn:chat:state.
 * - A tab hidden for five minutes closes its connection; it reconnects when it is shown again.
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

	function setUnread(total) {
		var count = Math.max(0, Number(total) || 0);
		document.querySelectorAll('[data-adn-chat-badge]').forEach(function (badge) {
			badge.textContent = count > BADGE_MAX ? BADGE_MAX + '+' : String(count);
			badge.hidden = count === 0;
		});
	}

	function emit(name, detail) {
		window.dispatchEvent(new CustomEvent('adn:chat:' + name, {detail: detail}));
	}

	var api = {connected: false, setUnread: setUnread, join: null};
	window.AdnChatLive = api;

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
				emit('message', detail);
			})
			.listen('.chat.read', function (detail) {
				setUnread(detail.unread_total);
				emit('read', detail);
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
