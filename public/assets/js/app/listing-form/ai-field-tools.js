/*
 * adniro: AI writing help beside the listing form's Title and Description.
 * "AI help" menu -> the server writes a suggestion -> a panel under the field shows it with
 * Use this / Try again / Dismiss. Nothing is replaced until the seller chooses, and Undo brings
 * their own text back. All text is set with textContent.
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	if (!LF || !LF.undo || !document.querySelector('[data-lf-write]')) {
		return;
	}

	const MIN_TEXT = 10;
	const TIMEOUT_MS = 30000;
	const UNDO_MS = 20000;
	const RETRY_REASONS = ['ai_unavailable', 'busy', 'rate', 'network'];
	const cfg = LF.config;
	const panels = {};
	/** @type {Object<string, number>} the latest request per field; older answers are ignored */
	const requests = {};
	const undoTimers = {};

	function wrapperOf(field) {
		if (field === 'description') {
			return LF.form.querySelector('.lf-description');
		}
		const input = LF.byName(field);
		return input ? input.closest('.mb-3, [class*="col-"]') : null;
	}

	function toggleOf(field) {
		return document.getElementById('lfAiTools-' + field);
	}

	/** What the seller has written that the AI would work from */
	function sourceLength(field, action) {
		const state = LF.state();
		if (action !== 'suggest') {
			return (field === 'title' ? state.title : state.description).length;
		}
		return (state.title + ' ' + state.description).trim().length;
	}

	function button(label, className, onClick) {
		const el = document.createElement('button');
		el.type = 'button';
		el.className = 'btn btn-sm ' + className;
		el.textContent = label;
		el.addEventListener('click', onClick);
		return el;
	}

	/** The panel under a field, created once and reused */
	function panelFor(field) {
		if (panels[field] && panels[field].isConnected) {
			return panels[field];
		}
		const wrapper = wrapperOf(field);
		const panel = document.createElement('div');
		panel.className = 'col-12 lf-ai-panel';
		panel.setAttribute('role', 'region');
		panel.setAttribute('aria-live', 'polite');
		panel.setAttribute('aria-label', LF.text('ai_suggestion'));
		wrapper.after(panel);
		panels[field] = panel;
		return panel;
	}

	function closePanel(field, returnFocus) {
		window.clearTimeout(undoTimers[field]);
		const panel = panels[field];
		if (panel) {
			panel.remove();
			delete panels[field];
		}
		if (returnFocus && toggleOf(field)) {
			toggleOf(field).focus();
		}
	}

	function render(field, parts) {
		const panel = panelFor(field);
		panel.replaceChildren(...parts.filter(Boolean));
		panel.setAttribute('aria-busy', 'false');
		return panel;
	}

	function message(field, text, isError, retry) {
		const p = document.createElement('p');
		p.className = 'lf-ai-panel-message' + (isError ? ' is-error' : '');
		p.textContent = text;
		const actions = document.createElement('div');
		actions.className = 'lf-ai-panel-actions';
		if (retry) {
			actions.append(button(LF.text('ai_again'), 'btn-outline-primary', retry));
		}
		actions.append(button(LF.text('ai_dismiss'), 'btn-link', () => closePanel(field, true)));
		render(field, [p, actions]);
	}

	function showSuggestion(field, text, again) {
		const heading = document.createElement('p');
		heading.className = 'lf-ai-panel-heading';
		heading.textContent = LF.text('ai_suggestion');
		const body = document.createElement('div');
		body.className = 'lf-ai-panel-text';
		body.textContent = text;
		const note = document.createElement('p');
		note.className = 'lf-ai-panel-note';
		note.textContent = LF.text('ai_check');
		const actions = document.createElement('div');
		actions.className = 'lf-ai-panel-actions';
		const use = button(LF.text('ai_use'), 'btn-primary', () => useText(field, text));
		actions.append(use, button(LF.text('ai_again'), 'btn-outline-primary', again),
			button(LF.text('ai_dismiss'), 'btn-link', () => closePanel(field, true)));
		render(field, [heading, body, note, actions]);
		use.focus({preventScroll: true});
	}

	function useText(field, text) {
		LF.undo.apply(field, text);

		const status = document.createElement('p');
		status.className = 'lf-ai-panel-message';
		status.setAttribute('role', 'status');
		status.textContent = LF.text('ai_used') + ' ';
		const undo = button(LF.text('ai_undo'), 'btn-link lf-ai-undo', () => {
			// Nothing to undo once the seller has typed over the AI text
			if (!LF.undo.restore(field)) {
				closePanel(field, true);
				return;
			}
			status.textContent = LF.text('ai_undone');
			window.setTimeout(() => closePanel(field, false), 2500);
		});
		status.append(undo);
		render(field, [status]);
		undo.focus({preventScroll: true});

		// The undo offer goes once the seller moves on
		window.clearTimeout(undoTimers[field]);
		undoTimers[field] = window.setTimeout(() => {
			LF.undo.forget(field);
			closePanel(field, false);
		}, UNDO_MS);
	}

	function updateLimit(text) {
		if (!text) {
			return;
		}
		document.querySelectorAll('[data-lf-write-limit]').forEach((el) => {
			el.textContent = text;
		});
	}

	function request(field, action) {
		// One request per field at a time: double clicks would only hit the "busy" lock
		if (requests[field] && requests[field].busy) {
			return;
		}
		if (sourceLength(field, action) < MIN_TEXT) {
			message(field, LF.text('ai_need_text'), false, null);
			return;
		}
		const again = () => request(field, action);
		const working = document.createElement('p');
		working.className = 'lf-ai-panel-message';
		working.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ';
		working.append(document.createTextNode(LF.text('ai_working')));
		render(field, [working]).setAttribute('aria-busy', 'true');

		const id = (requests[field] ? requests[field].id : 0) + 1;
		requests[field] = {id: id, busy: true};
		const isCurrent = () => requests[field] && requests[field].id === id;
		
		const body = new FormData();
		body.append('field', field);
		body.append('action', action);
		body.append('title', LF.rawValue('title'));
		body.append('description', LF.rawValue('description'));
		body.append('category_id', String(LF.state().categoryId || ''));
		if (cfg.postId) {
			body.append('post_id', String(cfg.postId));
		}
		const token = document.querySelector('meta[name="csrf-token"]');
		const controller = new AbortController();
		const timer = window.setTimeout(() => controller.abort(), TIMEOUT_MS);

		window.fetch(cfg.writeUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			signal: controller.signal,
			headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''},
		})
			.then((response) => response.json().catch(() => ({ok: false, reason: 'network'})))
			.then((json) => {
				if (!isCurrent()) {
					return;
				}
				updateLimit(json.limit_text);
				if (json.ok && json.text) {
					showSuggestion(field, json.text, again);
					return;
				}
				const retry = RETRY_REASONS.includes(json.reason) ? again : null;
				message(field, json.message || LF.text('ai_error'), true, retry);
			})
			.catch(() => {
				if (isCurrent()) {
					message(field, LF.text('ai_error'), true, again);
				}
			})
			.finally(() => {
				window.clearTimeout(timer);
				if (isCurrent()) {
					requests[field].busy = false;
				}
			});
	}

	document.addEventListener('click', (event) => {
		const item = event.target.closest('[data-lf-write]');
		if (!item) {
			return;
		}
		event.preventDefault();
		request(item.dataset.lfField, item.dataset.lfWrite);
	});
})(window, document);
