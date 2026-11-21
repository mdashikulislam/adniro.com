/*
 * adniro: the live buyer preview in the listing form's side panel: the listing as buyers will see it,
 * as a feed card or a detail view, filled from the form as the seller types. Text only (textContent),
 * so nothing typed is ever read as HTML.
 *
 * Photo: the first photo chosen in the form (single-step mode), else the listing's saved cover (edit
 * pages), else a placeholder that says where photos are added (multi-step: on the next step).
 * Details: the category's own custom fields; a field labelled "Condition" becomes the condition badge.
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	const live = document.querySelector('[data-lf-live]');
	if (!LF || !live) {
		return;
	}

	const MAX_BADGES = 3;
	const MAX_BADGE_CHARS = 24;
	const DESCRIPTION_CHARS = 280;
	const MAX_INITIALS = 2;
	// The "Condition" custom field, in the site's languages (admins may name it in any of them)
	const CONDITION_LABEL = /condition|état|etat|zustand|estado|condizion|состояни|الحالة|অবস্থা|हालत|स्थिति/i;
	const PHOTO_SELECTOR = '#lfSection-photos .file-preview-thumbnails .kv-preview-thumb img';
	const FIELD_GROUP = '.mb-3, .form-group, .col-12';

	const slots = {};
	live.querySelectorAll('[data-lf-live]').forEach((el) => {
		slots[el.dataset.lfLive] = el;
	});
	const photo = live.querySelector('[data-lf-live-photo]');
	const emptyPhoto = live.querySelector('[data-lf-live-empty]');
	const badges = live.querySelector('[data-lf-live-specs]');
	const badgesEmpty = live.querySelector('[data-lf-live-specs-empty]');
	const detailList = live.querySelector('[data-lf-live-details]');
	const verified = live.querySelector('.lf-live-verified');
	const customFields = document.getElementById('cfContainer');
	const photosSection = document.getElementById('lfSection-photos');
	const savedCover = live.dataset.lfCover || '';
	const defaultSeller = live.dataset.lfSellerDefault || '';
	const locale = document.documentElement.lang || undefined;

	function shorten(text, max) {
		const clean = String(text || '').replace(/\s+/g, ' ').trim();
		return clean.length > max ? clean.slice(0, max - 1).trimEnd() + '…' : clean;
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

	function initials(name) {
		return name.split(/\s+/).filter(Boolean).slice(0, MAX_INITIALS)
			.map((word) => word.charAt(0).toUpperCase()).join('') || '?';
	}

	// ---------- the category's own fields ----------

	function labelOf(group) {
		const label = group.querySelector('label.form-label, legend, label:not(.form-check-label)');
		return label ? label.textContent.replace(/\*/g, '').trim() : '';
	}

	function checkedLabels(group, type) {
		return Array.from(group.querySelectorAll('input[type="' + type + '"]:checked')).map((input) => {
			const label = group.querySelector('label[for="' + input.id + '"]') || input.closest('label');
			return label ? label.textContent.trim() : '';
		}).filter(Boolean).join(', ');
	}

	function valueOf(group) {
		const select = group.querySelector('select');
		if (select) {
			return Array.from(select.selectedOptions).filter((option) => option.value !== '').map((option) => option.text.trim()).join(', ');
		}
		const radios = checkedLabels(group, 'radio');
		if (radios) {
			return radios;
		}
		const boxes = checkedLabels(group, 'checkbox');
		if (boxes) {
			return boxes;
		}
		const text = group.querySelector('input[type="text"], input[type="number"]');
		return text ? text.value.trim() : '';
	}

	/** Each custom field the seller filled in, with its label */
	function chosenDetails() {
		if (!customFields) {
			return [];
		}
		const groups = Array.from(customFields.querySelectorAll('select, input[type="radio"], input[type="checkbox"], input[type="text"], input[type="number"]'))
			.map((input) => input.closest(FIELD_GROUP) || input.parentElement)
			.filter((group, index, all) => group && all.indexOf(group) === index);
		return groups.map((group) => ({label: labelOf(group), value: valueOf(group)}))
			.filter((detail) => detail.value !== '');
	}

	function listItem(tag, text) {
		const item = document.createElement(tag);
		item.textContent = text;
		return item;
	}

	function drawDetails() {
		const details = chosenDetails();
		const condition = details.find((detail) => CONDITION_LABEL.test(detail.label));
		const others = details.filter((detail) => detail !== condition);

		show('condition', condition ? shorten(condition.value, MAX_BADGE_CHARS) : '');
		slots.condition.hidden = !condition;

		badges.replaceChildren(...others.slice(0, MAX_BADGES).map((detail) => listItem('li', shorten(detail.value, MAX_BADGE_CHARS))));
		badges.hidden = others.length === 0;
		badgesEmpty.hidden = details.length > 0;

		detailList.replaceChildren(...details.flatMap((detail) => [listItem('dt', detail.label), listItem('dd', detail.value)]));
		detailList.hidden = details.length === 0;
	}

	// ---------- photo ----------

	function drawPhoto() {
		const thumbnail = document.querySelector(PHOTO_SELECTOR);
		const url = (thumbnail && thumbnail.getAttribute('src')) || savedCover;
		if (url && photo.getAttribute('src') !== url) {
			photo.setAttribute('src', url);
		}
		photo.hidden = !url;
		emptyPhoto.hidden = !!url;
	}

	// ---------- seller ----------

	function drawSeller() {
		const name = String(LF.rawValue('contact_name') || defaultSeller).trim();
		show('seller', name, 'live_seller_empty');
		show('initials', initials(name));
	}

	function draw(state) {
		const price = priceText(state);
		show('category', state.categoryName, 'preview_category');
		show('price', price, 'preview_price');
		slots.price.hidden = !state.priceShown;
		show('title', state.title, 'live_title_empty');
		show('description', shorten(state.description, DESCRIPTION_CHARS), 'live_description_empty');
		show('city', state.cityName, 'preview_city');
		slots.negotiable.hidden = !(state.negotiable && price);
		drawSeller();
		drawDetails();
		drawPhoto();
	}

	LF.onChange(draw);

	/* Feed card or detail view (data-lf-view buttons) */
	live.querySelectorAll('[data-lf-view]').forEach((button) => {
		button.addEventListener('click', () => {
			live.dataset.view = button.dataset.lfView;
			live.querySelectorAll('[data-lf-view]').forEach((other) => {
				other.classList.toggle('is-active', other === button);
				other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
			});
		});
	});

	/* What LF does not watch: the seller's name, custom fields (select2 reports through jQuery) and photos */
	document.addEventListener('input', (event) => {
		if (event.target.name === 'contact_name') {
			drawSeller();
		} else if (event.target.closest('#cfContainer')) {
			drawDetails();
		}
	});
	document.addEventListener('change', (event) => {
		if (event.target.closest('#cfContainer')) {
			drawDetails();
		}
	});
	if (window.jQuery) {
		window.jQuery(document).on('change', '#cfContainer select', drawDetails);
	}
	if (window.MutationObserver) {
		[customFields, photosSection].filter(Boolean).forEach((target) => {
			new MutationObserver(() => window.requestAnimationFrame(() => {
				drawDetails();
				drawPhoto();
			})).observe(target, {childList: true, subtree: true});
		});
	}
	if (verified) {
		verified.hidden = live.dataset.lfSellerVerified !== '1';
	}
})(window, document);
