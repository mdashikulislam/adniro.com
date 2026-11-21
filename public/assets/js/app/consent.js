/*
 * Cookie consent (adniro). Decided in the browser, so cached guest pages show the banner only to
 * visitors who have not chosen yet. The scripts in <template id="consentGatedScripts"> (the footer
 * tracking code, e.g. Google Analytics) run only after "Accept".
 */
(function () {
	'use strict';

	var banner = document.getElementById('cookieConsent');
	if (!banner) {
		return;
	}
	var cookieName = banner.dataset.cookie || 'site_cookie_consent';
	var maxAge = parseInt(banner.dataset.maxAge, 10) || 31536000;
	var activated = false;

	function readChoice() {
		var match = document.cookie.match(new RegExp('(?:^|; )' + cookieName + '=(granted|denied)'));
		return match ? match[1] : null;
	}

	function saveChoice(choice) {
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = cookieName + '=' + choice + '; Max-Age=' + maxAge + '; Path=/; SameSite=Lax' + secure;
	}

	/* Scripts inside a <template> never run: copy each one into the page so the browser runs it */
	function runGatedScripts() {
		var template = document.getElementById('consentGatedScripts');
		if (activated || !template) {
			return;
		}
		activated = true;
		Array.prototype.forEach.call(template.content.querySelectorAll('script'), function (original) {
			var script = document.createElement('script');
			Array.prototype.forEach.call(original.attributes, function (attribute) {
				script.setAttribute(attribute.name, attribute.value);
			});
			script.text = original.text;
			document.body.appendChild(script);
		});
	}

	function show() {
		banner.classList.remove('d-none');
	}

	function hide() {
		banner.classList.add('d-none');
	}

	Array.prototype.forEach.call(banner.querySelectorAll('[data-consent]'), function (button) {
		button.addEventListener('click', function () {
			var choice = button.dataset.consent === 'granted' ? 'granted' : 'denied';
			var hadGranted = readChoice() === 'granted';
			saveChoice(choice);
			hide();
			if (choice === 'granted') {
				runGatedScripts();
			} else if (hadGranted) {
				/* Analytics already running on this page: a reload stops it */
				location.reload();
			}
		});
	});

	/* "Cookie settings" links reopen the banner */
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
		show();
	}
})();
