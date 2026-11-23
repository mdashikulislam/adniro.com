/*
 * adniro: replying to one message in a chat (assets/js/app/chat.js). The Reply button beside a bubble
 * (chat-log.js) puts a short quote of that message above the message box; the next message is sent as a reply
 * to it and shows the quote. A tap on a quote goes back to the message it answers.
 */
(function (window) {
	'use strict';

	var QUOTE_LENGTH = 90;
	var FLASH_MS = 1400;

	/** @param {{T: Object, ME: number, els: Object, store: Object, kit: Object}} ctx */
	window.AdnChatReply = function (ctx) {
		var T = ctx.T;
		var ME = ctx.ME;
		var els = ctx.els;
		var store = ctx.store;
		var fill = ctx.kit.fill;

		function keyOf(message) {
			return message.client_uuid || ('m' + message.id);
		}

		function nameOf(userId) {
			return userId === ME ? T.you : store.open.other.name;
		}

		function quoteOf(message) {
			if (message.offer) {
				// An offer is quoted by its amount; an answer says which answer it was
				var kind = message.type === 'offer' ? 'offer' : 'offer_' + message.offer.status;
				return {id: message.id, user_id: message.user_id, type: kind, text: message.offer.amount};
			}
			var text = String(message.body || '').replace(/\s+/g, ' ').trim();
			return {
				id: message.id,
				user_id: message.user_id,
				type: message.type,
				text: text.length > QUOTE_LENGTH ? text.slice(0, QUOTE_LENGTH) + '…' : text
			};
		}

		function clear() {
			if (store.open) {
				store.open.replyTo = null;
			}
			els.replying.hidden = true;
		}

		function start(message) {
			var quote = quoteOf(message);
			store.open.replyTo = quote;
			els.replyingName.textContent = nameOf(quote.user_id);
			els.replyingBody.textContent = ctx.kit.lineText(quote.type, quote.text);
			els.replying.setAttribute('aria-label', fill(T.replying_to, {name: nameOf(quote.user_id)}));
			els.replying.hidden = false;
			els.input.focus();
		}

		/** The quote for the message being sent, if it is a reply; the bar is then closed. */
		function take() {
			var quote = store.open ? store.open.replyTo : null;
			clear();
			return quote || null;
		}

		/** Goes to the message a quote points at, when it is among the ones loaded, and lights it up briefly. */
		function jumpTo(messageId) {
			var message = store.open.messages.filter(function (item) {
				return item.id === messageId;
			})[0];
			var node = message ? els.messages.querySelector('[data-key="' + keyOf(message) + '"]') : null;
			if (!node) {
				return;
			}
			// Only the message area scrolls (scrollIntoView would move the whole page too)
			var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			var offset = node.getBoundingClientRect().top - els.log.getBoundingClientRect().top;
			els.log.scrollTo({
				top: els.log.scrollTop + offset - (els.log.clientHeight - node.offsetHeight) / 2,
				behavior: calm ? 'auto' : 'smooth'
			});
			node.classList.add('is-flash');
			window.setTimeout(function () {
				node.classList.remove('is-flash');
			}, FLASH_MS);
		}

		els.messages.addEventListener('click', function (event) {
			var replyBtn = event.target.closest('[data-chat-reply]');
			if (replyBtn && store.open) {
				var message = store.open.messages.filter(function (item) {
					return keyOf(item) === replyBtn.dataset.chatReply;
				})[0];
				if (message) {
					start(message);
				}
				return;
			}
			var quote = event.target.closest('[data-chat-quote]');
			if (quote && store.open) {
				jumpTo(Number(quote.dataset.chatQuote));
			}
		});
		els.replyingCancel.addEventListener('click', clear);

		return {take: take, clear: clear};
	};
})(window);
