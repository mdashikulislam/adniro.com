/*
 * adniro: the live buyer preview in the listing form's side panel: the site's own listing card, as in
 * search results, filled from the form as the seller types. Text only (textContent), so nothing typed
 * is ever read as HTML.
 *
 * Photo: the first photo on this page (single-step form, Photos step), else the listing's saved cover
 * (edit pages) or the first photo already added (back from the Photos step), else a placeholder that
 * says where photos are added.
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	const live = document.querySelector('[data-lf-live]');
	if (!LF || !live) {
		return;
	}

	const EXCERPT_CHARS = 160; // as the listing card (grid.blade.php)
	const PHOTO_SELECTOR = '#lfSection-photos .file-preview-thumbnails .kv-preview-thumb img';

	const slots = {};
	live.querySelectorAll('[data-lf-live]').forEach((el) => {
		slots[el.dataset.lfLive] = el;
	});
	const photo = live.querySelector('[data-lf-live-photo]');
	const placeholder = live.querySelector('[data-lf-live-empty]');
	const cityPill = live.querySelector('[data-lf-live-city-pill]');
	const photosSection = document.getElementById('lfSection-photos');
	const savedCover = live.dataset.lfCover || '';
	const locale = document.documentElement.lang || undefined;

	function excerpt(text) {
		const clean = String(text || '').replace(/\s+/g, ' ').trim();
		return clean.length > EXCERPT_CHARS ? clean.slice(0, EXCERPT_CHARS - 1).trimEnd() + '…' : clean;
	}

	function show(slot, value, emptyKey) {
		const el = slots[slot];
		if (!el) {
			return;
		}
		el.textContent = value || (emptyKey ? LF.text(emptyKey) : '');
		el.classList.toggle('is-empty', !value);
	}

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

	function priceText(state) {
		if (!state.priceShown || state.price === '') {
			return '';
		}
		return [LF.config.currency, formatPrice(state.price)].filter(Boolean).join(' ');
	}

	function drawPhoto() {
		const thumbnail = document.querySelector(PHOTO_SELECTOR);
		const url = (thumbnail && thumbnail.getAttribute('src')) || savedCover;
		if (url && photo.getAttribute('src') !== url) {
			photo.setAttribute('src', url);
		}
		photo.hidden = !url;
		placeholder.hidden = !!url;
	}

	function draw(state) {
		const description = excerpt(state.description);
		show('category', state.categoryName, 'preview_category');
		show('title', state.title, 'live_title_empty');
		show('excerpt', description);
		slots.excerpt.hidden = !description;
		show('price', priceText(state), 'preview_price');
		slots.price.hidden = !state.priceShown;
		show('city', state.cityName);
		cityPill.hidden = !state.cityName;
		drawPhoto();
	}

	LF.onChange(draw);

	/* Adding or removing a photo changes no form field that LF watches */
	if (photosSection && window.MutationObserver) {
		new MutationObserver(() => window.requestAnimationFrame(drawPhoto))
			.observe(photosSection, {childList: true, subtree: true});
	}
})(window, document);
