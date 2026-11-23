/*
 * adniro: the live chat page (front/chat/index.blade.php, assets/css/chat.css).
 *
 * The page's first data is in #adnChatState. This script draws the inbox and the open conversation, sends
 * messages (each with its own id, so a retry never sends twice), and keeps both up to date:
 *  - live, from the events assets/js/app/chat-live.js passes on (new message, read, connection state);
 *  - or, when the live connection is down, by asking for what it has missed every few seconds while the tab
 *    is visible.
 * Everything written to the page goes through textContent: message text is never treated as HTML.
 *
 * Parts, loaded before this file: chat-kit.js (helpers), chat-inbox.js (the list, its filters and search),
 * chat-log.js (the messages), chat-presence.js (online and typing), chat-reply.js (replying to one message),
 * chat-safety.js (block and report), chat-delete.js (taking a message back, deleting a chat), chat-drafts.js
 * (text not sent yet), chat-guard.js (a question before a risky message), chat-settings.js (emails, sound,
 * notifications), chat-photo.js and chat-attach.js (photos to send), chat-viewer.js (a photo, full size),
 * chat-offers.js (offers), chat-tools.js (quick and saved replies, away message, mark as sold), chat-tidy.js
 * (archive, mute, mark unread), chat-privacy.js (online, seen, blocked people).
 */
(function (window, document) {
	'use strict';

	var root = document.getElementById('adnChat');
	var stateEl = document.getElementById('adnChatState');
	if (!root || !stateEl) {
		return;
	}

	var boot = JSON.parse(stateEl.textContent);
	var T = boot.text;
	var URLS = boot.urls;
	var ME = boot.me.id;

	var CATCH_UP_MS = 10000;
	var INBOX_EVERY_TICKS = 3;
	var OFFLINE_BAR_AFTER_MS = 4000;
	var LOAD_OLDER_PX = 60;
	var COUNTER_FROM = 200;
	var INPUT_MAX_PX = 132;
	var SEND_TURN_MAX_MS = 20000;

	var body = document.body;

	function $(selector) {
		return root.querySelector(selector);
	}

	var els = {
		list: $('[data-chat-list]'),
		filters: root.querySelectorAll('[data-chat-filter]'),
		pick: $('[data-chat-pick]'),
		open: $('[data-chat-open]'),
		back: $('[data-chat-back]'),
		avatar: $('[data-chat-avatar]'),
		name: $('[data-chat-name]'),
		status: $('[data-chat-status]'),
		viewListing: $('[data-chat-view-listing]'),
		block: $('[data-chat-block]'),
		blockLabel: $('[data-chat-block-label]'),
		report: $('[data-chat-report]'),
		deleteChat: $('[data-chat-delete-chat]'),
		offline: $('[data-chat-offline]'),
		listing: $('[data-chat-listing]'),
		listingPhoto: $('[data-chat-listing-photo]'),
		listingLabel: $('[data-chat-listing-label]'),
		listingTitle: $('[data-chat-listing-title]'),
		listingPrice: $('[data-chat-listing-price]'),
		log: $('[data-chat-log]'),
		older: $('[data-chat-older]'),
		messages: $('[data-chat-messages]'),
		typing: $('[data-chat-typing]'),
		newBtn: $('[data-chat-new]'),
		quick: $('[data-chat-quick]'),
		tray: $('[data-chat-tray]'),
		drop: $('[data-chat-drop]'),
		replying: $('[data-chat-replying]'),
		replyingName: $('[data-chat-replying-name]'),
		replyingBody: $('[data-chat-replying-body]'),
		replyingCancel: $('[data-chat-replying-cancel]'),
		error: $('[data-chat-error]'),
		composer: $('[data-chat-composer]'),
		photoInput: $('[data-chat-photo-input]'),
		photoBtn: $('[data-chat-photo-btn]'),
		input: $('[data-chat-input]'),
		count: $('[data-chat-count]'),
		send: $('[data-chat-send]'),
		blocked: $('[data-chat-blocked]'),
		reportModal: document.getElementById('adnChatReport')
	};

	var store = {
		threads: boot.inbox.threads.slice(),
		nextCursor: boot.inbox.next_cursor,
		pagesLoaded: 1,
		filter: boot.filter || '',
		search: '',
		open: null,
		presence: null,
		typingTimer: null,
		typingSentAt: 0,
		offlineTimer: null,
		ticks: 0
	};

	/* ---------- Parts (chat-kit.js, chat-inbox.js, chat-log.js, chat-reply.js, chat-safety.js) ---------- */
	var kit = window.AdnChatKit(T);
	var fill = kit.fill;
	var urlFor = kit.urlFor;
	var el = kit.el;
	var icon = kit.icon;
	var uuid = kit.uuid;
	var request = kit.request;
	var live = kit.live;
	var toast = kit.toast;
	var avatarInto = kit.avatarInto;
	// The bar shows the listing the conversation is about, with no label. Only a listing about to be asked
	// about (opened from another listing's "Chat with seller") says so, since it is not the one talked about yet
	var renderListing = function (post) {
		kit.listingInto(els, post, store.open && (store.open.about || store.open.draft) ? T.asking_about : '');
	};

	var ctx = {
		T: T, URLS: URLS, ME: ME, LIMITS: boot.limits, root: root, els: els, store: store, kit: kit,
		TOOLS: boot.tools, PRIVACY: boot.privacy,
		QUICK: {buyer: boot.quick_replies, seller: boot.seller_quick_replies}
	};

	var inbox = window.AdnChatInbox(ctx);
	var renderInbox = inbox.render;
	var loadInbox = inbox.load;
	var upsertThread = inbox.upsert;
	var patchThread = inbox.patch;

	var log = window.AdnChatLog(ctx);
	var renderLog = log.render;
	var isNearBottom = log.isNearBottom;
	var scrollToBottom = log.scrollToBottom;
	var appendMessage = log.append;
	var redrawMessage = log.redraw;
	var upsertMessage = log.upsert;
	var refreshTicks = log.refreshTicks;
	var lastServerId = log.lastServerId;
	var lastIdFromOther = log.lastIdFromOther;
	var fitToKeyboard = log.fitToKeyboard;
	var releasePhotos = log.releasePhotos;
	var reply = window.AdnChatReply(ctx);

	var presence = window.AdnChatPresence(ctx, log);
	var setStatus = presence.setStatus;
	var hideTyping = presence.hideTyping;
	var joinPresence = presence.join;
	var leavePresence = presence.leave;
	var whisperTyping = presence.whisper;

	var drafts = window.AdnChatDrafts(ctx);
	var guard = window.AdnChatGuard(ctx, boot.safety);
	window.AdnChatSettings(ctx);

	window.AdnChatSafety(ctx, function (open, thread) {
		open.thread = thread;
		patchThread(thread.id, thread);
		open.online = false;
		setStatus('');
		renderComposer();
		renderLog();
		joinPresence();
	});

	/* ---------- Conversation: header, listing, composer ---------- */
	function renderComposer() {
		var open = store.open;
		var thread = open.thread;
		open.canSend = !boot.paused && (open.draft ? true : !!(thread && thread.can_send));

		els.composer.hidden = !open.canSend;
		els.blocked.hidden = open.canSend;
		els.blocked.textContent = '';
		if (boot.paused) {
			els.blocked.textContent = T.paused;
		} else if (!open.canSend && thread) {
			els.blocked.appendChild(document.createTextNode((thread.blocked_by_me ? T.blocked_by_me : T.cannot_send) + ' '));
			if (thread.blocked_by_me) {
				var unblock = el('button', '', els.block.dataset.unblockLabel);
				unblock.type = 'button';
				unblock.dataset.chatUnblock = '1';
				els.blocked.appendChild(unblock);
			}
		}

		els.block.parentNode.hidden = !thread;
		els.report.parentNode.hidden = !thread;
		els.deleteChat.parentNode.hidden = !thread;
		els.blockLabel.textContent = thread && thread.blocked_by_me ? els.block.dataset.unblockLabel : els.block.dataset.blockLabel;
		tools.refresh();
		tidy.refresh();
	}

	function showError(message) {
		els.error.textContent = message || '';
		els.error.hidden = !message;
	}

	function updateSendButton() {
		var text = els.input.value;
		els.send.disabled = !(text.trim() || attach.count());

		var left = boot.limits.body_max - text.length;
		els.count.hidden = left > COUNTER_FROM;
		els.count.textContent = fill(T.chars_left, {count: left});
	}

	function growInput() {
		els.input.style.height = 'auto';
		els.input.style.height = Math.min(els.input.scrollHeight, INPUT_MAX_PX) + 'px';
		// What is typed sets its own direction (Arabic on an English page, or the reverse); empty, the box
		// follows the page, so the placeholder sits where the page's language puts it
		if (els.input.value) {
			els.input.setAttribute('dir', 'auto');
		} else {
			els.input.removeAttribute('dir');
		}
	}

	/* ---------- Opening and closing a conversation ---------- */
	/** Tells the server how far this member has read, once per new message, only while the tab is visible. */
	function markRead() {
		var open = store.open;
		if (!open || !open.thread || document.hidden) {
			return;
		}
		// A conversation marked unread with nothing from the other person in view is read by its last message
		var lastId = lastIdFromOther() || (open.thread.unread > 0 ? lastServerId() : 0);
		if (lastId <= open.readSent) {
			return;
		}

		var threadId = open.thread.id;
		var sentBefore = open.readSent;
		open.readSent = lastId;
		request('POST', urlFor(URLS.read, threadId), {message_id: lastId}).then(function (data) {
			patchThread(threadId, {unread: 0});
			if (live()) {
				live().setUnread(data.unread_total);
			}
		}).catch(function () {
			open.readSent = sentBefore;
		});
	}

	function showOpen(data) {
		releasePhotos(store.open);
		store.open = {
			thread: data.thread || null,
			draft: data.draft || null,
			// Opened from another listing of the same person: the next message is about that listing
			about: data.about || null,
			other: data.thread ? data.thread.other : data.draft.other,
			messages: (data.messages || []).slice(),
			hasMore: !!data.has_more,
			online: false,
			loadingOlder: false,
			readSent: 0,
			// How far this tab has caught up with the server: moved only by the messages loaded with the
			// conversation and by catch-up answers. A message this tab sends, or one that arrives live, does
			// not move it, so nothing that came in between is ever skipped (they are asked for again, once)
			syncedId: 0,
			starting: null
		};
		var open = store.open;
		root.dataset.openThread = open.thread ? open.thread.id : ''; // chat-alerts.js: no alert for what is on screen
		// Already read on the server: nothing to tell it until a newer message arrives
		open.readSent = open.thread && open.thread.unread > 0 ? 0 : lastIdFromOther();
		open.syncedId = lastServerId();
		// Where the messages this member has not read yet begin: a "New messages" line is drawn there
		open.newFrom = log.firstUnreadKey();

		body.classList.add('is-chat-open');
		els.pick.hidden = true;
		els.open.hidden = false;
		els.name.textContent = open.other.name;
		avatarInto(els.avatar, open.other, false);
		renderListing(open.about ? open.about.post : (data.thread ? data.thread.post : data.draft.post));
		hideTyping();
		showError('');
		attach.clear();
		reply.clear();
		els.input.value = drafts.load(open); // what was typed here earlier and not sent
		growInput();
		updateSendButton();
		els.older.hidden = true;
		els.newBtn.hidden = true;

		renderComposer(); // first: whether messages get a Reply button depends on it
		renderLog();
		renderInbox();
		log.scrollToStart();
		joinPresence();
		markRead();
		fitToKeyboard();
	}

	function closeOpen() {
		releasePhotos(store.open);
		leavePresence();
		hideTyping();
		store.open = null;
		root.dataset.openThread = '';
		body.classList.remove('is-chat-open');
		els.open.hidden = true;
		els.pick.hidden = false;
		renderInbox();
	}

	function openThread(threadId, push) {
		return request('GET', urlFor(URLS.open, threadId)).then(function (data) {
			showOpen(data);
			if (push) {
				window.history.pushState({thread: threadId}, '', urlFor(URLS.thread, threadId));
			}
			return true;
		}).catch(function (error) {
			toast(error.message || T.load_error, 'error');
			return false;
		});
	}

	/**
	 * After a chat is picked from the list, the keyboard goes where the member will use it next: the message
	 * box on a computer, the person's name on a phone (the box would bring the keyboard up over the messages)
	 * or when nothing can be written.
	 */
	function focusConversation() {
		var open = store.open;
		if (!open) {
			return;
		}
		var writesHere = open.canSend && !window.matchMedia('(pointer: coarse)').matches;
		(writesHere ? els.input : els.name).focus({preventScroll: true});
	}

	/** Back to the list: the conversation closes and the keyboard returns to its row. */
	function leaveConversation(threadId) {
		closeOpen();
		window.history.pushState({}, '', URLS.inbox);
		fitToKeyboard();
		if (threadId) {
			inbox.focus(threadId, false);
		}
	}

	function loadOlder() {
		var open = store.open;
		if (!open || !open.thread || !open.hasMore || open.loadingOlder || !open.messages.length) {
			return;
		}
		open.loadingOlder = true;
		els.older.hidden = false;

		request('GET', urlFor(URLS.messages, open.thread.id) + '?before=' + open.messages[0].id).then(function (data) {
			if (store.open !== open) {
				return;
			}
			var heightBefore = els.log.scrollHeight;
			var topBefore = els.log.scrollTop;
			open.messages = data.messages.concat(open.messages);
			open.hasMore = data.has_more;
			renderLog();
			els.log.scrollTop = els.log.scrollHeight - heightBefore + topBefore;
		}).catch(function (error) {
			toast(error.message || T.load_error, 'error');
		}).then(function () {
			open.loadingOlder = false;
			els.older.hidden = true;
		});
	}

	/* ---------- Sending ---------- */
	/**
	 * The server saved something sent from here (a message, an offer, the answer to one): the conversation is
	 * now what it answered with. $changesListing: it was sent about another listing than the one shown.
	 */
	function adopt(open, data, changesListing) {
		var started = !open.thread;
		open.thread = data.thread;
		open.draft = null;
		root.dataset.openThread = data.thread.id;
		upsertMessage(data.message);
		upsertThread(data.thread);
		if (started || changesListing) {
			window.history.replaceState({thread: data.thread.id}, '', data.thread.url);
			renderListing(data.thread.post);
			renderComposer();
		} else {
			tools.renderQuick();
		}
		if (started) {
			joinPresence();
		}
	}

	/**
	 * Sends one message of a conversation: the one on screen, or the one it was written in when the member
	 * has since gone back to the list or opened another chat. What was sent is sent either way; only the
	 * screen is left alone when it shows something else.
	 */
	function deliver(local, conversation) {
		var open = conversation || store.open;
		local.state = 'sending';
		local.error = null;
		if (store.open === open) {
			redrawMessage(local);
		}

		// A new conversation is created by its first message: later ones wait for it
		if (!open.thread && open.starting) {
			return open.starting.then(function () {
				return deliver(local, open);
			});
		}

		var form = new FormData();
		form.append('client_uuid', local.client_uuid);
		if (local.reply_to) {
			form.append('reply_to', local.reply_to.id);
		}
		if (local.photo) {
			form.append('photo', local.photo, 'photo.jpg');
		} else {
			form.append('body', local.body);
		}

		var url = local.via || (open.thread ? urlFor(URLS.messages, open.thread.id) : open.draft.start_url);
		var sending = request('POST', url, form).then(function (data) {
			if (store.open === open) {
				adopt(open, data, !!local.via);
			}
		}).catch(function (error) {
			local.state = 'failed';
			local.error = [403, 413, 422, 429].indexOf(error.status) !== -1 ? error.message : T.failed;
			if (store.open === open) {
				redrawMessage(local);
			}
		});

		if (!open.thread) {
			open.starting = sending.then(function () {
				open.starting = null;
			});
		}
		return sending;
	}

	/**
	 * Messages leave one after the other, so several photos and a text arrive in the order they were sent.
	 * One that gets no answer does not hold the others back for ever: after a while the next one leaves.
	 */
	function deliverInTurn(local) {
		var open = store.open;
		var previous = open.sending || Promise.resolve();
		var waited = new Promise(function (resolve) {
			window.setTimeout(resolve, SEND_TURN_MAX_MS);
		});
		open.sending = Promise.race([previous, waited]).then(function () {
			return deliver(local, open); // also when the member has left this conversation meanwhile
		});
	}

	function sendMessage(text, photo) {
		var open = store.open;
		if (!open || !open.canSend) {
			return;
		}
		// The listing this message is sent from: the first one of a new conversation, or of a new subject
		var firstOfDraft = open.draft && !open.thread && !open.starting && !open.startQueued ? open.draft : null;
		open.startQueued = open.startQueued || !!firstOfDraft; // several sent together: only the first is "the first"
		var topic = open.about || firstOfDraft;
		var local = {
			id: null,
			post: topic ? topic.post : null,
			via: open.about ? open.about.start_url : null,
			client_uuid: uuid(),
			user_id: ME,
			type: photo ? 'image' : 'text',
			body: photo ? '' : text,
			image: photo ? {url: photo.preview, thumb: photo.preview} : null,
			photo: photo ? photo.blob : null,
			reply_to: reply.take(),
			at: new Date().toISOString(),
			state: 'sending'
		};
		open.about = null;
		open.messages.push(local);
		appendMessage(local, true);
		scrollToBottom();
		tools.renderQuick();
		deliverInTurn(local);
	}

	function submitComposer() {
		var text = els.input.value.trim();
		if (!text && !attach.count()) {
			return;
		}
		showError('');
		var open = store.open;
		// A phone number, an email address or "pay outside" wording is asked about first (chat-guard.js)
		guard.confirm(text).then(function (confirmed) {
			if (store.open !== open) {
				return;
			}
			if (!confirmed) {
				els.input.focus();
				return;
			}
			// Each photo is its own message; the messages keep the previews
			attach.take().forEach(function (photo) {
				sendMessage('', photo);
			});
			if (text) {
				drafts.clear(open);
				sendMessage(text, null);
			}
			els.input.value = '';
			growInput();
			updateSendButton();
			els.input.focus();
		});
	}

	/* ---------- Staying up to date ---------- */
	function catchUp() {
		var open = store.open;
		if (!open || !open.thread) {
			return;
		}
		request('GET', urlFor(URLS.messages, open.thread.id) + '?after=' + open.syncedId).then(function (data) {
			if (store.open !== open) {
				return;
			}
			var added = data.messages.map(upsertMessage).some(Boolean);
			deleting.markUnsentIds(data.unsent);
			data.messages.forEach(function (message) {
				open.syncedId = Math.max(open.syncedId, message.id);
			});
			if (data.read_up_to > open.thread.read_up_to) {
				open.thread = Object.assign({}, open.thread, {read_up_to: data.read_up_to});
				refreshTicks();
			}
			if (added) {
				hideTyping();
				markRead();
				tools.renderQuick();
			}
			if (data.has_more) {
				catchUp(); // a long absence: the answer comes in parts
			}
		}).catch(function () {
			// the next check tries again
		});
	}

	function onLiveMessage(detail) {
		upsertThread(detail.thread);
		var open = store.open;
		if (!open || !open.thread || open.thread.id !== detail.message.thread_id) {
			// In the open conversation the message itself is read out (chat-log.js); elsewhere, who wrote
			if (detail.message.user_id !== ME && !detail.thread.muted) {
				kit.announce(fill(T.new_message_from, {name: detail.thread.other.name}));
			}
			return;
		}
		open.thread = Object.assign({}, detail.thread, {read_up_to: Math.max(detail.thread.read_up_to, open.thread.read_up_to)});
		// syncedId is not moved here: a live message can overtake older ones still to be caught up
		upsertMessage(detail.message);
		tools.renderQuick();
		if (detail.message.user_id !== ME) {
			hideTyping();
			markRead();
		}
	}

	function onLiveRead(detail) {
		if (detail.reader_id === ME) {
			patchThread(detail.thread_id, {unread: 0}); // read in another tab
			return;
		}
		patchThread(detail.thread_id, {read_up_to: detail.message_id});
		if (store.open && store.open.thread && store.open.thread.id === detail.thread_id) {
			refreshTicks();
		}
	}

	function onLiveState(detail) {
		window.clearTimeout(store.offlineTimer);
		if (detail.connected) {
			els.offline.hidden = true;
			catchUp();
			loadInbox('refresh').catch(function () {});
			if (!store.presence) {
				joinPresence();
			}
			return;
		}
		store.offlineTimer = window.setTimeout(function () {
			els.offline.hidden = false;
		}, OFFLINE_BAR_AFTER_MS);
	}

	/** Without a live connection: ask for what was missed, only while the tab is visible. */
	function fallbackTick() {
		if (document.hidden || (live() && live().connected)) {
			return;
		}
		store.ticks += 1;
		catchUp();
		if (store.ticks % INBOX_EVERY_TICKS === 0) {
			loadInbox('refresh').catch(function () {});
		}
	}

	/* ---------- Events ---------- */
	els.list.addEventListener('click', function (event) {
		var row = event.target.closest('.adn-chat-row');
		if (!row || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
			return;
		}
		event.preventDefault();
		openThread(Number(row.dataset.threadId), true).then(function (opened) {
			if (opened) {
				focusConversation();
			}
		});
	});

	els.back.addEventListener('click', function (event) {
		event.preventDefault();
		leaveConversation(store.open && store.open.thread ? store.open.thread.id : 0);
	});

	window.addEventListener('popstate', function () {
		var match = window.location.pathname.match(/\/chat\/(\d+)$/);
		if (match) {
			openThread(Number(match[1]), false);
		} else if (/\/chat\/new\//.test(window.location.pathname)) {
			window.location.reload();
		} else {
			closeOpen();
		}
	});

	els.composer.addEventListener('submit', function (event) {
		event.preventDefault();
		submitComposer();
	});

	els.input.addEventListener('input', function () {
		growInput();
		updateSendButton();
		whisperTyping();
		drafts.save(store.open, els.input.value);
	});

	els.input.addEventListener('keydown', function (event) {
		var desktop = !window.matchMedia('(pointer: coarse)').matches;
		if (event.key === 'Enter' && !event.shiftKey && !event.isComposing && desktop) {
			event.preventDefault();
			submitComposer();
		}
	});

	els.messages.addEventListener('click', function (event) {
		var retry = event.target.closest('[data-chat-retry]');
		if (!retry || !store.open) {
			return;
		}
		var local = store.open.messages.filter(function (message) {
			return message.client_uuid === retry.dataset.chatRetry;
		})[0];
		if (local) {
			deliver(local);
		}
	});

	els.log.addEventListener('scroll', function () {
		if (els.log.scrollTop < LOAD_OLDER_PX) {
			loadOlder();
		}
		if (isNearBottom()) {
			els.newBtn.hidden = true;
		}
	}, {passive: true});

	els.newBtn.addEventListener('click', scrollToBottom);

	window.addEventListener('adn:chat:message', function (event) {
		onLiveMessage(event.detail);
	});
	window.addEventListener('adn:chat:read', function (event) {
		onLiveRead(event.detail);
	});
	window.addEventListener('adn:chat:state', function (event) {
		onLiveState(event.detail);
	});
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			catchUp();
			markRead();
		}
	});
	if (window.visualViewport) {
		window.visualViewport.addEventListener('resize', fitToKeyboard);
		window.visualViewport.addEventListener('scroll', fitToKeyboard);
	}
	window.setInterval(fallbackTick, CATCH_UP_MS);

	/* ---------- Start ---------- */
	ctx.offers = window.AdnChatOffers(ctx, {
		log: log,
		adopt: adopt,
		reload: function (threadId) {
			openThread(threadId, false);
		}
	});
	var tools = window.AdnChatTools(ctx, {
		offers: ctx.offers,
		inbox: inbox,
		send: function (text) {
			sendMessage(text, null);
		},
		listingChanged: function (post) {
			renderListing(post);
			renderComposer();
			renderLog(); // offers on it can no longer be accepted
		}
	});
	var deleting = window.AdnChatDelete(ctx, {
		log: log,
		inbox: inbox,
		close: function () {
			leaveConversation(0);
		}
	});
	var attach = window.AdnChatAttach(ctx, {
		showError: showError,
		changed: function () {
			updateSendButton();
			// The row of photos takes room from the messages: the last one stays in view
			if (store.open && isNearBottom()) {
				scrollToBottom();
			}
		}
	});
	window.AdnChatViewer(ctx);
	var tidy = window.AdnChatTidy(ctx, {inbox: inbox, close: leaveConversation});
	window.AdnChatPrivacy(ctx, {
		// Online and "seen" both changed what this conversation shows: it is loaded again
		changed: function () {
			if (store.open && store.open.thread) {
				openThread(store.open.thread.id, false);
			}
		},
		unblocked: function (userId) {
			loadInbox('refresh').catch(function () {});
			if (store.open && store.open.thread && store.open.other.id === userId) {
				openThread(store.open.thread.id, false);
			}
		}
	});
	els.photoBtn.hidden = !boot.limits.photos;
	els.block.dataset.blockLabel = els.blockLabel.textContent;
	els.block.dataset.unblockLabel = root.dataset.unblockLabel || '';
	renderInbox();
	if (boot.open) {
		showOpen(boot.open);
	} else if (boot.draft) {
		showOpen({draft: boot.draft});
	}
	updateSendButton();
})(window, document);
