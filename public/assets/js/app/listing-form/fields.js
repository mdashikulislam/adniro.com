/*
 * adniro: shared helpers for the redesigned listing form (listing-form/*.js).
 * Reads the form's current values in one place (plain inputs, select2, the category picker
 * and whichever rich editor the admin chose) and tells the other scripts when they change.
 * Exposes window.adniroListingForm.
 */
(function (window, document) {
	'use strict';

	const form = document.querySelector('.lf-v2 #payableForm');
	if (!form) {
		return;
	}

	const TICK_MS = 1000;
	const DEBOUNCE_MS = 150;
	const FOCUSABLE = 'input:not([type="hidden"]), textarea, select, a[href], button, [tabindex]:not([tabindex="-1"])';
	const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	let config = {};
	try {
		config = JSON.parse(form.dataset.lfConfig || '{}');
	} catch (e) {
		config = {};
	}
	const strings = config.strings || {};

	/** A translated string, with {placeholders} filled in */
	function text(key, params) {
		let out = String(strings[key] || key);
		Object.entries(params || {}).forEach(([name, value]) => {
			out = out.split('{' + name + '}').join(String(value));
		});
		return out;
	}

	/** The field with this name; checkbox helpers put a hidden "0" copy first, so prefer the real one */
	function byName(name) {
		const all = Array.from(form.querySelectorAll('[name="' + name + '"]'));
		return all.find((el) => el.type !== 'hidden') || all[0] || null;
	}

	function isVisible(el) {
		return !!el && el.getClientRects().length > 0;
	}

	function htmlToText(html) {
		const doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
		return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
	}

	/** The description as plain text, from the textarea or the rich editor on top of it */
	function descriptionText() {
		const area = byName('description');
		if (!area) {
			return '';
		}
		if (window.tinymce && area.id && window.tinymce.get(area.id)) {
			return htmlToText(window.tinymce.get(area.id).getContent());
		}
		if (window.jQuery && window.jQuery(area).data('summernote')) {
			return htmlToText(window.jQuery(area).summernote('code'));
		}
		if (window.CKEDITOR && window.CKEDITOR.instances && window.CKEDITOR.instances[area.id]) {
			return htmlToText(window.CKEDITOR.instances[area.id].getData());
		}
		return htmlToText(area.value);
	}

	/** The picked category's name; the picker shows "Name [Edit]" */
	function categoryName(categoryId) {
		const box = form.querySelector('#catsContainer');
		if (!box || categoryId <= 0) {
			return '';
		}
		return (box.textContent || '').split('[')[0].replace(/\s+/g, ' ').trim();
	}

	function value(name) {
		const el = byName(name);
		return el ? String(el.value || '').trim() : '';
	}

	/** Everything the panel, preview, counters and checks need, read fresh */
	function state() {
		const categoryId = parseInt(value('category_id'), 10) || 0;
		const city = form.querySelector('#cityId');
		const cityOption = (city && city.selectedIndex >= 0) ? city.options[city.selectedIndex] : null;
		let cityId = city ? String(city.value || '') : '';
		let cityName = (cityOption && cityId && cityId !== '0') ? cityOption.text.trim() : '';
		// Until the city picker has loaded, the saved city sits in the location modal's hidden fields
		if (!cityId || cityId === '0') {
			cityId = value('selected_city_id');
			const savedName = value('selected_city_name');
			cityName = (savedName && savedName !== '--') ? savedName : '';
		}
		const negotiable = byName('negotiable');

		return {
			categoryId: categoryId,
			categoryName: categoryName(categoryId),
			title: value('title'),
			description: descriptionText(),
			price: value('price'),
			priceShown: isVisible(form.querySelector('#priceBloc')),
			negotiable: !!(negotiable && negotiable.checked),
			cityId: (cityId && cityId !== '0') ? cityId : '',
			cityName: (cityId && cityId !== '0') ? cityName : '',
			email: value('email'),
			phone: value('phone'),
		};
	}

	/* Change notifications: form events, plus a slow tick for what fires no event
	   (select2, the category and city pickers, rich editors) */
	const listeners = [];
	let lastSnapshot = null;
	let debounceTimer = null;

	function notify(force) {
		const current = state();
		const snapshot = JSON.stringify(current);
		if (!force && snapshot === lastSnapshot) {
			return;
		}
		lastSnapshot = snapshot;
		listeners.forEach((listener) => {
			try {
				listener(current);
			} catch (e) {
				window.console && console.error('listing form:', e);
			}
		});
	}

	function onChange(listener) {
		listeners.push(listener);
		listener(state());
	}

	const scheduleNotify = () => {
		window.clearTimeout(debounceTimer);
		debounceTimer = window.setTimeout(() => notify(false), DEBOUNCE_MS);
	};
	form.addEventListener('input', scheduleNotify);
	form.addEventListener('change', scheduleNotify);
	window.setInterval(() => notify(false), TICK_MS);

	/** Scroll to a field (or the block around it) and put the keyboard there */
	function focusTarget(target) {
		const el = (typeof target === 'string') ? document.querySelector(target) : target;
		if (!el) {
			return;
		}
		const details = el.closest('details');
		if (details && !details.open) {
			details.open = true;
		}
		el.scrollIntoView({behavior: reduceMotion ? 'auto' : 'smooth', block: 'center'});

		const area = el.querySelector('textarea[name="description"]');
		if (area && window.tinymce && window.tinymce.get(area.id)) {
			window.tinymce.get(area.id).focus();
			return;
		}
		const candidates = el.matches(FOCUSABLE) ? [el] : Array.from(el.querySelectorAll(FOCUSABLE + ', .select2-selection'));
		const focusable = candidates.find(isVisible);
		if (focusable) {
			focusable.focus({preventScroll: true});
		}
	}

	window.adniroListingForm = {
		form: form,
		config: config,
		text: text,
		byName: byName,
		isVisible: isVisible,
		state: state,
		onChange: onChange,
		focusTarget: focusTarget,
	};
})(window, document);
