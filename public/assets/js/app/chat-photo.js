/*
 * adniro: shrinks a photo in the browser before it is sent in a chat (assets/js/app/chat.js).
 * The photo is drawn to a canvas no larger than maxSide and saved as a JPEG: a small, fast upload, with the
 * camera's location data gone. The server checks and re-saves it again (App\Services\Chat\ChatPhotos).
 */
(function (window) {
	'use strict';

	var OUTPUT_TYPE = 'image/jpeg';

	function decode(file) {
		if (window.createImageBitmap) {
			return window.createImageBitmap(file, {imageOrientation: 'from-image'}).catch(function () {
				return decodeWithImage(file);
			});
		}

		return decodeWithImage(file);
	}

	function decodeWithImage(file) {
		return new Promise(function (resolve, reject) {
			var url = URL.createObjectURL(file);
			var image = new Image();
			image.onload = function () {
				URL.revokeObjectURL(url);
				resolve(image);
			};
			image.onerror = function () {
				URL.revokeObjectURL(url);
				reject(new Error('decode'));
			};
			image.src = url;
		});
	}

	/**
	 * @param {File} file
	 * @param {{maxSide: number, quality: number}} options
	 * @returns {Promise<Blob>} rejects with Error('decode') when the browser cannot read the photo
	 */
	function shrink(file, options) {
		return decode(file).then(function (source) {
			var width = source.naturalWidth || source.width;
			var height = source.naturalHeight || source.height;
			if (!width || !height) {
				throw new Error('decode');
			}

			var scale = Math.min(1, options.maxSide / Math.max(width, height));
			var canvas = document.createElement('canvas');
			canvas.width = Math.max(1, Math.round(width * scale));
			canvas.height = Math.max(1, Math.round(height * scale));

			var context = canvas.getContext('2d');
			// JPEG has no transparency: a white page behind transparent PNG and WebP photos
			context.fillStyle = '#fff';
			context.fillRect(0, 0, canvas.width, canvas.height);
			context.drawImage(source, 0, 0, canvas.width, canvas.height);
			if (typeof source.close === 'function') {
				source.close();
			}

			return new Promise(function (resolve, reject) {
				canvas.toBlob(function (blob) {
					if (blob) {
						resolve(blob);
					} else {
						reject(new Error('decode'));
					}
				}, OUTPUT_TYPE, options.quality);
			});
		});
	}

	window.AdnChatPhoto = {shrink: shrink};
})(window);
