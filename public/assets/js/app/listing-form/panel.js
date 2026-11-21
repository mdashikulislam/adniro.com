/*
 * adniro: the listing strength card in the listing form's side panel (score, tier, next step and live
 * hints, scored by strength-score.js), its score in the phone bar, and the dismissible AI banner for guests.
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
	const TIERS = ['weak', 'fair', 'strong', 'excellent'];

	const strength = document.querySelector('[data-lf-strength]');
	const items = Array.from(document.querySelectorAll('[data-lf-check]'));
	const chipCounts = document.querySelectorAll('[data-lf-progress-count]');

	/** A hint from strength-score.js: a translation key with values, or a literal text (a chosen name) */
	function hintText(hint) {
		if (!hint) {
			return '';
		}
		return hint.text !== undefined ? hint.text : LF.text(hint.key, hint.params);
	}

	function drawItem(element, entry) {
		element.hidden = !entry;
		if (!entry) {
			return;
		}
		element.classList.toggle('is-done', entry.done);
		element.classList.toggle('is-warn', !!entry.warn);
		element.querySelector('[data-lf-hint]').textContent = hintText(entry.hint);
		element.querySelector('[data-lf-points]').textContent = entry.done ? '' : '+' + entry.remaining;
		const bar = element.querySelector('[data-lf-bar]');
		if (bar) {
			bar.style.width = Math.round((entry.bar || 0) * 100) + '%';
		}
		element.querySelector('.lf-check-state').textContent = ' (' + LF.text(entry.done ? 'check_done' : 'check_todo') + ')';
	}

	function drawMeter(result) {
		const tierLabel = LF.text('strength_' + result.tier);
		const reached = TIERS.indexOf(result.tier);
		strength.dataset.tier = result.tier;
		strength.querySelector('[data-lf-score]').textContent = String(result.score);
		strength.querySelector('[data-lf-tier]').textContent = tierLabel;
		strength.querySelector('[data-lf-tier-text]').textContent = LF.text('strength_' + result.tier + '_text');
		strength.querySelectorAll('[data-lf-step]').forEach((step) => {
			step.classList.toggle('is-on', TIERS.indexOf(step.dataset.lfStep) <= reached);
		});
		const meter = strength.querySelector('[data-lf-meter]');
		meter.setAttribute('aria-valuenow', String(result.score));
		meter.setAttribute('aria-valuetext', result.score + '/100, ' + tierLabel);
	}

	/** The one step that adds the most points, with the field it leads to */
	function drawNext(next, limits) {
		const link = strength.querySelector('[data-lf-next]');
		link.hidden = !next;
		if (!next) {
			return;
		}
		const item = items.find((element) => element.dataset.lfCheck === next.key);
		link.dataset.lfTarget = item ? item.querySelector('[data-lf-target]').dataset.lfTarget : '';
		strength.querySelector('[data-lf-next-text]').textContent = LF.text('next_' + next.key, {min: next.key === 'title' ? limits.titleMin : limits.descriptionMin});
		strength.querySelector('[data-lf-next-points]').textContent = '+' + next.remaining;
	}

	function update(state) {
		if (!strength || !Strength) {
			return;
		}
		const result = Strength.score(state, cfg);
		items.forEach((element) => drawItem(element, result.items.find((entry) => entry.key === element.dataset.lfCheck)));
		drawMeter(result);
		drawNext(result.next, result.limits);
		chipCounts.forEach((chip) => {
			chip.textContent = String(result.score);
		});
	}

	LF.onChange(update);

	/* An item, or the next step, takes you to its field; on phones the sheet closes first */
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
