/*
 * adniro: photos waiting to be sent in a chat (assets/js/app/chat.js): picked with the photo button (several at
 * once), pasted into the message box, or dragged onto the conversation. Each is shrunk first (chat-photo.js)
 * and shown small above the message box, where it can be removed. Each photo is sent as its own message.
 */
(function (window, document) {
	'use strict';

	var MAX_AT_ONCE = 6;
	var PHOTO = {maxSide: 1600, quality: 0.82};
	var KB = 1024;

	/**
	 * @param {{T: Object, LIMITS: Object, els: Object, store: Object, kit: Object}} ctx
	 * @param {{showError: function(string): void, changed: Function}} parts
	 */
	window.AdnChatAttach = function (ctx, parts) {
		var T = ctx.T;
		var els = ctx.els;
		var kit = ctx.kit;
		var picked = []; // {blob: Blob, preview: string}
		var reading = Promise.resolve();

		function canAttach() {
			return !!ctx.LIMITS.photos && !!ctx.store.open && !!ctx.store.open.canSend;
		}

		function render() {
			els.tray.textContent = '';
			picked.forEach(function (photo, index) {
				var item = kit.el('li', 'adn-chat-tray-item');
				var image = kit.el('img');
				image.src = photo.preview;
				image.alt = kit.fill(T.photo_number, {number: index + 1});
				image.width = 64;
				image.height = 64;
				item.appendChild(image);

				var remove = kit.el('button', 'adn-chat-photo-remove');
				remove.type = 'button';
				remove.setAttribute('aria-label', els.tray.dataset.removeLabel + ' ' + (index + 1));
				remove.appendChild(kit.icon('x-lg'));
				remove.addEventListener('click', function () {
					URL.revokeObjectURL(photo.preview);
					picked = picked.filter(function (other) {
						return other !== photo;
					});
					render();
					(els.tray.querySelector('button') || els.input).focus();
				});
				item.appendChild(remove);
				els.tray.appendChild(item);
			});
			els.tray.hidden = picked.length === 0;
			parts.changed();
		}

		/** Shrinks one photo and puts it in the tray. A photo the browser cannot read is skipped, with a word why. */
		function read(file) {
			return window.AdnChatPhoto.shrink(file, PHOTO).then(function (blob) {
				if (blob.size > ctx.LIMITS.photo_max_kb * KB) {
					parts.showError(T.error_photo_size);
					return;
				}
				if (picked.length < MAX_AT_ONCE) {
					picked.push({blob: blob, preview: URL.createObjectURL(blob)});
					render();
				}
			}).catch(function () {
				parts.showError(T.error_photo_read);
			});
		}

		/** @param {FileList|Array<File>} files what was picked, pasted or dropped */
		function add(files) {
			parts.showError('');
			var images = Array.prototype.filter.call(files || [], function (file) {
				return /^image\//.test(file.type);
			});
			if (!images.length) {
				if (files && files.length) {
					parts.showError(T.error_photo_type);
				}
				return;
			}
			var room = MAX_AT_ONCE - picked.length;
			if (images.length > room) {
				parts.showError(kit.fill(T.error_photos_many, {max: MAX_AT_ONCE}));
			}
			// One after the other: they keep the order they were picked in
			images.slice(0, Math.max(0, room)).forEach(function (file) {
				reading = reading.then(function () {
					return read(file);
				});
			});
		}

		/** The photos to send. Their previews now belong to the messages, which free them. */
		function take() {
			var photos = picked;
			picked = [];
			render();
			return photos;
		}

		function clear() {
			picked.forEach(function (photo) {
				URL.revokeObjectURL(photo.preview);
			});
			picked = [];
			render();
		}

		function hasFiles(event) {
			var types = event.dataTransfer ? event.dataTransfer.types : null;
			return !!types && Array.prototype.indexOf.call(types, 'Files') !== -1;
		}

		els.photoBtn.addEventListener('click', function () {
			els.photoInput.click();
		});
		els.photoInput.addEventListener('change', function () {
			add(els.photoInput.files);
			els.photoInput.value = ''; // the same photo can be picked again
		});

		// A photo copied from elsewhere, pasted into the message box
		els.input.addEventListener('paste', function (event) {
			var files = event.clipboardData ? event.clipboardData.files : null;
			if (!files || !files.length || !canAttach()) {
				return;
			}
			event.preventDefault();
			add(files);
		});

		// Photos dragged from the computer onto the conversation
		['dragenter', 'dragover'].forEach(function (name) {
			els.open.addEventListener(name, function (event) {
				if (!hasFiles(event) || !canAttach()) {
					return;
				}
				event.preventDefault();
				els.drop.hidden = false;
			});
		});
		els.open.addEventListener('dragleave', function (event) {
			if (!els.open.contains(event.relatedTarget)) {
				els.drop.hidden = true;
			}
		});
		els.open.addEventListener('drop', function (event) {
			if (!hasFiles(event) || !canAttach()) {
				return;
			}
			event.preventDefault();
			els.drop.hidden = true;
			add(event.dataTransfer.files);
			els.input.focus();
		});

		return {
			take: take,
			clear: clear,
			count: function () {
				return picked.length;
			}
		};
	};
})(window, document);
