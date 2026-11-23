/*
 * adniro: alerts for a new chat message, on every page for signed-in members (loaded after chat-live.js, which
 * passes each new message on as the window event adn:chat:message).
 *
 * - A short sound, on unless the member turned it off.
 * - A browser notification when the site is in another tab or behind another window, only once the member
 *   has turned it on in the chat settings and allowed it in the browser.
 * Neither happens for a message in the conversation the member is looking at, nor for their own messages.
 * With several tabs open, one of them alerts: the tabs agree through localStorage.
 *
 * The two choices are kept in this browser (localStorage): a sound or a notification belongs to a device, not
 * to an account. The sound is made here (two notes), so there is no audio file to load.
 */
(function (window, document) {
	'use strict';

	var configEl = document.getElementById('adnChatLive');
	if (!configEl) {
		return;
	}

	var ME = JSON.parse(configEl.textContent).user_id;
	var PREFS_KEY = 'adnChatAlerts';
	var CLAIM_KEY = 'adnChatAlerted';
	// The tab on screen answers first; hidden tabs wait a little, each a different time
	var VISIBLE_WAIT_MS = 30;
	var HIDDEN_WAIT_MS = {min: 80, spread: 200};
	var SOUND_START_WITHIN_MS = 1500;
	var NOTES = [{hz: 880, at: 0}, {hz: 1174.66, at: 0.13}];
	var NOTE_SECONDS = 0.22;
	var NOTE_ATTACK_SECONDS = 0.02;
	var VOLUME = 0.12;
	var SILENT = 0.0001;

	var remembered = null; // the choices, when the browser will not keep them (a private window)
	var audio = null;

	function readPrefs() {
		var saved = remembered;
		try {
			saved = JSON.parse(window.localStorage.getItem(PREFS_KEY)) || remembered;
		} catch (error) {
			saved = remembered;
		}
		saved = saved || {};
		return {sound: saved.sound !== false, desktop: saved.desktop === true};
	}

	function savePrefs(change) {
		remembered = Object.assign(readPrefs(), change);
		try {
			window.localStorage.setItem(PREFS_KEY, JSON.stringify(remembered));
		} catch (error) {
			// kept for this page only
		}
	}

	/** Android browsers have the Notification object but only let an installed app use it: not offered there. */
	function canNotify() {
		return typeof window.Notification === 'function' && !/Android/i.test(window.navigator.userAgent);
	}

	/** "unsupported", "blocked" (refused in the browser), "on" or "off". */
	function desktopState() {
		if (!canNotify()) {
			return 'unsupported';
		}
		if (window.Notification.permission === 'denied') {
			return 'blocked';
		}
		return readPrefs().desktop && window.Notification.permission === 'granted' ? 'on' : 'off';
	}

	/* ---------- Sound ---------- */
	function audioContext() {
		var AudioContext = window.AudioContext || window.webkitAudioContext;
		if (!audio && AudioContext) {
			try {
				audio = new AudioContext();
			} catch (error) {
				audio = null;
			}
		}
		return audio;
	}

	function playNotes(context) {
		NOTES.forEach(function (note) {
			var start = context.currentTime + note.at;
			var oscillator = context.createOscillator();
			var gain = context.createGain();
			oscillator.type = 'sine';
			oscillator.frequency.value = note.hz;
			gain.gain.setValueAtTime(SILENT, start);
			gain.gain.exponentialRampToValueAtTime(VOLUME, start + NOTE_ATTACK_SECONDS);
			gain.gain.exponentialRampToValueAtTime(SILENT, start + NOTE_SECONDS);
			oscillator.connect(gain);
			gain.connect(context.destination);
			oscillator.start(start);
			oscillator.stop(start + NOTE_SECONDS + NOTE_ATTACK_SECONDS);
		});
	}

	/**
	 * Browsers keep a page silent until the member has clicked or typed on it. If the sound cannot start
	 * right away it is dropped: a sound that plays a minute late would only confuse.
	 */
	function ding() {
		var context = audioContext();
		if (!context) {
			return;
		}
		if (context.state === 'running') {
			playNotes(context);
			return;
		}
		var askedAt = Date.now();
		context.resume().then(function () {
			if (context.state === 'running' && Date.now() - askedAt < SOUND_START_WITHIN_MS) {
				playNotes(context);
			}
		}).catch(function () {
			// stays silent
		});
	}

	/* ---------- Browser notification ---------- */
	function openThreadId() {
		var chat = document.getElementById('adnChat');
		return chat ? Number(chat.dataset.openThread) || 0 : 0;
	}

	function isOnScreen() {
		return !document.hidden && document.hasFocus();
	}

	function notify(detail) {
		var preview = window.AdnChatLive && window.AdnChatLive.preview ? window.AdnChatLive.preview(detail.message) : '';
		try {
			var note = new window.Notification(detail.thread.other.name, {
				body: preview,
				tag: 'adn-chat-' + detail.thread.id,
				icon: detail.thread.other.photo || undefined
			});
			note.onclick = function () {
				window.focus();
				note.close();
				if (openThreadId() !== detail.thread.id) {
					window.location.href = detail.thread.url;
				}
			};
		} catch (error) {
			// some phone browsers only allow notifications from an installed app
		}
	}

	/* ---------- One alert, whatever the number of open tabs ---------- */
	/** True for the first tab that asks about this message, or about a newer one. */
	function claim(messageId) {
		try {
			if (Number(window.localStorage.getItem(CLAIM_KEY)) >= messageId) {
				return false;
			}
			window.localStorage.setItem(CLAIM_KEY, String(messageId));
		} catch (error) {
			// no shared storage: this tab alerts by itself
		}
		return true;
	}

	function onMessage(detail) {
		var message = detail.message;
		if (!message || message.user_id === ME) {
			return;
		}
		// Being read right now: no alert here, and none from the other tabs
		if (isOnScreen() && openThreadId() === detail.thread.id) {
			claim(message.id);
			return;
		}
		var prefs = readPrefs();
		var wantsNotification = desktopState() === 'on';
		if (!prefs.sound && !wantsNotification) {
			return;
		}

		var wait = document.hidden ? HIDDEN_WAIT_MS.min + Math.random() * HIDDEN_WAIT_MS.spread : Math.random() * VISIBLE_WAIT_MS;
		window.setTimeout(function () {
			if (!claim(message.id)) {
				return;
			}
			if (prefs.sound) {
				ding();
			}
			if (wantsNotification && !isOnScreen()) {
				notify(detail);
			}
		}, wait);
	}

	window.AdnChatAlerts = {
		/** @returns {{sound: boolean, desktop: string}} desktop: "unsupported", "blocked", "on" or "off" */
		prefs: function () {
			return {sound: readPrefs().sound, desktop: desktopState()};
		},
		setSound: function (isOn) {
			savePrefs({sound: !!isOn});
			if (isOn) {
				ding(); // what it sounds like
			}
		},
		/** Asks the browser for permission the first time. @returns {Promise<string>} the new state */
		setDesktop: function (isOn) {
			if (!isOn || !canNotify()) {
				savePrefs({desktop: false});
				return Promise.resolve(desktopState());
			}
			return new Promise(function (resolve) {
				var isAnswered = false;
				var answered = function () {
					if (isAnswered) {
						return;
					}
					isAnswered = true;
					savePrefs({desktop: window.Notification.permission === 'granted'});
					resolve(desktopState());
				};
				if (window.Notification.permission !== 'default') {
					answered();
					return;
				}
				// Older browsers answer through the callback, newer ones through the promise
				var asking = window.Notification.requestPermission(answered);
				if (asking && typeof asking.then === 'function') {
					asking.then(answered, answered);
				}
			});
		},
		/** A member who asked for notifications keeps hearing from a tab left in the background. */
		staysConnected: function () {
			return desktopState() === 'on';
		}
	};

	window.addEventListener('adn:chat:message', function (event) {
		onMessage(event.detail);
	});
	// The first click or key press on the page lets the sound play later
	['pointerdown', 'keydown'].forEach(function (name) {
		document.addEventListener(name, function () {
			var context = readPrefs().sound ? audioContext() : null;
			if (context && context.state === 'suspended') {
				context.resume().catch(function () {});
			}
		}, {once: true, passive: true});
	});
})(window, document);
