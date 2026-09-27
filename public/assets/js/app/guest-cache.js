/*
 * Guest page cache helper
 * -----------------------
 * Pages served to cookie-less visitors come from the CDN cache and therefore carry
 * a CSRF token that does not belong to the visitor's (not yet existing) session.
 * This script fetches a real token from the server the first time it is needed
 * (first interaction, first non-GET form submit, first non-GET AJAX call) and
 * injects it everywhere the app reads it from.
 */
(function () {
	'use strict';
	
	var scriptEl = document.currentScript;
	var sessionCookie = (scriptEl && scriptEl.getAttribute('data-session-cookie')) || 'laravel_session';
	var tokenUrl = (scriptEl && scriptEl.getAttribute('data-token-url')) || '/common/csrf-token';
	
	var pending = null;
	
	/*
	 * The session cookie is HttpOnly (invisible to JS), so rely on:
	 *  - the server flag: pages rendered for a visitor with a session say data-has-session="1"
	 *    (cached guest pages are only ever rendered for cookie-less requests, so they say "0"),
	 *  - the XSRF-TOKEN cookie, which Laravel sets readable and which only exists with a session.
	 */
	function hasSessionCookie() {
		return document.cookie.indexOf(sessionCookie + '=') !== -1
			|| document.cookie.indexOf('XSRF-TOKEN=') !== -1;
	}
	var ready = (scriptEl && scriptEl.getAttribute('data-has-session') === '1') || hasSessionCookie();
	
	function applyToken(token) {
		if (!token) return;
		document.querySelectorAll('input[name="_token"]').forEach(function (el) { el.value = token; });
		document.querySelectorAll('meta[name="csrf-token"]').forEach(function (el) { el.setAttribute('content', token); });
		document.querySelectorAll('[data-csrf-token]').forEach(function (el) { el.setAttribute('data-csrf-token', token); });
		if (window.jQuery && window.jQuery.ajaxSetup) {
			window.jQuery.ajaxSetup({headers: {'X-CSRF-TOKEN': token}});
		}
		window.csrfToken = token;
		ready = true;
	}
	
	/* Asynchronous refresh (used on first interaction) */
	function refreshAsync() {
		if (ready) return Promise.resolve();
		if (pending) return pending;
		pending = fetch(tokenUrl, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}})
			.then(function (r) { return r.json(); })
			.then(function (d) { applyToken(d && d.token); })
			.catch(function () {})
			.then(function () { pending = null; });
		return pending;
	}
	
	/* Synchronous refresh (last resort, right before a request that needs the token) */
	function refreshSync() {
		if (ready) return;
		try {
			var xhr = new XMLHttpRequest();
			xhr.open('GET', tokenUrl, false);
			xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			xhr.setRequestHeader('Accept', 'application/json');
			xhr.send(null);
			if (xhr.status === 200) {
				var d = JSON.parse(xhr.responseText);
				applyToken(d && d.token);
			}
		} catch (e) {}
	}
	
	if (ready) return;
	
	/*
	 * No eager warm-up: starting a session would give the visitor a cookie and take every
	 * following page view off the CDN cache. The token is fetched only when a POST is about
	 * to happen (form submit, jQuery AJAX or fetch), synchronously, once per session.
	 */
	window.guestCacheRefreshToken = refreshAsync;
	
	/* Classic form submits */
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!(form instanceof HTMLFormElement)) return;
		if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') return;
		if (!form.querySelector('input[name="_token"]')) return;
		refreshSync();
	}, true);
	
	/* jQuery AJAX */
	if (window.jQuery && window.jQuery.ajaxPrefilter) {
		window.jQuery.ajaxPrefilter(function (options) {
			var type = (options.type || options.method || 'GET').toUpperCase();
			if (type === 'GET' || type === 'HEAD') return;
			refreshSync();
			if (!window.csrfToken) return;
			options.headers = options.headers || {};
			options.headers['X-CSRF-TOKEN'] = window.csrfToken;
			if (typeof options.data === 'string' && options.data.indexOf('_token=') !== -1) {
				options.data = options.data.replace(/(^|&)_token=[^&]*/, '$1_token=' + encodeURIComponent(window.csrfToken));
			} else if (options.data && typeof options.data === 'object' && '_token' in options.data) {
				options.data._token = window.csrfToken;
			}
		});
	}
	
	/* fetch() */
	if (window.fetch) {
		var nativeFetch = window.fetch;
		window.fetch = function (input, init) {
			var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
			var url = (typeof input === 'string') ? input : (input && input.url) || '';
			var isSameOrigin = (url.indexOf('http') !== 0) || (url.indexOf(window.location.origin + '/') === 0) || (url === window.location.origin);
			if (method !== 'GET' && method !== 'HEAD' && url.indexOf(tokenUrl) === -1 && isSameOrigin) {
				refreshSync();
				if (window.csrfToken) {
					init = init || {};
					var h = new Headers(init.headers || (input && input.headers) || {});
					h.set('X-CSRF-TOKEN', window.csrfToken);
					init.headers = h;
				}
			}
			return nativeFetch.call(this, input, init);
		};
	}
})();
