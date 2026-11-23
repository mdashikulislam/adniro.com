/*
 * adniro: offers in the chat (assets/js/app/chat.js): proposing a price for the listing the conversation is
 * about, the card an offer is shown as, and accepting or declining one.
 *
 * An offer and its answer are messages, so they arrive like any other. What changes on an older card (an offer
 * accepted, declined, or replaced by a newer one) is worked out here from the message that caused it, the same
 * way the server does it: nothing else has to be sent.
 */
(function (window, document) {
	'use strict';

	var AMOUNT_PATTERN = /^\d{1,9}(\.\d{1,2})?$/;
	var AMOUNT_INPUT_MAX = 16;
	var THOUSANDS_GROUP = 3;
	// Arabic, Persian, Bengali and Devanagari digits are typed as often as 0-9
	var DIGIT_ZEROS = [0x0660, 0x06f0, 0x09e6, 0x0966];
	var STATUS_ICONS = {accepted: 'check-circle-fill', declined: 'x-circle', replaced: 'arrow-repeat', pending: 'hourglass-split'};

	function asciiDigits(text) {
		return String(text).replace(/[^\u0000-\u007f]/g, function (char) {
			var code = char.charCodeAt(0);
			var zero = DIGIT_ZEROS.filter(function (base) {
				return code >= base && code <= base + 9;
			})[0];
			return zero === undefined ? char : String(code - zero);
		});
	}

	/**
	 * What the member typed, as a plain number: "1 200,50", "1.200,50" and "1,200.50" are all "1200.50".
	 * @returns {?string} null when it is not an amount
	 */
	function parseAmount(text) {
		var value = asciiDigits(text).replace(/[\s  ']/g, '').replace(/٫/g, '.').replace(/٬/g, ',');
		var hasComma = value.indexOf(',') !== -1;
		var hasDot = value.indexOf('.') !== -1;

		if (hasComma && hasDot) {
			// Both: the one that comes last is the decimal mark
			var decimalMark = value.lastIndexOf(',') > value.lastIndexOf('.') ? ',' : '.';
			value = value.split(decimalMark === ',' ? '.' : ',').join('').replace(',', '.');
		} else if (hasComma || hasDot) {
			var groups = value.split(hasComma ? ',' : '.');
			// "1.200" and "1,200,000" are thousands; "12,5" and "12.50" are decimals
			var isThousands = groups[0] !== '0' && groups[0].length <= THOUSANDS_GROUP && groups.slice(1).every(function (group) {
				return group.length === THOUSANDS_GROUP;
			});
			value = groups.join(isThousands ? '' : '.');
		}

		return AMOUNT_PATTERN.test(value) && Number(value) > 0 ? value : null;
	}

	/**
	 * @param {{T: Object, URLS: Object, ME: number, LIMITS: Object, els: Object, store: Object, kit: Object}} ctx
	 * @param {{log: Object, adopt: Function, reload: Function}} parts
	 */
	window.AdnChatOffers = function (ctx, parts) {
		var T = ctx.T;
		var URLS = ctx.URLS;
		var ME = ctx.ME;
		var store = ctx.store;
		var kit = ctx.kit;
		var el = kit.el;
		var icon = kit.icon;
		var fill = kit.fill;

		/** The listing an offer made now would be about. */
		function listing() {
			var open = store.open;
			if (!open) {
				return null;
			}
			if (open.about || open.draft) {
				return (open.about || open.draft).post;
			}
			return open.thread ? open.thread.post : null;
		}

		function canOffer() {
			var post = listing();
			return !!(ctx.LIMITS.offers && store.open.canSend && post && post.available && post.can_offer);
		}

		/* ---------- The card ---------- */
		function answerButton(message, answer) {
			var isAccept = answer === 'accept';
			var button = el('button', 'adn-chat-offer-btn' + (isAccept ? ' is-accept' : ''), isAccept ? T.offer_accept : T.offer_decline);
			button.type = 'button';
			button.dataset.chatOfferAnswer = answer;
			button.dataset.chatOfferId = message.id;
			return button;
		}

		function answerNode(offer) {
			var isAccepted = offer.status === 'accepted';
			var box = el('div', 'adn-chat-offer is-answer ' + (isAccepted ? 'is-accepted' : 'is-declined'));
			var head = el('span', 'adn-chat-offer-status');
			head.appendChild(icon(STATUS_ICONS[offer.status]));
			head.appendChild(el('strong', '', fill(isAccepted ? T.offer_accepted_line : T.offer_declined_line, {amount: offer.amount})));
			box.appendChild(head);
			if (isAccepted) {
				box.appendChild(el('span', 'adn-chat-offer-note', T.offer_accepted_note));
			}
			return box;
		}

		/** What goes inside the bubble of an offer, or of the answer to one. */
		function node(message) {
			var offer = message.offer;
			if (message.type === 'offer_answer') {
				return answerNode(offer);
			}

			var mine = message.user_id === ME;
			var box = el('div', 'adn-chat-offer is-' + offer.status);
			var label = el('span', 'adn-chat-offer-label');
			label.appendChild(icon('tag-fill'));
			label.appendChild(el('span', '', mine ? T.your_offer : T.offer));
			box.appendChild(label);
			box.appendChild(el('strong', 'adn-chat-offer-amount', offer.amount));

			if (offer.status === 'pending' && !mine && message.id && store.open.canSend) {
				var actions = el('div', 'adn-chat-offer-actions');
				actions.appendChild(answerButton(message, 'decline'));
				actions.appendChild(answerButton(message, 'accept'));
				box.appendChild(actions);
				return box;
			}

			var texts = {pending: T.offer_waiting, accepted: T.offer_accepted, declined: T.offer_declined, replaced: T.offer_replaced};
			var status = el('span', 'adn-chat-offer-status');
			status.appendChild(icon(STATUS_ICONS[offer.status] || STATUS_ICONS.pending));
			status.appendChild(el('span', '', texts[offer.status] || ''));
			box.appendChild(status);
			return box;
		}

		/**
		 * A message has arrived or was sent. A new offer closes the open ones on the same listing, and an answer
		 * closes the offer it answers: their cards are redrawn.
		 */
		function applied(message) {
			if (!message.offer || !store.open) {
				return;
			}
			store.open.messages.forEach(function (other) {
				if (other === message || !other.id || other.type !== 'offer' || !other.offer || other.offer.status !== 'pending') {
					return;
				}
				var isAnswered = message.type === 'offer_answer' && other.id === message.offer.offer_id;
				var isReplaced = message.type === 'offer' && !!message.id && other.id < message.id
					&& message.offer.post_id > 0 && other.offer.post_id === message.offer.post_id;
				if (isAnswered || isReplaced) {
					other.offer = Object.assign({}, other.offer, {status: isAnswered ? message.offer.status : 'replaced'});
					parts.log.redraw(other);
				}
			});
		}

		/* ---------- Making an offer ---------- */
		function send(open, post, amount) {
			var payload = {amount: amount, client_uuid: kit.uuid()};
			if (open.thread) {
				payload.thread = open.thread.id;
			}
			var sending = kit.request('POST', kit.urlFor(URLS.offer, post.id), payload).then(function (data) {
				if (store.open !== open) {
					return;
				}
				open.about = null; // the offer was about that listing: it is now the conversation's
				parts.adopt(open, data, true);
			}).catch(function (error) {
				kit.toast(error.message, 'error');
			});

			// A new conversation is created by the offer: messages written meanwhile wait for it
			if (!open.thread) {
				open.starting = sending.then(function () {
					open.starting = null;
				});
			}
		}

		function openDialog() {
			var open = store.open;
			var post = listing();
			if (!open || !canOffer() || typeof window.Swal === 'undefined') {
				return;
			}
			window.Swal.fire({
				title: T.offer_title,
				text: post.price ? post.title + ' · ' + post.price : post.title,
				input: 'text',
				inputLabel: fill(T.offer_amount_label, {currency: post.currency}),
				inputAttributes: {inputmode: 'decimal', autocomplete: 'off', maxlength: AMOUNT_INPUT_MAX},
				showCancelButton: true,
				confirmButtonText: T.offer_send,
				cancelButtonText: T.cancel,
				reverseButtons: true,
				inputValidator: function (value) {
					return parseAmount(value) ? undefined : T.offer_invalid;
				}
			}).then(function (result) {
				if (result.isConfirmed && store.open === open) {
					send(open, post, parseAmount(result.value));
				}
			});
		}

		/* ---------- Answering one ---------- */
		function confirmAccept(message) {
			if (typeof window.Swal === 'undefined') {
				return Promise.resolve(false);
			}
			return window.Swal.fire({
				icon: 'question',
				title: fill(T.offer_accept_title, {amount: message.offer.amount}),
				text: fill(T.offer_accept_text, {name: store.open.other.name}),
				showCancelButton: true,
				confirmButtonText: T.offer_accept,
				cancelButtonText: T.cancel,
				reverseButtons: true
			}).then(function (result) {
				return result.isConfirmed;
			});
		}

		function answer(button) {
			var open = store.open;
			var isAccept = button.dataset.chatOfferAnswer === 'accept';
			var message = open ? open.messages.filter(function (item) {
				return item.id === Number(button.dataset.chatOfferId);
			})[0] : null;
			if (!message || !open.thread) {
				return;
			}

			(isAccept ? confirmAccept(message) : Promise.resolve(true)).then(function (confirmed) {
				if (!confirmed || store.open !== open) {
					return;
				}
				button.parentNode.querySelectorAll('button').forEach(function (other) {
					other.disabled = true;
				});
				var url = kit.urlFor(URLS.offer_answer, open.thread.id).replace('__MESSAGE__', message.id);
				return kit.request('POST', url, {answer: isAccept ? 'accept' : 'decline'}).then(function (data) {
					if (store.open === open) {
						parts.adopt(open, data, false);
					}
				});
			}).catch(function (error) {
				kit.toast(error.message, 'error');
				if (store.open !== open) {
					return;
				}
				if (error.status === 409) {
					parts.reload(open.thread.id); // answered, replaced or withdrawn meanwhile: show what it is now
				} else {
					parts.log.redraw(message); // the buttons come back
				}
			});
		}

		ctx.els.messages.addEventListener('click', function (event) {
			var button = event.target.closest('[data-chat-offer-answer]');
			if (button) {
				answer(button);
			}
		});

		return {node: node, applied: applied, canOffer: canOffer, open: openDialog};
	};

	window.AdnChatOffers.parseAmount = parseAmount;
})(window, document);
