/*
 * adniro: the chat's settings (assets/js/app/chat.js): the switch that turns the email about unread messages
 * on or off. The choice is saved as soon as the switch moves; if saving fails the switch goes back.
 */
(function (window, document) {
	'use strict';

	/** @param {{T: Object, URLS: Object, kit: Object}} ctx */
	window.AdnChatSettings = function (ctx) {
		var emails = document.querySelector('[data-chat-emails]');
		if (!emails) {
			return;
		}

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
	};
})(window, document);
