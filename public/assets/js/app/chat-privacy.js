/*
 * adniro: my chat privacy (assets/js/app/chat.js, front/chat/partials/privacy-dialog.blade.php): whether
 * others see me online and what I have read, and the people I blocked, who can be unblocked here.
 * A switch is saved as soon as it moves; if saving fails it goes back.
 */
(function (window, document) {
	'use strict';

	/**
	 * @param {{T: Object, URLS: Object, PRIVACY: Object, kit: Object}} ctx
	 * @param {{changed: Function, unblocked: function(number): void}} parts
	 */
	window.AdnChatPrivacy = function (ctx, parts) {
		var T = ctx.T;
		var kit = ctx.kit;
		var dialog = document.getElementById('adnChatPrivacy');
		if (!dialog) {
			return;
		}
		var online = dialog.querySelector('[data-chat-privacy-online]');
		var seen = dialog.querySelector('[data-chat-privacy-seen]');
		var list = dialog.querySelector('[data-chat-blocked-list]');

		function note(text) {
			list.textContent = '';
			list.appendChild(kit.el('li', 'adn-chat-blocked-none', text));
		}

		function personRow(person) {
			var row = kit.el('li', 'adn-chat-blocked-row');
			var avatar = kit.el('span', 'adn-chat-avatar adn-chat-avatar-sm');
			avatar.setAttribute('aria-hidden', 'true');
			kit.avatarInto(avatar, person, false);
			row.appendChild(avatar);
			row.appendChild(kit.ownDir(kit.el('span', 'adn-chat-blocked-name', person.name)));

			var unblock = kit.el('button', 'btn btn-sm btn-outline-secondary', T.unblock);
			unblock.type = 'button';
			unblock.setAttribute('aria-label', kit.fill(T.unblock_name, {name: person.name}));
			unblock.addEventListener('click', function () {
				unblock.disabled = true;
				kit.request('DELETE', kit.urlFor(ctx.URLS.block, person.id)).then(function () {
					row.remove();
					if (!list.children.length) {
						note(T.blocked_none);
					}
					kit.toast(kit.fill(T.unblocked_done, {name: person.name}));
					parts.unblocked(person.id);
				}).catch(function (error) {
					unblock.disabled = false;
					kit.toast(error.message, 'error');
				});
			});
			row.appendChild(unblock);

			return row;
		}

		/** Takes the choices as they are now. Returns whether they differ from what this tab knew. */
		function show(privacy) {
			var isNew = !!privacy.online !== !!ctx.PRIVACY.online || !!privacy.seen !== !!ctx.PRIVACY.seen;
			ctx.PRIVACY = privacy;
			online.checked = !!privacy.online;
			seen.checked = !!privacy.seen;
			return isNew;
		}

		function load() {
			note(T.loading);
			kit.request('GET', ctx.URLS.privacy).then(function (data) {
				show(data.privacy);
				list.textContent = '';
				data.blocked.forEach(function (person) {
					list.appendChild(personRow(person));
				});
				if (!data.blocked.length) {
					note(T.blocked_none);
				}
			}).catch(function (error) {
				note(error.message || T.load_error);
			});
		}

		function save() {
			var before = ctx.PRIVACY;
			online.disabled = true;
			seen.disabled = true;

			kit.request('PUT', ctx.URLS.privacy, {online: online.checked, seen: seen.checked}).then(function (data) {
				var isNew = show(data.privacy);
				kit.toast(T.tools_saved);
				if (isNew) {
					parts.changed(); // unless the live event below got here first
				}
			}).catch(function (error) {
				show(before);
				kit.toast(error.message, 'error');
			}).then(function () {
				online.disabled = false;
				seen.disabled = false;
			});
		}

		// Saved from any of my tabs or devices: every tab follows at once, so none goes on showing me online
		window.addEventListener('adn:chat:privacy', function (event) {
			if (show(event.detail.privacy)) {
				parts.changed();
			}
		});

		online.addEventListener('change', save);
		seen.addEventListener('change', save);
		document.querySelectorAll('[data-chat-privacy-open]').forEach(function (button) {
			button.addEventListener('click', function () {
				if (!window.bootstrap) {
					return;
				}
				window.bootstrap.Modal.getOrCreateInstance(dialog).show();
				load();
			});
		});
	};
})(window, document);
