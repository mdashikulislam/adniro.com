/*
 * adniro: the price hint under the listing form's Price: what similar listings on the site ask
 * (statistics from the server, no AI). Shown as information; it never fills in the price.
 */
(function (window, document) {
	'use strict';
	
	const LF = window.adniroListingForm;
	const priceBloc = document.getElementById('priceBloc');
	if (!LF || !LF.config.priceHintUrl || !priceBloc) {
		return;
	}
	
	const DEBOUNCE_MS = 300;
	const cache = {};
	let lastKey = null;
	let timer = null;
	
	// After #priceBloc, not inside it: d.modal.category.js rewrites parts of that block
	const hint = document.createElement('p');
	hint.className = 'col-12 lf-price-hint';
	hint.setAttribute('aria-live', 'polite');
	hint.hidden = true;
	const icon = document.createElement('i');
	icon.className = 'bi bi-graph-up';
	icon.setAttribute('aria-hidden', 'true');
	const text = document.createElement('span');
	hint.append(icon, text);
	priceBloc.after(hint);
	
	function show(value, priceShown) {
		text.textContent = value || '';
		hint.hidden = !value || !priceShown;
	}
	
	function load(key, state) {
		if (Object.prototype.hasOwnProperty.call(cache, key)) {
			show(cache[key], state.priceShown);
			return;
		}
		const params = new URLSearchParams({category_id: String(state.categoryId)});
		if (state.cityId) {
			params.set('city_id', state.cityId);
		}
		if (LF.config.postId) {
			params.set('post_id', String(LF.config.postId));
		}
		window.fetch(LF.config.priceHintUrl + '?' + params.toString(), {
			credentials: 'same-origin',
			headers: {'Accept': 'application/json'},
		})
			.then((response) => {
				// A throttled or failed request is tried again next time, not remembered
				if (!response.ok) {
					throw new Error('price hint ' + response.status);
				}
				return response.json();
			})
			.then((json) => {
				cache[key] = (json.ok && json.hint) ? json.hint.text : '';
				// The seller may have moved on to another category meanwhile
				if (key === lastKey) {
					show(cache[key], LF.state().priceShown);
				}
			})
			.catch(() => {
				/* No hint is fine: it is only information */
			});
	}
	
	LF.onChange((state) => {
		if (state.categoryId <= 0) {
			lastKey = null;
			show('', false);
			return;
		}
		const key = state.categoryId + ':' + (state.cityId || '');
		if (key === lastKey) {
			hint.hidden = !text.textContent || !state.priceShown;
			return;
		}
		lastKey = key;
		window.clearTimeout(timer);
		timer = window.setTimeout(() => load(key, state), DEBOUNCE_MS);
	});
})(window, document);
