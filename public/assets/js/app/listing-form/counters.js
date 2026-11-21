/*
 * adniro: character counters under the title and the description (redesigned listing form).
 * Screen readers get the limit from maxlength and the error checks, so counters are hidden from them.
 */
(function (window, document) {
	'use strict';

	const LF = window.adniroListingForm;
	if (!LF) {
		return;
	}

	const NEAR_RATIO = 0.9;

	function addCounter(wrapper) {
		if (!wrapper) {
			return null;
		}
		const counter = document.createElement('span');
		counter.className = 'lf-counter';
		counter.setAttribute('aria-hidden', 'true');
		wrapper.appendChild(counter);
		return counter;
	}

	function render(counter, length, min, max) {
		if (!counter) {
			return;
		}
		counter.textContent = (length > 0 && length < min)
			? LF.text('counter_min', {min: min})
			: LF.text('counter', {count: length, max: max});
		counter.classList.toggle('is-near', length >= max * NEAR_RATIO && length <= max);
		counter.classList.toggle('is-over', length > max);
	}

	const title = LF.byName('title');
	const titleCounter = addCounter(title ? title.closest('.mb-3, [class*="col-"]') : null);
	const descriptionCounter = addCounter(LF.form.querySelector('.lf-description'));
	const cfg = LF.config;

	LF.onChange((state) => {
		render(titleCounter, state.title.length, cfg.titleMin || 0, cfg.titleMax || 150);
		render(descriptionCounter, state.description.length, cfg.descriptionMin || 0, cfg.descriptionMax || 6000);
	});
})(window, document);
