/*
 * adniro: "Get the app" on phones (front/layouts/partials/app-install.blade.php, assets/css/app-install.css).
 * Loaded on phones and in the installed app only.
 *
 * - Registers the service worker (public/sw.js: the offline page only, no page caching).
 * - Android Chrome & co: the browser's own install prompt, from the card or the menu / footer entries.
 * - iPhone (Safari, Chrome) and Firefox on Android: the step-by-step "Add to Home Screen" guide.
 * - Samsung Internet: "Open in Chrome" (its own app packages are blocked by Google Play Protect on recent Android).
 * - Nothing inside the installed app, nor in Facebook / Instagram / TikTok in-app browsers (they cannot install).
 *
 * The card is never forced: once per visit at most, snoozed for a week when dismissed, never on the listing form,
 * sign-in, account or payment pages, never over the cookie banner or another dialog. The menu and footer
 * entries stay available.
 */
(function (window, document) {
	'use strict';

	var card = document.getElementById('adnInstall');
	var sheet = document.getElementById('adnInstallSheet');
	if (!card || !sheet) {
		return;
	}

	var STORE_KEY = 'adn_app_install';
	var SESSION_KEY = 'adn_app_install_shown';
	var SNOOZE_MS = 7 * 24 * 60 * 60 * 1000;
	var SHOW_DELAY_MS = 1200;
	var BUSY_RETRY_MS = 6000;
	var BLOCKED_PATHS = /^\/(admin|account|auth|posts\/create|posts\/\d+\/(details|photos|payment|finish)|create|edit|payment|pricing)/i;

	var root = document.documentElement;
	var installed = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
	if (installed) {
		root.classList.add('is-standalone');
	}

	/* ---------- Service worker ---------- */
	var swUrl = card.getAttribute('data-sw');
	if (swUrl && 'serviceWorker' in navigator) {
		window.addEventListener('load', function () {
			navigator.serviceWorker.register(swUrl, {scope: '/'}).catch(function () {
				// the site works the same without it
			});
		});
	}

	if (installed) {
		return;
	}

	var ua = navigator.userAgent || '';
	var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
	var isIOSOtherBrowser = isIOS && /CriOS|FxiOS|EdgiOS|OPiOS/i.test(ua);
	var isSamsung = /SamsungBrowser/i.test(ua);
	var isAndroidFirefox = /Android/i.test(ua) && /Firefox/i.test(ua);
	var isInApp = /FBAN|FBAV|Instagram|Line\/|MicroMessenger|Snapchat|TikTok|musical_ly|Twitter/i.test(ua);
	if (isInApp) {
		return;
	}

	var deferredPrompt = null;
	var lastFocus = null;

	/* ---------- Remembered choices (the page works the same without storage) ---------- */
	function readState() {
		try {
			return JSON.parse(window.localStorage.getItem(STORE_KEY)) || {};
		} catch (e) {
			return {};
		}
	}

	function writeState(state) {
		try {
			window.localStorage.setItem(STORE_KEY, JSON.stringify(state));
		} catch (e) {
			// storage unavailable
		}
	}

	function shownThisVisit() {
		try {
			return window.sessionStorage.getItem(SESSION_KEY) === '1';
		} catch (e) {
			return false;
		}
	}

	function markShown() {
		try {
			window.sessionStorage.setItem(SESSION_KEY, '1');
		} catch (e) {
			// storage unavailable
		}
	}

	function snoozed() {
		var dismissedAt = readState().dismissedAt || 0;
		return (Date.now() - dismissedAt) < SNOOZE_MS;
	}

	/* ---------- What this phone gets ---------- */
	function actionLabel() {
		if (isSamsung) {
			return card.getAttribute('data-label-chrome');
		}
		return (isIOS || isAndroidFirefox) ? card.getAttribute('data-label-home') : card.getAttribute('data-label-install');
	}

	function preferredTab() {
		if (isIOS) {
			return isIOSOtherBrowser ? 'ios-chrome' : 'safari';
		}
		return 'android';
	}

	function chromeIntentUrl() {
		return 'intent://' + location.host + location.pathname + location.search +
			'#Intent;scheme=' + location.protocol.replace(':', '') + ';package=com.android.chrome;S.browser_fallback_url=' +
			encodeURIComponent(location.href) + ';end';
	}

	function install() {
		if (deferredPrompt) {
			var promptEvent = deferredPrompt;
			deferredPrompt = null;
			promptEvent.prompt();
			promptEvent.userChoice.then(function (choice) {
				hideCard(!choice || choice.outcome !== 'accepted');
			}).catch(function () {
				hideCard(false);
			});
			return;
		}
		if (isSamsung) {
			location.href = chromeIntentUrl();
			return;
		}
		hideCard(false);
		openSheet(preferredTab());
	}

	/* ---------- Header menu, account menu and footer entries ([data-adn-install-entry]): always there in the browser ---------- */
	function showEntries() {
		var label = actionLabel();
		document.querySelectorAll('[data-adn-install-label]').forEach(function (el) {
			el.textContent = label;
		});
		document.querySelectorAll('[data-adn-install-entry]').forEach(function (el) {
			el.hidden = false;
		});
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest && event.target.closest('[data-adn-install]');
		if (trigger) {
			event.preventDefault();
			// From the header menu: close it first
			var openMenu = trigger.closest('.navbar-collapse.show');
			if (openMenu && window.bootstrap && window.bootstrap.Collapse) {
				window.bootstrap.Collapse.getOrCreateInstance(openMenu).hide();
			}
			install();
		}
	});

	/* ---------- Card ---------- */
	function bottomIsBusy() {
		var consent = document.getElementById('cookieConsent');
		var consentShown = consent && !consent.classList.contains('d-none') && consent.offsetParent !== null;
		return consentShown || !!document.querySelector('.modal.show, .offcanvas.show');
	}

	function canShowCard() {
		return !shownThisVisit() && !snoozed() && !BLOCKED_PATHS.test(location.pathname) && sheet.hidden;
	}

	function showCard(retried) {
		if (!card.hidden || !canShowCard()) {
			return;
		}
		if (bottomIsBusy()) {
			if (!retried) {
				setTimeout(function () { showCard(true); }, BUSY_RETRY_MS);
			}
			return;
		}
		if (isSamsung) {
			card.querySelector('[data-adn-install-text]').textContent = card.getAttribute('data-text-chrome');
		}
		markShown();
		card.hidden = false;
		window.requestAnimationFrame(function () {
			card.classList.add('is-in');
		});
	}

	function hideCard(snooze) {
		card.classList.remove('is-in');
		card.hidden = true;
		if (snooze) {
			var state = readState();
			state.dismissedAt = Date.now();
			writeState(state);
		}
	}

	card.querySelector('[data-adn-install-dismiss]').addEventListener('click', function () {
		hideCard(true);
	});

	/* ---------- Step-by-step guide ---------- */
	var tabs = Array.prototype.slice.call(sheet.querySelectorAll('[data-adn-tab]'));

	function selectTab(name, focus) {
		tabs.forEach(function (tab) {
			var on = tab.getAttribute('data-adn-tab') === name;
			tab.setAttribute('aria-selected', on ? 'true' : 'false');
			tab.tabIndex = on ? 0 : -1;
			tab.classList.toggle('is-on', on);
			if (on && focus) {
				tab.focus();
			}
		});
		sheet.querySelectorAll('[data-adn-pane]').forEach(function (pane) {
			pane.hidden = pane.getAttribute('data-adn-pane') !== name;
		});
	}

	function onSheetKey(event) {
		if (event.key === 'Escape') {
			closeSheet();
			return;
		}
		var current = tabs.indexOf(document.activeElement);
		if (current !== -1 && (event.key === 'ArrowRight' || event.key === 'ArrowLeft')) {
			var step = (event.key === 'ArrowRight') === (root.dir !== 'rtl') ? 1 : -1;
			var next = tabs[(current + step + tabs.length) % tabs.length];
			selectTab(next.getAttribute('data-adn-tab'), true);
			event.preventDefault();
		}
	}

	function openSheet(tab) {
		lastFocus = document.activeElement;
		selectTab(tab, false);
		sheet.hidden = false;
		root.classList.add('adn-install-sheet-open');
		document.addEventListener('keydown', onSheetKey);
		var close = sheet.querySelector('.adn-install-close');
		if (close) {
			close.focus();
		}
	}

	function closeSheet() {
		sheet.hidden = true;
		root.classList.remove('adn-install-sheet-open');
		document.removeEventListener('keydown', onSheetKey);
		if (lastFocus && typeof lastFocus.focus === 'function') {
			lastFocus.focus();
		}
	}

	sheet.addEventListener('click', function (event) {
		if (event.target.closest('[data-adn-sheet-close]')) {
			closeSheet();
			return;
		}
		var tab = event.target.closest('[data-adn-tab]');
		if (tab) {
			selectTab(tab.getAttribute('data-adn-tab'), false);
		}
	});

	/* ---------- Start ---------- */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', showEntries);
	} else {
		showEntries();
	}

	window.addEventListener('beforeinstallprompt', function (event) {
		event.preventDefault();
		if (isSamsung) {
			return;
		}
		deferredPrompt = event;
		setTimeout(showCard, SHOW_DELAY_MS);
	});

	window.addEventListener('appinstalled', function () {
		deferredPrompt = null;
		hideCard(true);
	});

	// Browsers without an install prompt
	if (isIOS || isAndroidFirefox || isSamsung) {
		setTimeout(showCard, SHOW_DELAY_MS);
	}
})(window, document);
