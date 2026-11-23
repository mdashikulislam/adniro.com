/*
 * adniro: the tools around the message box (assets/js/app/chat.js):
 * - quick replies: for a buyer who has not written about the listing yet, and for the listing's owner while a
 *   question waits for an answer;
 * - the "+" menu: make an offer, and the member's saved replies (one tap puts it in the message box);
 * - the dialog where a member writes their saved replies and their away message;
 * - "Mark as sold", for the owner of the listing the conversation is about.
 */
(function (window, document) {
	'use strict';

	/**
	 * @param {{T: Object, URLS: Object, ME: number, LIMITS: Object, TOOLS: Object, QUICK: Object, root: Element, els: Object, store: Object, kit: Object}} ctx
	 * @param {{offers: Object, inbox: Object, send: Function, listingChanged: Function}} parts
	 */
	window.AdnChatTools = function (ctx, parts) {
		var T = ctx.T;
		var ME = ctx.ME;
		var els = ctx.els;
		var store = ctx.store;
		var kit = ctx.kit;
		var el = kit.el;
		var icon = kit.icon;

		var more = ctx.root.querySelector('[data-chat-more]');
		var moreMenu = ctx.root.querySelector('[data-chat-more-menu]');
		var sold = ctx.root.querySelector('[data-chat-sold]');
		var chip = ctx.root.querySelector('[data-chat-away-chip]');
		var dialog = document.getElementById('adnChatTools');
		var form = dialog.querySelector('[data-chat-tools-form]');
		var awayOn = dialog.querySelector('[data-chat-away-on]');
		var awayMessage = dialog.querySelector('[data-chat-away-message]');
		var savedList = dialog.querySelector('[data-chat-saved-list]');
		var savedAdd = dialog.querySelector('[data-chat-saved-add]');
		var dialogError = dialog.querySelector('[data-chat-tools-error]');
		var save = dialog.querySelector('[data-chat-tools-save]');

		/* ---------- Quick replies ---------- */
		function quickReplies() {
			var open = store.open;
			if (!open || !open.canSend) {
				return [];
			}
			var hasWritten = open.messages.some(function (message) {
				return message.user_id === ME;
			});
			// Opened from a listing, and nothing has been written about it yet
			if ((open.draft && !hasWritten) || open.about) {
				return ctx.QUICK.buyer;
			}
			// My own listing, and the other person's question is the last thing said
			var post = open.thread ? open.thread.post : null;
			var last = open.messages[open.messages.length - 1];
			var isWaiting = !!last && !!last.id && last.user_id !== ME && !last.offer && !last.unsent;
			return post && post.available && post.owner_id === ME && isWaiting ? ctx.QUICK.seller : [];
		}

		function renderQuick() {
			var replies = quickReplies();
			els.quick.hidden = replies.length === 0;
			els.quick.textContent = '';
			replies.forEach(function (text) {
				var button = el('button', '', text);
				button.type = 'button';
				button.dataset.chatQuick = text;
				els.quick.appendChild(button);
			});
		}

		/* ---------- The "+" menu ---------- */
		function menuButton(iconName, text, name, value) {
			var button = el('button', 'dropdown-item');
			button.type = 'button';
			button.dataset[name] = value;
			if (iconName) {
				button.appendChild(icon(iconName));
			}
			button.appendChild(el('span', '', text));
			return button;
		}

		function fillMore() {
			moreMenu.textContent = '';
			if (parts.offers.canOffer()) {
				moreMenu.appendChild(menuButton('tag', T.offer_title, 'chatOfferOpen', '1'));
			}
			moreMenu.appendChild(el('h6', 'dropdown-header', T.saved_replies));
			ctx.TOOLS.saved_replies.forEach(function (text, index) {
				moreMenu.appendChild(menuButton(null, text, 'chatSavedPick', index)).classList.add('adn-chat-saved-item');
			});
			if (!ctx.TOOLS.saved_replies.length) {
				moreMenu.appendChild(el('p', 'adn-chat-plus-empty', T.saved_empty));
			}
			moreMenu.appendChild(menuButton('pencil', T.saved_edit, 'chatToolsOpen', '1'));
		}

		/** A saved reply goes into the message box, after what is already there: it can be changed before sending. */
		function insert(text) {
			var current = els.input.value;
			els.input.value = (current && !/\s$/.test(current) ? current + ' ' : current) + text;
			els.input.dispatchEvent(new Event('input', {bubbles: true}));
			els.input.focus();
		}

		/* ---------- The dialog: away message and saved replies ---------- */
		function modal() {
			return window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(dialog) : null;
		}

		function showDialogError(message) {
			dialogError.textContent = message || '';
			dialogError.hidden = !message;
		}

		function refreshAdd() {
			savedAdd.hidden = savedList.children.length >= ctx.LIMITS.saved_replies;
		}

		function savedRow(text) {
			var row = el('div', 'adn-chat-saved-row');
			var field = el('textarea', 'form-control');
			field.rows = 2;
			field.maxLength = ctx.LIMITS.tools_text_max;
			field.placeholder = savedList.dataset.placeholder;
			field.setAttribute('aria-label', savedList.dataset.replyLabel);
			field.value = text;
			row.appendChild(field);

			var remove = el('button', 'adn-chat-icon-btn');
			remove.type = 'button';
			remove.dataset.chatSavedRemove = '1';
			remove.setAttribute('aria-label', savedList.dataset.removeLabel);
			remove.appendChild(icon('x-lg'));
			row.appendChild(remove);
			return row;
		}

		function openDialog() {
			var instance = modal();
			if (!instance) {
				return;
			}
			showDialogError('');
			awayOn.checked = ctx.TOOLS.away_on;
			awayMessage.value = ctx.TOOLS.away_message;
			savedList.textContent = '';
			ctx.TOOLS.saved_replies.forEach(function (text) {
				savedList.appendChild(savedRow(text));
			});
			if (!savedList.children.length) {
				savedList.appendChild(savedRow(''));
			}
			refreshAdd();
			instance.show();
		}

		function saveDialog() {
			var replies = Array.prototype.map.call(savedList.querySelectorAll('textarea'), function (field) {
				return field.value.trim();
			}).filter(Boolean);
			var payload = {away_on: awayOn.checked, away_message: awayMessage.value.trim(), saved_replies: replies};

			showDialogError('');
			save.disabled = true;
			kit.request('PUT', ctx.URLS.tools, payload).then(function (data) {
				ctx.TOOLS = data.tools;
				chip.hidden = !ctx.TOOLS.away_on;
				modal().hide();
				kit.toast(T.tools_saved);
			}).catch(function (error) {
				showDialogError(error.message);
			}).then(function () {
				save.disabled = false;
			});
		}

		/* ---------- Mark as sold ---------- */
		function ownListing() {
			var post = store.open && store.open.thread ? store.open.thread.post : null;
			return post && post.id && post.available && post.owner_id === ME ? post : null;
		}

		function markSold() {
			var open = store.open;
			var post = ownListing();
			if (!post || typeof window.Swal === 'undefined') {
				return;
			}
			window.Swal.fire({
				icon: 'question',
				title: T.sold_title,
				text: T.sold_text,
				showCancelButton: true,
				confirmButtonText: T.sold_confirm,
				cancelButtonText: T.cancel,
				reverseButtons: true
			}).then(function (result) {
				if (!result.isConfirmed) {
					return;
				}
				return kit.request('POST', kit.urlFor(ctx.URLS.sold, post.id)).then(function (data) {
					parts.inbox.patch(open.thread.id, {post: data.post});
					if (store.open === open) {
						open.thread = Object.assign({}, open.thread, {post: data.post});
						parts.listingChanged(data.post);
					}
					kit.toast(T.sold_done);
				});
			}).catch(function (error) {
				kit.toast(error.message, 'error');
			});
		}

		/** After the conversation or what can be done in it changed. */
		function refresh() {
			sold.parentNode.hidden = !ownListing();
			renderQuick();
		}

		/* ---------- Events ---------- */
		more.addEventListener('show.bs.dropdown', fillMore);
		moreMenu.addEventListener('click', function (event) {
			var pick = event.target.closest('[data-chat-saved-pick]');
			if (pick) {
				insert(ctx.TOOLS.saved_replies[Number(pick.dataset.chatSavedPick)] || '');
			} else if (event.target.closest('[data-chat-offer-open]')) {
				parts.offers.open();
			}
		});
		// The dialog opens from the "+" menu, from the chat settings and from the "away" reminder
		ctx.root.addEventListener('click', function (event) {
			if (event.target.closest('[data-chat-tools-open]')) {
				openDialog();
			}
		});
		els.quick.addEventListener('click', function (event) {
			var button = event.target.closest('[data-chat-quick]');
			if (button) {
				parts.send(button.dataset.chatQuick);
			}
		});
		savedAdd.addEventListener('click', function () {
			var row = savedList.appendChild(savedRow(''));
			refreshAdd();
			row.querySelector('textarea').focus();
		});
		savedList.addEventListener('click', function (event) {
			var remove = event.target.closest('[data-chat-saved-remove]');
			if (remove) {
				remove.parentNode.remove();
				refreshAdd();
			}
		});
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			saveDialog();
		});
		sold.addEventListener('click', markSold);

		chip.hidden = !ctx.TOOLS.away_on;

		return {refresh: refresh, renderQuick: renderQuick};
	};
})(window, document);
