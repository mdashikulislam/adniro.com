/*
 * adniro: a chat photo, full size, without leaving the conversation (assets/js/app/chat.js,
 * front/chat/partials/viewer.blade.php). Goes through the photos of the open conversation: the arrow keys, the
 * two buttons, or a swipe. Escape, the close button or a tap beside the photo closes it.
 * A browser without <dialog> keeps the old way: the photo opens in a new tab.
 */
(function (window, document) {
	'use strict';

	var SWIPE_PX = 48;

	/** @param {{T: Object, ME: number, els: Object, store: Object, kit: Object}} ctx */
	window.AdnChatViewer = function (ctx) {
		var T = ctx.T;
		var kit = ctx.kit;
		var store = ctx.store;
		var dialog = document.getElementById('adnChatViewer');
		if (!dialog || typeof dialog.showModal !== 'function') {
			return;
		}

		function part(name) {
			return dialog.querySelector('[data-chat-viewer-' + name + ']');
		}

		var image = part('img');
		var wait = part('wait');
		var stage = part('stage');
		var previous = part('prev');
		var next = part('next');
		var photos = [];
		var index = 0;
		var opener = null;
		var loadNumber = 0;
		var swipeFrom = null;

		function keyOf(message) {
			return message.client_uuid || ('m' + message.id);
		}

		function show(position) {
			index = position;
			var message = photos[index];
			var date = new Date(message.at);
			var who = message.user_id === ctx.ME ? T.you : store.open.other.name;

			part('who').textContent = who;
			part('when').textContent = kit.dayLabel(date) + ', ' + kit.timeOf(date);
			part('count').textContent = photos.length > 1 ? kit.fill(T.viewer_count, {number: index + 1, total: photos.length}) : '';
			part('open').href = message.image.url;
			previous.hidden = photos.length < 2;
			next.hidden = photos.length < 2;
			previous.disabled = index === 0;
			next.disabled = index === photos.length - 1;

			// The small photo is already on the page: it shows at once, the full one replaces it when it is here
			var number = ++loadNumber;
			var full = new Image();
			image.alt = message.user_id === ctx.ME ? T.photo_mine : kit.fill(T.photo_from, {name: who});
			image.src = message.image.thumb;
			wait.hidden = message.image.thumb === message.image.url;
			full.onload = function () {
				if (number === loadNumber) {
					image.src = message.image.url;
					wait.hidden = true;
				}
			};
			full.onerror = function () {
				if (number === loadNumber) {
					wait.hidden = true;
				}
			};
			full.src = message.image.url;
		}

		function step(by) {
			var position = index + by;
			if (position >= 0 && position < photos.length) {
				show(position);
			}
		}

		function open(key, trigger) {
			photos = store.open ? store.open.messages.filter(function (message) {
				return message.type === 'image' && !!message.image && !message.unsent;
			}) : [];
			var position = photos.map(keyOf).indexOf(key);
			if (position === -1) {
				return;
			}
			opener = trigger;
			dialog.showModal();
			show(position);
			part('close').focus();
		}

		ctx.els.messages.addEventListener('click', function (event) {
			var link = event.target.closest('[data-chat-photo-open]');
			// With Ctrl, Cmd or Shift the link does what links do: a new tab or window
			if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
				return;
			}
			event.preventDefault();
			open(link.dataset.chatPhotoOpen, link);
		});

		part('close').addEventListener('click', function () {
			dialog.close();
		});
		previous.addEventListener('click', function () {
			step(-1);
		});
		next.addEventListener('click', function () {
			step(1);
		});
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog || event.target === stage) {
				dialog.close(); // beside the photo
			}
		});
		dialog.addEventListener('keydown', function (event) {
			// The arrows follow the page: in a right-to-left language the left arrow goes forward
			var forward = document.documentElement.dir === 'rtl' ? 'ArrowLeft' : 'ArrowRight';
			if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
				event.preventDefault();
				step(event.key === forward ? 1 : -1);
			}
		});
		stage.addEventListener('pointerdown', function (event) {
			swipeFrom = event.pointerType === 'mouse' ? null : event.clientX;
		});
		stage.addEventListener('pointerup', function (event) {
			var moved = swipeFrom === null ? 0 : event.clientX - swipeFrom;
			swipeFrom = null;
			if (Math.abs(moved) >= SWIPE_PX) {
				var towardsNext = document.documentElement.dir === 'rtl' ? moved > 0 : moved < 0;
				step(towardsNext ? 1 : -1);
			}
		});
		dialog.addEventListener('close', function () {
			loadNumber += 1;
			image.removeAttribute('src');
			// Back to the photo it was opened from; if the message was drawn again meanwhile, to the messages
			(opener && document.contains(opener) ? opener : ctx.els.log).focus();
			opener = null;
		});

		// The photo on screen was taken back by its sender
		window.addEventListener('adn:chat:unsent', function (event) {
			if (dialog.open && photos[index] && photos[index].id === event.detail.message.id) {
				dialog.close();
			}
		});
	};
})(window, document);
