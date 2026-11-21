/*
 * adniro: checks while filling in the redesigned listing form.
 * On leaving a field: a short error if what was typed is wrong. On submit: every missing
 * required field is marked and listed in a summary at the top, so nothing is lost on reload.
 * Only clear-cut checks run here, after the browser's own required checks; the server still
 * validates everything.
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	if (!LF) {
		return;
	}

	const form = LF.form;
	const cfg = LF.config;
	const SUMMARY_ID = 'lfErrorSummary';

	const wrapperOf = (el) => el ? el.closest('.mb-3, [class*="col-"], .form-check') : null;
	const lengthMessage = (length, min) => (length === 0)
		? LF.text('error_required')
		: LF.text('error_too_short', {min: min});

	/**
	 * Each rule: the element to focus and mark, whether it applies now, and its error (null when fine).
	 * "named" rules have a generic message, so the summary puts the field's label in front.
	 * @type {Array<{key: string, named?: boolean, field: function(): ?Element, applies: function(): boolean, error: function(object): ?string}>}
	 */
	const rules = [
		{
			key: 'category',
			field: () => form.querySelector('#catsContainer'),
			applies: () => true,
			error: (s) => (s.categoryId > 0) ? null : LF.text('error_category'),
		},
		{
			key: 'title',
			named: true,
			field: () => LF.byName('title'),
			applies: () => !!LF.byName('title'),
			error: (s) => (s.title.length >= (cfg.titleMin || 1)) ? null : lengthMessage(s.title.length, cfg.titleMin || 1),
		},
		{
			key: 'description',
			named: true,
			field: () => LF.byName('description'),
			applies: () => !!LF.byName('description'),
			error: (s) => (s.description.length >= (cfg.descriptionMin || 1))
				? null
				: lengthMessage(s.description.length, cfg.descriptionMin || 1),
		},
		{
			key: 'price',
			named: true,
			field: () => LF.byName('price'),
			applies: () => !!cfg.priceMandatory && LF.state().priceShown,
			error: (s) => (s.price !== '') ? null : LF.text('error_required'),
		},
		{
			key: 'city',
			field: () => form.querySelector('#cityBox'),
			applies: () => !!form.querySelector('#cityId'),
			error: (s) => s.cityId ? null : LF.text('error_city'),
		},
		{
			key: 'contact_name',
			named: true,
			field: () => LF.byName('contact_name'),
			applies: () => LF.isVisible(LF.byName('contact_name')),
			error: () => (String(LF.byName('contact_name').value).trim() !== '') ? null : LF.text('error_required'),
		},
		{
			key: 'email',
			field: () => LF.byName('email'),
			applies: () => LF.isVisible(LF.byName('email')) && LF.state().email !== '',
			// The browser's own check, which is no stricter than the server's
			error: () => LF.byName('email').validity.typeMismatch ? LF.text('error_email') : null,
		},
		{
			key: 'accept_terms',
			field: () => LF.byName('accept_terms'),
			applies: () => !!LF.byName('accept_terms'),
			error: () => LF.byName('accept_terms').checked ? null : LF.text('error_terms'),
		},
	];

	function errorElement(rule, create) {
		const id = 'lfError-' + rule.key;
		let el = document.getElementById(id);
		const wrapper = wrapperOf(rule.field());
		if (!el && create && wrapper) {
			el = document.createElement('div');
			el.id = id;
			el.className = 'lf-error';
			wrapper.appendChild(el);
		}
		return el;
	}

	/** The control screen readers should tie the message to */
	function describedControl(rule) {
		const field = rule.field();
		if (!field) {
			return null;
		}
		return field.matches('input, textarea, select') ? field : field.querySelector('.select2-selection, a[href], input, select');
	}

	function setError(rule, message) {
		const field = rule.field();
		if (!field) {
			return;
		}
		const control = describedControl(rule);
		const errorId = 'lfError-' + rule.key;
		field.classList.toggle('is-invalid', !!message);

		if (message) {
			errorElement(rule, true).textContent = message;
		} else {
			const el = errorElement(rule, false);
			if (el) {
				el.remove();
			}
		}
		if (control) {
			const ids = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id && id !== errorId);
			if (message) {
				ids.push(errorId);
			}
			ids.length ? control.setAttribute('aria-describedby', ids.join(' ')) : control.removeAttribute('aria-describedby');
			message ? control.setAttribute('aria-invalid', 'true') : control.removeAttribute('aria-invalid');
		}
	}

	function labelOf(rule) {
		const wrapper = wrapperOf(rule.field());
		const label = wrapper ? wrapper.querySelector('label') : null;
		return label ? label.textContent.replace('*', '').replace(/\s+/g, ' ').trim() : '';
	}
	
	function check(rule, state) {
		return rule.applies() ? rule.error(state) : null;
	}

	function renderSummary(failures) {
		let summary = document.getElementById(SUMMARY_ID);
		if (!failures.length) {
			if (summary) {
				summary.remove();
			}
			return null;
		}
		if (!summary) {
			summary = document.createElement('div');
			summary.id = SUMMARY_ID;
			summary.className = 'lf-error-summary';
			summary.setAttribute('role', 'alert');
			summary.setAttribute('tabindex', '-1');
			const firstCard = form.querySelector('.lf-card, .lf-ai-banner, .ai-assistant-wrap');
			form.insertBefore(summary, firstCard || form.firstChild);
		}
		summary.replaceChildren();

		const heading = document.createElement('p');
		heading.textContent = LF.text('error_summary');
		const list = document.createElement('ul');
		failures.forEach(({rule, message}) => {
			const item = document.createElement('li');
			const link = document.createElement('a');
			link.href = '#lfError-' + rule.key;
			const label = rule.named ? labelOf(rule) : '';
			link.textContent = label ? label + ': ' + message : message;
			link.addEventListener('click', (event) => {
				event.preventDefault();
				LF.focusTarget(wrapperOf(rule.field()) || rule.field());
			});
			item.appendChild(link);
			list.appendChild(item);
		});
		summary.append(heading, list);
		return summary;
	}

	/* Submit: runs before the other submit handlers, and stops them when something is missing */
	form.addEventListener('submit', (event) => {
		const state = LF.state();
		const failures = rules
			.map((rule) => ({rule: rule, message: check(rule, state)}))
			.filter(({message}) => message !== null);

		rules.forEach((rule) => {
			const failure = failures.find((f) => f.rule === rule);
			setError(rule, failure ? failure.message : null);
		});
		if (!failures.length) {
			renderSummary([]);
			return;
		}

		event.preventDefault();
		event.stopImmediatePropagation();
		const summary = renderSummary(failures);
		summary.scrollIntoView({block: 'center'});
		summary.focus({preventScroll: true});
	}, true);

	/* Leaving a field: only flag what was typed wrong, not fields the seller has not reached yet */
	const blurRules = ['title', 'description', 'price', 'contact_name', 'email'];
	form.addEventListener('focusout', (event) => {
		const name = event.target && event.target.name;
		const rule = rules.find((r) => blurRules.includes(r.key) && r.key === name);
		if (!rule) {
			return;
		}
		const typed = String(event.target.value || '').trim() !== '';
		const shown = !!errorElement(rule, false);
		if (typed || shown) {
			setError(rule, check(rule, LF.state()));
		}
	});

	/* Fixing a field clears its error straight away */
	LF.onChange((state) => {
		rules.forEach((rule) => {
			if (errorElement(rule, false) && check(rule, state) === null) {
				setError(rule, null);
			}
		});
		// Keep the summary in step: only the errors still showing
		if (document.getElementById(SUMMARY_ID)) {
			const remaining = rules
				.map((rule) => ({rule: rule, el: errorElement(rule, false)}))
				.filter(({el}) => el)
				.map(({rule, el}) => ({rule: rule, message: el.textContent}));
			renderSummary(remaining);
		}
	});
})(window, document);
