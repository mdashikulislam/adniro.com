/*
 * adniro: the message log of an open chat conversation (assets/js/app/chat.js): bubbles, day separators,
 * ticks (sending, sent, seen), and adding or completing a message without redrawing the rest.
 */
(function (window, document) {
	'use strict';

	var NEAR_BOTTOM_PX = 140;

	/** @param {{T: Object, ME: number, els: Object, store: Object, kit: Object}} ctx */
	window.AdnChatLog = function (ctx) {
		var T = ctx.T;
		var ME = ctx.ME;
		var els = ctx.els;
		var store = ctx.store;
		var el = ctx.kit.el;
		var icon = ctx.kit.icon;
		var sameDay = ctx.kit.sameDay;
		var dayLabel = ctx.kit.dayLabel;
		var timeOf = ctx.kit.timeOf;
		var linkified = ctx.kit.linkified;

		function isSeen(message) {
			return !!message.id && !!store.open.thread && message.id <= store.open.thread.read_up_to;
		}

		function tickFor(message) {
			var state = message.state === 'sending' ? 'sending' : (isSeen(message) ? 'seen' : 'sent');
			var names = {sending: 'clock', sent: 'check2', seen: 'check2-all'};
			var tick = el('i', 'bi bi-' + names[state] + ' adn-chat-tick');
			tick.setAttribute('role', 'img');
			tick.setAttribute('aria-label', T[state]);
			return tick;
		}

		/** The listing a message is about, shown above it: one conversation can cover several listings. */
		function listingRef(post) {
			var ref = el(post.available && post.url ? 'a' : 'div', 'adn-chat-ref');
			if (post.available && post.url) {
				ref.href = post.url;
			}

			var photo = el('span', 'adn-chat-ref-photo');
			photo.setAttribute('aria-hidden', 'true');
			if (post.photo) {
				var image = el('img');
				image.src = post.photo;
				image.alt = '';
				image.loading = 'lazy';
				photo.appendChild(image);
			} else {
				photo.appendChild(icon('image'));
			}
			ref.appendChild(photo);

			var text = el('span', 'adn-chat-ref-text');
			text.appendChild(el('strong', '', post.title));
			text.appendChild(el('span', '', post.available ? post.price : T.unavailable));
			ref.appendChild(text);

			return ref;
		}

		function messageNode(message, previous) {
			var mine = message.user_id === ME;
			var date = new Date(message.at);
			// A message that brings a new listing starts a new group, however close it follows
			var follows = !message.post && previous && previous.user_id === message.user_id && sameDay(new Date(previous.at), date);

			var node = el('div', 'adn-chat-msg');
			node.dataset.key = message.client_uuid || ('m' + message.id);
			node.classList.toggle('is-mine', mine);
			node.classList.toggle('is-follow', !!follows);
			node.classList.toggle('is-sending', message.state === 'sending');
			node.classList.toggle('is-seen', mine && isSeen(message));

			if (message.post) {
			node.appendChild(listingRef(message.post));
		}

		var bubble = el('div', 'adn-chat-bubble');
			bubble.appendChild(el('span', 'visually-hidden', (mine ? T.you : store.open.other.name) + ': '));
			if (message.type === 'image' && message.image) {
				bubble.classList.add('is-photo');
				var link = el('a');
				link.href = message.image.url;
				link.target = '_blank';
				link.rel = 'noopener';
				var image = el('img');
				image.src = message.image.thumb;
				image.alt = T.photo;
				image.loading = 'lazy';
				link.appendChild(image);
				bubble.appendChild(link);
			} else {
				bubble.appendChild(linkified(message.body));
			}

			var meta = el('span', 'adn-chat-meta');
			meta.appendChild(el('time', '', timeOf(date)));
			if (mine && message.state !== 'failed') {
				meta.appendChild(tickFor(message));
			}
			bubble.appendChild(meta);
			node.appendChild(bubble);

			if (!mine && message.caution) {
				node.appendChild(el('p', 'adn-chat-note', T.caution));
			}
			if (message.state === 'failed') {
				var retry = el('button', 'adn-chat-fail', message.error || T.failed);
				retry.type = 'button';
				retry.dataset.chatRetry = node.dataset.key;
				retry.setAttribute('role', 'alert');
				node.appendChild(retry);
			}

			return node;
		}

		function dayNode(date) {
			var node = el('div', 'adn-chat-day');
			node.appendChild(el('span', '', dayLabel(date)));
			return node;
		}

		function renderLog() {
			var fragment = document.createDocumentFragment();
			store.open.messages.forEach(function (message, index, all) {
				var previous = index > 0 ? all[index - 1] : null;
				if (!previous || !sameDay(new Date(previous.at), new Date(message.at))) {
					fragment.appendChild(dayNode(new Date(message.at)));
				}
				fragment.appendChild(messageNode(message, previous));
			});
			els.messages.textContent = '';
			els.messages.appendChild(fragment);
		}

		function isNearBottom() {
			return els.log.scrollHeight - els.log.scrollTop - els.log.clientHeight < NEAR_BOTTOM_PX;
		}

		function scrollToBottom() {
			els.log.scrollTop = els.log.scrollHeight;
			els.newBtn.hidden = true;
		}

		function appendMessage(message, animate) {
			var all = store.open.messages;
			var previous = all.length > 1 ? all[all.length - 2] : null;
			if (!previous || !sameDay(new Date(previous.at), new Date(message.at))) {
				els.messages.appendChild(dayNode(new Date(message.at)));
			}
			var node = messageNode(message, previous);
			node.classList.toggle('is-new', !!animate);
			els.messages.appendChild(node);
		}

		function redrawMessage(message) {
			var key = message.client_uuid || ('m' + message.id);
			var current = els.messages.querySelector('[data-key="' + key + '"]');
			if (!current) {
				return;
			}
			var index = store.open.messages.indexOf(message);
			current.replaceWith(messageNode(message, index > 0 ? store.open.messages[index - 1] : null));
		}

		function findMessage(message) {
			return store.open.messages.filter(function (item) {
				return (message.id && item.id === message.id) || (message.client_uuid && item.client_uuid === message.client_uuid);
			})[0] || null;
		}

		/** Adds a message from the server, or completes the one this tab is still sending. */
		function upsertMessage(message) {
			var existing = findMessage(message);
			if (existing) {
				var wasSending = existing.state === 'sending' || existing.state === 'failed';
				// A photo this tab sent keeps showing its local copy: no reload, no flicker
				var image = wasSending && existing.image ? existing.image : message.image;
				Object.assign(existing, message, {image: image, state: 'sent', error: null});
				redrawMessage(existing);
				return false;
			}

			var stick = isNearBottom() || message.user_id === ME;
			store.open.messages.push(message);
			appendMessage(message, true);
			if (stick) {
				scrollToBottom();
			} else {
				els.newBtn.hidden = false;
			}
			return true;
		}

		function refreshTicks() {
			store.open.messages.forEach(function (message) {
				if (message.user_id === ME && message.id) {
					redrawMessage(message);
				}
			});
		}

		function lastServerId() {
			var ids = store.open.messages.filter(function (message) {
				return !!message.id;
			}).map(function (message) {
				return message.id;
			});
			return ids.length ? Math.max.apply(null, ids) : 0;
		}

		function lastIdFromOther() {
			return store.open.messages.reduce(function (last, message) {
				return message.user_id !== ME && message.id > last ? message.id : last;
			}, 0);
		}

		/** Phones: keep the open conversation inside the visible screen while the keyboard is up. */
		function fitToKeyboard() {
			var conv = els.log.closest('[data-chat-conv]');
			var viewport = window.visualViewport;
			var fullScreen = document.body.classList.contains('is-chat-open') && window.matchMedia('(max-width: 991.98px)').matches;
			if (!viewport || !fullScreen) {
				conv.style.height = '';
				conv.style.top = '';
				return;
			}
			var near = isNearBottom();
			conv.style.height = viewport.height + 'px';
			conv.style.top = viewport.offsetTop + 'px';
			if (near) {
				scrollToBottom();
			}
		}

		/** Frees the previews of photos sent from this tab. */
		function releasePhotos(open) {
			(open ? open.messages : []).forEach(function (message) {
				if (message.photo && message.image) {
					URL.revokeObjectURL(message.image.url);
				}
			});
		}

		return {
			render: renderLog,
			isNearBottom: isNearBottom,
			scrollToBottom: scrollToBottom,
			append: appendMessage,
			redraw: redrawMessage,
			upsert: upsertMessage,
			refreshTicks: refreshTicks,
			lastServerId: lastServerId,
			lastIdFromOther: lastIdFromOther,
			fitToKeyboard: fitToKeyboard,
			releasePhotos: releasePhotos
		};
	};
})(window, document);
