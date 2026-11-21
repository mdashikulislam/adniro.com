/*
 * adniro: window.adniroListingForm for the multi-step Photos step, where the details fields are not on
 * the page. It gives the panel scripts (panel.js, preview.js) the details saved in step 1, from the
 * panel's data-lf-panel-state (App\Services\ListingForm\PanelState), in the shape fields.js gives on the
 * details step. Items about step-1 fields lead back to step 1; the photos item leads to the photos.
 */
(function (window, document) {
	'use strict';

	const panel = document.querySelector('[data-lf-panel-state]');
	if (!panel || window.adniroListingForm) {
		return;
	}

	let saved = {};
	try {
		saved = JSON.parse(panel.dataset.lfPanelState || '{}');
	} catch (e) {
		saved = {};
	}
	const config = saved.config || {};
	const strings = config.strings || {};
	const PHOTOS = '#lfSection-photos';

	/** A translated string, with {placeholders} filled in */
	function text(key, params) {
		let out = String(strings[key] || key);
		Object.entries(params || {}).forEach(([name, value]) => {
			out = out.split('{' + name + '}').join(String(value));
		});
		return out;
	}

	function state() {
		return Object.assign({}, saved.state || {});
	}

	function focusTarget(target) {
		if (String(target).indexOf(PHOTOS) === 0) {
			const photos = document.querySelector(PHOTOS);
			if (photos) {
				photos.scrollIntoView({behavior: 'smooth', block: 'start'});
				const browse = photos.querySelector('input[type="file"]');
				if (browse) {
					browse.focus({preventScroll: true});
				}
			}
			return;
		}
		if (saved.backUrl) {
			window.location.href = saved.backUrl;
		}
	}

	window.adniroListingForm = {
		config: config,
		text: text,
		state: state,
		onChange: (listener) => listener(state()),
		rawValue: () => '',
		byName: () => null,
		focusTarget: focusTarget,
	};
})(window, document);
