/*
 * adniro: the live preview card in the listing form's side panel.
 * Text only (textContent), so nothing typed is ever read as HTML.
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	if (!LF) {
		return;
	}

	const slots = {};
	document.querySelectorAll('[data-lf-preview]').forEach((el) => {
		slots[el.dataset.lfPreview] = el;
	});
	if (!Object.keys(slots).length) {
		return;
	}

	const locale = document.documentElement.lang || undefined;

	function formatPrice(raw) {
		const number = Number(raw);
		if (!Number.isFinite(number)) {
			return raw;
		}
		try {
			return new Intl.NumberFormat(locale, {maximumFractionDigits: 2}).format(number);
		} catch (e) {
			return String(number);
		}
	}

	function show(slot, value, emptyKey) {
		const el = slots[slot];
		if (!el) {
			return;
		}
		el.textContent = value || LF.text(emptyKey);
		el.classList.toggle('is-empty', !value);
	}

	LF.onChange((state) => {
		let price = '';
		if (state.priceShown && state.price !== '') {
			price = [LF.config.currency, formatPrice(state.price)].filter(Boolean).join(' ');
			if (state.negotiable) {
				price += ' · ' + LF.text('preview_negotiable');
			}
		}
		show('category', state.categoryName, 'preview_category');
		show('title', state.title, 'preview_title');
		show('price', price, 'preview_price');
		show('city', state.cityName, 'preview_city');

		if (slots.price) {
			slots.price.hidden = !state.priceShown;
		}
	});
})(window, document);
