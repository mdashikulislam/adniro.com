/*
 * Cookie consent (adniro). Decided in the browser, so pages cached at the edge work for every country.
 *
 * Inert until allowed: the footer tracking code (<template id="consentGatedScripts">, e.g. Google
 * Analytics) and the ad slots (<template data-consent-ad>). Visitors from the regions on the banner
 * (the UK, the EEA, Switzerland) see the banner and get them after "Accept"; everyone else gets them
 * at once. The visitor's country comes from Cloudflare (/cdn-cgi/trace); when it can't be read, the
 * visitor is asked. An explicit "Decline" is respected everywhere.
 */
(function () {
	'use strict';

	var banner = document.getElementById('cookieConsent');
	if (!banner) {
		return;
	}
	var cookieName = banner.dataset.cookie || 'site_cookie_consent';
	var maxAge = parseInt(banner.dataset.maxAge, 10) || 31536000;
	var regions = (banner.dataset.consentRegions || '').split(',');
	var REGION_KEY = 'adn_consent_region';
	var SCRIPT_TIMEOUT = 5000; /* a blocked ad script must not hold up the next slot */
	var UNKNOWN_REGIONS = ['XX', 'T1']; /* Cloudflare: country unknown, Tor */
	var activated = false;
	var writeShimInstalled = false;
	var slice = Array.prototype.slice;

	function readChoice() {
		var match = document.cookie.match(new RegExp('(?:^|; )' + cookieName + '=(granted|denied)'));
		return match ? match[1] : null;
	}

	function saveChoice(choice) {
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = cookieName + '=' + choice + '; Max-Age=' + maxAge + '; Path=/; SameSite=Lax' + secure;
	}

	/* ---------- the visitor's region ---------- */

	function rememberRegion(code) {
		try { sessionStorage.setItem(REGION_KEY, code); } catch (e) { /* private mode: ask again next page */ }
	}

	function storedRegion() {
		try { return sessionStorage.getItem(REGION_KEY); } catch (e) { return null; }
	}

	/* callback(true) when the visitor must be asked */
	function regionNeedsConsent(callback) {
		var decide = function (code) {
			callback(!code || UNKNOWN_REGIONS.indexOf(code) !== -1 || regions.indexOf(code) !== -1);
		};
		var known = banner.dataset.region || storedRegion();
		if (known) {
			return decide(known);
		}
		if (!window.fetch) {
			return decide(null);
		}
		fetch(banner.dataset.regionUrl || '/cdn-cgi/trace', {credentials: 'omit', cache: 'no-store'})
			.then(function (response) { return response.ok ? response.text() : ''; })
			.then(function (text) {
				var match = text.match(/^loc=([A-Z0-9]{2})$/m);
				var code = match && UNKNOWN_REGIONS.indexOf(match[1]) === -1 ? match[1] : null;
				if (code) {
					rememberRegion(code);
				}
				decide(code);
			})
			.catch(function () { decide(null); });
	}

	/* ---------- running inert code ---------- */

	function eachInTurn(items, worker, done) {
		var index = 0;
		(function next() {
			if (index >= items.length) {
				return done();
			}
			worker(items[index++], next);
		})();
	}

	function copyAttributes(from, to) {
		slice.call(from.attributes).forEach(function (attribute) {
			to.setAttribute(attribute.name, attribute.value);
		});
	}

	/*
	 * Ad snippets often document.write(). After the page has loaded, a real document.write() would wipe
	 * the page, so it is replaced for good: a write from one of our ad scripts goes into that script's
	 * slot; any other write after load is dropped.
	 */
	function installWriteShim() {
		if (writeShimInstalled) {
			return;
		}
		writeShimInstalled = true;
		var nativeWrite = document.write;
		var nativeWriteln = document.writeln;
		var route = function (html, native, args) {
			var script = document.currentScript;
			if (script && script.adWrite) {
				script.adWrite(html);
			} else if (document.readyState === 'loading') {
				native.apply(document, args);
			}
		};
		document.write = function () { route(slice.call(arguments).join(''), nativeWrite, arguments); };
		document.writeln = function () { route(slice.call(arguments).join('') + '\n', nativeWriteln, arguments); };
	}

	function placeHtml(html, parent, before, done) {
		if (!html || !parent) {
			return done();
		}
		var fragment = document.createRange().createContextualFragment(html);
		insertNodes(slice.call(fragment.childNodes), parent, before, done);
	}

	/* Runs one script where it stands; what it writes is placed right after it */
	function runScript(original, parent, before, done) {
		var script = document.createElement('script');
		var written = [];
		var finished = false;
		copyAttributes(original, script);
		script.adWrite = function (html) {
			if (!finished) {
				written.push(html);
			} else {
				/* written after the timeout: still placed right after its script */
				placeHtml(html, script.parentNode, script.nextSibling, function () {});
			}
		};

		function finish() {
			if (finished) {
				return;
			}
			finished = true;
			placeHtml(written.join(''), parent, before, done);
		}

		if (original.src) {
			script.async = false;
			script.onload = script.onerror = finish;
			setTimeout(finish, SCRIPT_TIMEOUT);
			parent.insertBefore(script, before);
		} else {
			script.text = original.text;
			parent.insertBefore(script, before); /* an inline script runs on insertion */
			finish();
		}
	}

	/* An element is placed with its scripts taken out, then each script runs in its own spot */
	function insertElement(node, parent, before, done) {
		var clone = document.importNode(node, true);
		var nested = clone.querySelectorAll ? slice.call(clone.querySelectorAll('script')) : [];
		var spots = nested.map(function (script) {
			var mark = document.createComment('');
			script.parentNode.replaceChild(mark, script);
			return {script: script, mark: mark};
		});
		parent.insertBefore(clone, before);
		eachInTurn(spots, function (spot, next) {
			runScript(spot.script, spot.mark.parentNode, spot.mark, function () {
				spot.mark.parentNode.removeChild(spot.mark);
				next();
			});
		}, done);
	}

	function insertNodes(nodes, parent, before, done) {
		eachInTurn(nodes, function (node, next) {
			if (node.nodeName === 'SCRIPT') {
				runScript(node, parent, before, next);
			} else {
				insertElement(node, parent, before, next);
			}
		}, done);
	}

	/*
	 * The ad slots, in page order. A slot hidden at this screen size (desktop/tablet/mobile) waits, and
	 * runs if the screen is resized or turned so that it shows.
	 */
	function runAdSlots() {
		var slots = slice.call(document.querySelectorAll('template[data-consent-ad]:not([data-running])')).filter(function (template) {
			return template.parentElement && template.parentElement.getClientRects().length > 0;
		});
		slots.forEach(function (template) {
			template.setAttribute('data-running', '');
		});
		eachInTurn(slots, function (template, next) {
			var parent = template.parentNode;
			insertNodes(slice.call(template.content.childNodes), parent, template, function () {
				parent.removeChild(template);
				next();
			});
		}, function () {});
	}

	/* The footer tracking code, at the end of the page */
	function runTrackingCode() {
		var template = document.getElementById('consentGatedScripts');
		if (!template) {
			return;
		}
		slice.call(template.content.querySelectorAll('script')).forEach(function (original) {
			var script = document.createElement('script');
			copyAttributes(original, script);
			script.text = original.text;
			document.body.appendChild(script);
		});
	}

	function runGatedScripts() {
		if (activated) {
			return;
		}
		activated = true;
		installWriteShim();
		runTrackingCode();
		runAdSlots();
		var resizeTimer = null;
		window.addEventListener('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(runAdSlots, 300);
		});
	}

	/* ---------- the banner ---------- */

	function show() {
		banner.classList.remove('d-none');
	}

	function hide() {
		banner.classList.add('d-none');
	}

	slice.call(banner.querySelectorAll('[data-consent]')).forEach(function (button) {
		button.addEventListener('click', function () {
			var choice = button.dataset.consent === 'granted' ? 'granted' : 'denied';
			var wasRunning = activated;
			saveChoice(choice);
			hide();
			if (choice === 'granted') {
				runGatedScripts();
			} else if (wasRunning) {
				/* Analytics and ads already running on this page: a reload stops them */
				location.reload();
			}
		});
	});

	/* "Cookie settings" links reopen the banner, wherever the visitor is */
	document.addEventListener('click', function (event) {
		var link = event.target.closest ? event.target.closest('[data-cookie-settings]') : null;
		if (link) {
			event.preventDefault();
			show();
		}
	});

	var choice = readChoice();
	if (choice === 'granted') {
		runGatedScripts();
	} else if (choice === null) {
		regionNeedsConsent(function (mustAsk) {
			if (mustAsk) {
				show();
			} else {
				runGatedScripts();
			}
		});
	}
})();
