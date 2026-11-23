/*
 * adniro: the chat's settings (assets/js/app/chat.js).
 * - The email about unread messages, on or off: saved on the account as soon as the switch moves; if saving
 *   fails the switch goes back.
 * - The sound and the browser notification for a new message: kept in this browser (chat-alerts.js). Turning
 *   notifications on asks the browser for permission the first time.
 */
(function (window, document) {
	'use strict';

	/** @param {{T: Object, URLS: Object, kit: Object}} ctx */
	window.AdnChatSettings = function (ctx) {
		var emails = document.querySelector('[data-chat-emails]');
		var sound = document.querySelector('[data-chat-sound]');
		var desktop = document.querySelector('[data-chat-desktop]');
		var alerts = window.AdnChatAlerts || null;

		if (emails) {
			emails.addEventListener('change', function () {
				var isOn = emails.checked;
				emails.disabled = true;

				ctx.kit.request('PUT', ctx.URLS.settings, {emails: isOn}).then(function (data) {
					emails.checked = !!data.emails;
					ctx.kit.toast(data.emails ? ctx.T.emails_on : ctx.T.emails_off);
				}).catch(function (error) {
					emails.checked = !isOn;
					ctx.kit.toast(error.message, 'error');
				}).then(function () {
					emails.disabled = false;
				});
			});
		}

		if (!sound || !desktop) {
			return;
		}
		if (!alerts) {
			sound.closest('label').hidden = true;
			desktop.closest('label').hidden = true;
			return;
		}

		var prefs = alerts.prefs();
		sound.checked = prefs.sound;
		desktop.checked = prefs.desktop === 'on';
		// A browser that cannot show notifications is not offered them
		desktop.closest('label').hidden = prefs.desktop === 'unsupported';

		sound.addEventListener('change', function () {
			alerts.setSound(sound.checked);
		});
		desktop.addEventListener('change', function () {
			desktop.disabled = true;
			alerts.setDesktop(desktop.checked).then(function (state) {
				desktop.checked = state === 'on';
				desktop.disabled = false;
				if (state === 'on') {
					ctx.kit.toast(ctx.T.desktop_on);
				} else if (state === 'blocked') {
					ctx.kit.toast(ctx.T.desktop_blocked, 'error');
				} else if (state === 'unsupported') {
					ctx.kit.toast(ctx.T.desktop_unsupported, 'error');
				}
			});
		});
	};
})(window, document);
