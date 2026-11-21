/*
 * adniro: the checklist in the listing form's side panel (and its count in the phone bar),
 * plus the dismissible AI banner for guests.
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

	/** When each checklist item counts as done; null hides the item */
	const checks = {
		category: (s) => s.categoryId > 0,
		title: (s) => s.title.length >= (cfg.titleMin || 1),
		description: (s) => s.description.length >= (cfg.descriptionMin || 1),
		price: (s) => s.priceShown ? s.price !== '' : null,
		city: (s) => !!s.cityId,
		contact: (s) => s.email !== '' || s.phone !== '',
	};

	const items = Array.from(document.querySelectorAll('[data-lf-check]'));
	const progress = document.querySelector('[data-lf-progress]');
	const progressBar = progress ? progress.querySelector('.progress-bar') : null;
	const progressText = document.querySelector('[data-lf-progress-text]');
	const chipCounts = document.querySelectorAll('[data-lf-progress-count]');
	const checklist = document.querySelector('.lf-checklist');

	function update(state) {
		let done = 0;
		let total = 0;
		items.forEach((item) => {
			const rule = checks[item.dataset.lfCheck];
			const result = rule ? rule(state) : null;
			item.hidden = (result === null);
			if (result === null) {
				return;
			}
			total++;
			done += result ? 1 : 0;
			item.classList.toggle('is-done', result);
			const stateLabel = item.querySelector('.lf-check-state');
			if (stateLabel) {
				stateLabel.textContent = ' (' + LF.text(result ? 'check_done' : 'check_todo') + ')';
			}
		});

		const ready = total > 0 && done === total;
		if (progress) {
			progress.setAttribute('aria-valuemax', String(total));
			progress.setAttribute('aria-valuenow', String(done));
		}
		if (progressBar) {
			progressBar.style.width = (total ? Math.round(done / total * 100) : 0) + '%';
		}
		if (progressText) {
			progressText.textContent = ready ? LF.text('checklist_ready') : LF.text('checklist_progress', {done: done, total: total});
		}
		chipCounts.forEach((chip) => {
			chip.textContent = done + '/' + total;
		});
		if (checklist) {
			checklist.classList.toggle('is-ready', ready);
		}
	}

	LF.onChange(update);

	/* A checklist item takes you to its field; on phones the sheet closes first */
	document.addEventListener('click', (event) => {
		const link = event.target.closest('.lf-check-link');
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
