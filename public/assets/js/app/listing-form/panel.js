/*
 * adniro: the listing quality card in the listing form's side panel (a ring with the score, a tier badge,
 * and what each item is worth, scored by strength-score.js), the score in the phone bar, and the
 * dismissible AI banner for guests.
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	if (!LF) {
		return;
	}

	const BANNER_KEY = 'adniro.listingForm.aiBannerHidden';
	const panel = document.getElementById('lfPanel');
	const cfg = LF.config;

	const Strength = window.adniroListingStrength;

	const strength = document.querySelector('[data-lf-strength]');
	const items = Array.from(document.querySelectorAll('[data-lf-check]'));
	const chipCounts = document.querySelectorAll('[data-lf-progress-count]');
	const photosSection = document.getElementById('lfSection-photos');
	const PHOTO_THUMB = '.file-preview-thumbnails .kv-preview-thumb';

	/** A hint from strength-score.js: a translation key with values, or a literal text (a chosen name) */
	function hintText(hint) {
		if (!hint) {
			return '';
		}
		return hint.text !== undefined ? hint.text : LF.text(hint.key, hint.params);
	}

	/** Row labels carry the good-length targets, e.g. "Clear title (15–80 characters)" */
	function labelText(key, limits) {
		return LF.text('check_' + key, {min: {title: limits.titleMin, photos: limits.photosMin}[key] || limits.descriptionMin, max: limits.titleMax});
	}

	function drawItem(element, entry, limits) {
		element.hidden = !entry;
		if (!entry) {
			return;
		}
		const link = element.querySelector('.lf-check-link');
		element.classList.toggle('is-done', entry.done);
		element.querySelector('[data-lf-label]').textContent = labelText(entry.key, limits);
		element.querySelector('[data-lf-worth]').textContent = '+' + entry.worth + '%';
		link.title = hintText(entry.hint);
		element.querySelector('.lf-check-state').textContent = ' (' + LF.text(entry.done ? 'check_done' : 'check_todo') + ')';
	}

	function drawScore(result) {
		const tierLabel = LF.text('strength_' + result.tier);
		strength.dataset.tier = result.tier;
		strength.querySelector('[data-lf-score]').textContent = String(result.score);
		strength.querySelector('[data-lf-ring]').style.strokeDasharray = result.score + ' 100';
		strength.querySelector('[data-lf-tier]').textContent = tierLabel;
		strength.querySelector('[data-lf-tier-text]').textContent = LF.text('strength_' + result.tier + '_text');
		const meter = strength.querySelector('[data-lf-meter]');
		meter.setAttribute('aria-valuenow', String(result.score));
		meter.setAttribute('aria-valuetext', result.score + '%, ' + tierLabel);
	}

	/** Photos added so far: live where the photos are on this page, else the ones already saved (data-lf-photo-count) */
	function photoCount() {
		return photosSection
			? photosSection.querySelectorAll(PHOTO_THUMB).length
			: Number(panel && panel.dataset.lfPhotoCount) || 0;
	}

	function update(state) {
		if (!strength || !Strength) {
			return;
		}
		const scored = items.some((element) => element.dataset.lfCheck === 'photos')
			? Object.assign({}, state, {photoCount: photoCount()})
			: state;
		const result = Strength.score(scored, cfg);
		items.forEach((element) => drawItem(element, result.items.find((entry) => entry.key === element.dataset.lfCheck), result.limits));
		drawScore(result);
		chipCounts.forEach((chip) => {
			chip.textContent = result.score + '%';
		});
	}

	LF.onChange(update);

	/* Large screens: the panel sticks below the header when it fits. When it is taller than the screen,
	   it scrolls with the page and sticks once its end is in view, so nothing needs an inner scrollbar. */
	const HEADER_OFFSET = 92; // px, the CSS inset-block-start (5.75rem)
	const BOTTOM_GAP = 16;
	const largeScreen = window.matchMedia ? window.matchMedia('(min-width: 992px)') : null;
	function placePanel() {
		if (!panel) {
			return;
		}
		panel.style.top = (largeScreen && largeScreen.matches)
			? Math.min(HEADER_OFFSET, window.innerHeight - panel.offsetHeight - BOTTOM_GAP) + 'px'
			: '';
	}
	window.addEventListener('resize', placePanel);
	if (window.ResizeObserver && panel) {
		new ResizeObserver(placePanel).observe(panel);
	}
	placePanel();

	/* Adding or removing a photo changes no form field that LF watches */
	if (photosSection && window.MutationObserver) {
		new MutationObserver(() => window.requestAnimationFrame(() => update(LF.state())))
			.observe(photosSection, {childList: true, subtree: true});
	}

	/* An item takes you to its field; on phones the sheet closes first */
	document.addEventListener('click', (event) => {
		const link = event.target.closest('#lfPanel a[data-lf-target]');
		if (!link) {
			return;
		}
		event.preventDefault();
		const target = link.dataset.lfTarget;
		const sheet = (panel && window.bootstrap && panel.classList.contains('show'))
			? window.bootstrap.Offcanvas.getInstance(panel)
			: null;
		if (sheet) {
			panel.addEventListener('hidden.bs.offcanvas', () => LF.focusTarget(target), {once: true});
			sheet.hide();
		} else {
			LF.focusTarget(target);
		}
	});

	/* Guest AI banner: hidden for good once dismissed (browser storage may be off) */
	const banner = document.querySelector('[data-lf-ai-banner]');
	if (banner) {
		try {
			if (window.localStorage.getItem(BANNER_KEY) === '1') {
				banner.remove();
			}
		} catch (e) {
			/* storage unavailable: keep showing it */
		}
		const hide = banner.querySelector('[data-lf-ai-banner-hide]');
		if (hide) {
			hide.addEventListener('click', () => {
				try {
					window.localStorage.setItem(BANNER_KEY, '1');
				} catch (e) {
					/* storage unavailable: hide for this visit only */
				}
				banner.remove();
			});
		}
	}
})(window, document);
