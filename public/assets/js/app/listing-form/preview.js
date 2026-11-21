/*
 * adniro: the live buyer preview in the listing form's side panel: the site's own listing card (as in
 * search results) and listing page (scaled down), filled from the form as the seller types. Text only
 * (textContent), so nothing typed is ever read as HTML.
 *
 * Photo: the first photo chosen in the form (single-step mode), else the listing's saved cover (edit
 * pages), else a placeholder that says where photos are added (multi-step: on the next step).
 * Details: the category's own custom fields, shown like the listing page's "Additional Details".
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	const live = document.querySelector('[data-lf-live]');
	if (!LF || !live) {
		return;
	}

	const CARD_EXCERPT_CHARS = 160; // as the listing card (grid.blade.php)
	const PAGE_DESCRIPTION_CHARS = 600;
	const PHOTO_SELECTOR = '#lfSection-photos .file-preview-thumbnails .kv-preview-thumb img';
	const FIELD_GROUP = '.mb-3, .form-group, .col-12';

	const slots = {};
	live.querySelectorAll('[data-lf-live]').forEach((el) => {
		slots[el.dataset.lfLive] = el;
	});
	const photos = [
		[live.querySelector('[data-lf-live-photo]'), live.querySelector('[data-lf-live-empty]')],
		[live.querySelector('[data-lf-live-page-photo]'), live.querySelector('[data-lf-live-page-empty]')],
	];
	const cityPill = live.querySelector('[data-lf-live-city-pill]');
	const priceCard = live.querySelector('[data-lf-live-price-card]');
	const specs = live.querySelector('[data-lf-live-specs]');
	const specsSection = live.querySelector('[data-lf-live-specs-section]');
	const tags = live.querySelector('[data-lf-live-tags]');
	const tagsSection = live.querySelector('[data-lf-live-tags-section]');
	const customFields = document.getElementById('cfContainer');
	const photosSection = document.getElementById('lfSection-photos');
	const savedCover = live.dataset.lfCover || '';
	const locale = document.documentElement.lang || undefined;

	function shorten(text, max) {
		const clean = String(text || '').trim();
		return clean.length > max ? clean.slice(0, max - 1).trimEnd() + '…' : clean;
	}

	function oneLine(text) {
		return String(text || '').replace(/\s+/g, ' ').trim();
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

	function element(tag, className, text) {
		const el = document.createElement(tag);
		if (className) {
			el.className = className;
		}
		if (text !== undefined) {
			el.textContent = text;
		}
		return el;
	}

	// ---------- the category's own fields, as the listing page's "Additional Details" ----------

	function labelOf(group) {
		const label = group.querySelector('label.form-label, legend, label:not(.form-check-label)');
		return label ? label.textContent.replace(/\*/g, '').trim() : '';
	}

	function checkedLabels(group, type) {
		return Array.from(group.querySelectorAll('input[type="' + type + '"]:checked')).map((input) => {
			const label = group.querySelector('label[for="' + input.id + '"]') || input.closest('label');
			return label ? label.textContent.trim() : '';
		}).filter(Boolean);
	}

	/** @returns {string[]} the chosen value(s); several for a multiple select or checkboxes */
	function valuesOf(group) {
		const select = group.querySelector('select');
		if (select) {
			return Array.from(select.selectedOptions).filter((option) => option.value !== '').map((option) => option.text.trim());
		}
		const radios = checkedLabels(group, 'radio');
		if (radios.length) {
			return radios;
		}
		const boxes = checkedLabels(group, 'checkbox');
		if (boxes.length) {
			return boxes;
		}
		const text = group.querySelector('input[type="text"], input[type="number"]');
		return text && text.value.trim() ? [text.value.trim()] : [];
	}

	function chosenDetails() {
		if (typeof LF.details === 'function') {
			return LF.details(); // the Photos step: the details saved in step 1 (summary.js)
		}
		if (!customFields) {
			return [];
		}
		const groups = Array.from(customFields.querySelectorAll('select, input[type="radio"], input[type="checkbox"], input[type="text"], input[type="number"]'))
			.map((input) => input.closest(FIELD_GROUP) || input.parentElement)
			.filter((group, index, all) => group && all.indexOf(group) === index);
		return groups.map((group) => ({label: labelOf(group), values: valuesOf(group), many: !!group.querySelector('input[type="checkbox"], select[multiple]')}))
			.filter((detail) => detail.values.length > 0);
	}

	/** One spec, built like front/post/show/partials/details/fields-values.blade.php */
	function specItem(detail) {
		if (detail.many) {
			const spec = element('div', 'listing-spec listing-spec-wide');
			const list = element('ul', 'listing-spec-list list-unstyled');
			detail.values.forEach((value) => {
				const item = element('li');
				item.append(element('i', 'bi bi-check2-circle'), ' ' + value);
				list.append(item);
			});
			spec.append(element('span', 'listing-spec-label', detail.label), list);
			return spec;
		}
		const spec = element('div', 'listing-spec');
		spec.append(element('span', 'listing-spec-label', detail.label), element('span', 'listing-spec-value', detail.values.join(', ')));
		return spec;
	}

	function drawDetails() {
		const details = chosenDetails();
		specs.replaceChildren(...details.map(specItem));
		specsSection.hidden = details.length === 0;
	}

	// ---------- tags (the "More options" section) ----------

	function chosenTags() {
		if (typeof LF.tags === 'function') {
			return LF.tags();
		}
		const field = LF.byName('tags[]');
		if (!field) {
			return [];
		}
		const values = field.tagName === 'SELECT'
			? Array.from(field.selectedOptions).map((option) => option.text.trim())
			: String(field.value || '').split(',').map((tag) => tag.trim());
		return values.filter(Boolean);
	}

	function drawTags() {
		const chosen = chosenTags();
		tags.replaceChildren(...chosen.map((tag) => {
			const item = element('span', 'listing-tag');
			item.append(element('i', 'bi bi-hash'), tag);
			return item;
		}));
		tagsSection.hidden = chosen.length === 0;
	}

	// ---------- photo ----------

	function drawPhoto() {
		const thumbnail = document.querySelector(PHOTO_SELECTOR);
		const url = (thumbnail && thumbnail.getAttribute('src')) || savedCover;
		photos.forEach(([img, placeholder]) => {
			if (url && img.getAttribute('src') !== url) {
				img.setAttribute('src', url);
			}
			img.hidden = !url;
			placeholder.hidden = !!url;
		});
	}

	function draw(state) {
		const price = priceText(state);
		const description = oneLine(state.description);

		// Feed card
		show('category', state.categoryName, 'preview_category');
		show('title', state.title, 'live_title_empty');
		show('excerpt', shorten(description, CARD_EXCERPT_CHARS));
		slots.excerpt.hidden = !description;
		show('price', price, 'preview_price');
		slots.price.hidden = !state.priceShown;
		show('city', state.cityName);
		cityPill.hidden = !state.cityName;

		// Listing page
		show('page-category', state.categoryName, 'preview_category');
		show('page-title', state.title, 'live_title_empty');
		show('page-price', price, 'preview_price');
		priceCard.hidden = !state.priceShown;
		slots.negotiable.hidden = !(state.negotiable && price);
		show('page-city', state.cityName, 'preview_city');
		show('description', shorten(String(state.description || '').trim(), PAGE_DESCRIPTION_CHARS), 'live_description_empty');

		drawDetails();
		drawTags();
		drawPhoto();
	}

	LF.onChange(draw);

	/* Listing card or listing page (data-lf-view buttons) */
	live.querySelectorAll('[data-lf-view]').forEach((button) => {
		button.addEventListener('click', () => {
			live.dataset.view = button.dataset.lfView;
			live.querySelectorAll('[data-lf-view]').forEach((other) => {
				other.classList.toggle('is-active', other === button);
				other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
			});
		});
	});

	/* What LF does not watch: custom fields and tags (select2 reports through jQuery) and photos */
	const redrawExtras = () => {
		drawDetails();
		drawTags();
		drawPhoto();
	};
	document.addEventListener('input', (event) => {
		if (event.target.closest('#cfContainer')) {
			drawDetails();
		}
	});
	document.addEventListener('change', (event) => {
		if (event.target.closest('#cfContainer') || event.target.name === 'tags[]') {
			redrawExtras();
		}
	});
	if (window.jQuery) {
		window.jQuery(document).on('change', '#cfContainer select, select[name="tags[]"]', redrawExtras);
	}
	if (window.MutationObserver) {
		[customFields, photosSection].filter(Boolean).forEach((target) => {
			new MutationObserver(() => window.requestAnimationFrame(redrawExtras))
				.observe(target, {childList: true, subtree: true});
		});
	}
})(window, document);
