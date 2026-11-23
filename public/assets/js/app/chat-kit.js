/*
 * adniro: small helpers shared by the live chat scripts (assets/js/app/chat.js): building elements, requests
 * to the chat's data routes, dates, and links in message text. Text always goes in through textContent.
 */
(function (window, document) {
	'use strict';

	var URL_PATTERN = /(https?:\/\/[^\s<]+[^\s<.,;:!?)\]'"])/g;

	/** @param {Object<string, string>} T the chat texts (lang/<locale>/chat.php, "js") */
	window.AdnChatKit = function (T) {
		var lang = document.documentElement.lang || undefined;
		var timeFormat = new Intl.DateTimeFormat(lang, {hour: '2-digit', minute: '2-digit'});
		var dayFormat = new Intl.DateTimeFormat(lang, {weekday: 'short', day: 'numeric', month: 'short'});
		var dateFormat = new Intl.DateTimeFormat(lang, {day: 'numeric', month: 'short', year: 'numeric'});

		function fill(text, params) {
			return String(text).replace(/:(\w+)/g, function (match, key) {
				return Object.prototype.hasOwnProperty.call(params, key) ? params[key] : match;
			});
		}

		function urlFor(template, id) {
			return template.replace('__ID__', encodeURIComponent(id));
		}

		function el(tag, className, text) {
			var node = document.createElement(tag);
			if (className) {
				node.className = className;
			}
			if (text !== undefined && text !== null) {
				node.textContent = text;
			}
			return node;
		}

		function icon(name, className) {
			var node = el('i', 'bi bi-' + name + (className ? ' ' + className : ''));
			node.setAttribute('aria-hidden', 'true');
			return node;
		}

		function uuid() {
			if (window.crypto && typeof window.crypto.randomUUID === 'function') {
				return window.crypto.randomUUID();
			}
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (char) {
				var random = Math.random() * 16 | 0;
				return (char === 'x' ? random : (random & 0x3 | 0x8)).toString(16);
			});
		}

		function csrfToken() {
			var meta = document.querySelector('meta[name="csrf-token"]');
			return meta ? meta.getAttribute('content') : '';
		}

		function firstError(data) {
			if (data && data.errors) {
				var keys = Object.keys(data.errors);
				if (keys.length && data.errors[keys[0]].length) {
					return data.errors[keys[0]][0];
				}
			}
			return data && typeof data.message === 'string' ? data.message : '';
		}

		function request(method, url, payload) {
			var headers = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken()};
			var init = {method: method, headers: headers, credentials: 'same-origin'};
			if (payload instanceof FormData) {
				init.body = payload;
			} else if (payload) {
				headers['Content-Type'] = 'application/json';
				init.body = JSON.stringify(payload);
			}

			return fetch(url, init).then(function (response) {
				return response.json().catch(function () {
					return {};
				}).then(function (data) {
					if (response.ok) {
						return data;
					}
					if (response.status === 419 || response.status === 401) {
						window.location.reload(); // the session ended: sign in again
					}
					var message = response.status === 429 ? T.too_fast : firstError(data);
					var error = new Error(message || T.error_generic);
					error.status = response.status;
					throw error;
				});
			}, function () {
				var error = new Error(T.error_offline);
				error.status = 0;
				throw error;
			});
		}

		function live() {
			return window.AdnChatLive || null;
		}

		function toast(message, type) {
			if (typeof window.Swal === 'undefined') {
				return;
			}
			window.Swal.fire({toast: true, position: 'top', timer: 3000, showConfirmButton: false, icon: type || 'success', text: message});
		}

		function sameDay(a, b) {
			return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
		}

		function dayLabel(date) {
			var now = new Date();
			var yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
			if (sameDay(date, now)) {
				return T.today;
			}
			if (sameDay(date, yesterday)) {
				return T.yesterday;
			}
			return date.getFullYear() === now.getFullYear() ? dayFormat.format(date) : dateFormat.format(date);
		}

		function shortTime(iso) {
			var date = new Date(iso);
			return sameDay(date, new Date()) ? timeFormat.format(date) : dayLabel(date);
		}

		function avatarInto(node, person, online) {
			node.textContent = '';
			node.classList.toggle('is-online', !!online);
			if (person && person.photo) {
				var image = el('img');
				image.src = person.photo;
				image.alt = '';
				image.loading = 'lazy';
				node.appendChild(image);
				return;
			}
			node.textContent = person && person.name ? person.name.trim().charAt(0).toUpperCase() : '?';
		}

		function linkified(text) {
			var fragment = document.createDocumentFragment();
			String(text).split(URL_PATTERN).forEach(function (part, index) {
				if (index % 2 === 0) {
					fragment.appendChild(document.createTextNode(part));
					return;
				}
				var link = el('a', '', part);
				link.href = part;
				link.target = '_blank';
				link.rel = 'noopener noreferrer nofollow';
				fragment.appendChild(link);
			});
			return fragment;
		}

		/** Fills the pinned listing card; a listing that is gone is shown as no longer available. */
		function listingInto(els, post) {
			els.listing.hidden = !post;
			if (!post) {
				return;
			}
			els.listing.classList.toggle('is-gone', !post.available);
			if (post.available && post.url) {
				els.listing.href = post.url;
				els.viewListing.href = post.url;
			} else {
				els.listing.removeAttribute('href');
			}
			els.viewListing.parentNode.hidden = !(post.available && post.url);
			els.listingTitle.textContent = post.title;
			els.listingPrice.textContent = post.available ? post.price : T.unavailable;

			els.listingPhoto.textContent = '';
			if (post.photo) {
				var image = el('img');
				image.src = post.photo;
				image.alt = '';
				els.listingPhoto.appendChild(image);
			} else {
				els.listingPhoto.appendChild(icon('image'));
			}
		}

		function timeOf(date) {
			return timeFormat.format(date);
		}

		return {
			fill: fill,
			urlFor: urlFor,
			el: el,
			icon: icon,
			uuid: uuid,
			request: request,
			live: live,
			toast: toast,
			sameDay: sameDay,
			dayLabel: dayLabel,
			shortTime: shortTime,
			timeOf: timeOf,
			avatarInto: avatarInto,
			listingInto: listingInto,
			linkified: linkified
		};
	};
})(window, document);
