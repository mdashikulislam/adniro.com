/*
 * adniro: the listing strength score of the redesigned listing form, as a pure function of the
 * form's state (no page access), so panel.js can draw it and tests/js can check it.
 *
 * Each item is worth points (the description most, contact details least); text fields earn
 * part of their points as they grow toward a good length. "Excellent" means every item is done.
 */
(function (root) {
	'use strict';

	const WEIGHTS = {category: 15, title: 20, description: 25, price: 15, city: 15, contact: 10};
	const TITLE_GOOD_MIN = 15;
	const TITLE_GOOD_MAX = 80;
	const TITLE_SHORT_SHARE = 0.5; // a too-short title earns up to half its points
	const TITLE_LONG_SHARE = 0.7;  // a too-long title still earns most of them
	const DESCRIPTION_GOOD = 200;
	const TIER_MINIMUMS = [['strong', 75], ['fair', 40], ['weak', 0]];

	/**
	 * @typedef {{key?: string, params?: Object, text?: string}} Hint  a translation key or a literal text
	 * @typedef {{ratio: number, hint: ?Hint, bar?: number, warn?: boolean}} Rating
	 */

	function charCount(text) {
		return String(text || '').trim().length;
	}

	/** Good-length targets: the built-in ones, unless the site's own minimums are stricter */
	function limitsFrom(config) {
		const titleMin = Math.max(TITLE_GOOD_MIN, Number(config.titleMin) || 0);
		return {
			titleMin: titleMin,
			titleMax: Math.max(titleMin, Math.min(TITLE_GOOD_MAX, Number(config.titleMax) || TITLE_GOOD_MAX)),
			descriptionMin: Math.max(DESCRIPTION_GOOD, Number(config.descriptionMin) || 0),
		};
	}

	/** @returns {Rating} */
	function rateTitle(title, limits) {
		const count = charCount(title);
		if (count === 0) {
			return {ratio: 0, hint: {key: 'hint_title_empty'}, bar: 0};
		}
		if (count > limits.titleMax) {
			return {ratio: TITLE_LONG_SHARE, hint: {key: 'hint_title_long', params: {max: limits.titleMax}}, bar: 1, warn: true};
		}
		if (count < limits.titleMin) {
			return {ratio: TITLE_SHORT_SHARE * count / limits.titleMin, hint: {key: 'hint_title_short', params: {count: limits.titleMin - count}}, bar: count / limits.titleMin};
		}
		return {ratio: 1, hint: {key: 'hint_title_good'}, bar: 1};
	}

	/** @returns {Rating} */
	function rateDescription(description, limits) {
		const count = charCount(description);
		if (count === 0) {
			return {ratio: 0, hint: {key: 'hint_description_empty'}, bar: 0};
		}
		if (count < limits.descriptionMin) {
			const ratio = count / limits.descriptionMin;
			return {ratio: ratio, hint: {key: 'hint_description_short', params: {count: limits.descriptionMin - count}}, bar: ratio};
		}
		return {ratio: 1, hint: {key: 'hint_description_good'}, bar: 1};
	}

	/** @returns {Rating} */
	function chosen(isChosen, name, todoKey) {
		return isChosen ? {ratio: 1, hint: name ? {text: name} : null} : {ratio: 0, hint: {key: todoKey}};
	}

	/** @returns {Object<string, ?Rating>} null when the item does not apply to this form */
	function rateAll(state, limits) {
		const priceSet = state.price !== '';
		const hasContact = state.email !== '' || state.phone !== '';
		return {
			category: chosen(state.categoryId > 0, state.categoryName, 'hint_category_todo'),
			title: rateTitle(state.title, limits),
			description: rateDescription(state.description, limits),
			price: state.priceShown
				? {ratio: priceSet ? 1 : 0, hint: priceSet ? (state.negotiable ? {key: 'hint_price_negotiable'} : null) : {key: 'hint_price_todo'}}
				: null,
			city: chosen(!!state.cityId, state.cityName, 'hint_city_todo'),
			contact: {ratio: hasContact ? 1 : 0, hint: {key: hasContact ? 'hint_contact_done' : 'hint_contact_todo'}},
		};
	}

	/**
	 * @param {Object} state  window.adniroListingForm.state()
	 * @param {{titleMin?: number, titleMax?: number, descriptionMin?: number}} config
	 * @returns {{score: number, tier: string, items: Array, next: ?Object, limits: Object}}
	 */
	function score(state, config) {
		const limits = limitsFrom(config || {});
		const ratings = rateAll(state, limits);
		const keys = Object.keys(WEIGHTS).filter((key) => ratings[key] !== null);
		const totalWeight = keys.reduce((sum, key) => sum + WEIGHTS[key], 0);

		const items = keys.map((key) => {
			const worth = WEIGHTS[key] * 100 / totalWeight;
			const rating = ratings[key];
			return Object.assign({}, rating, {
				key: key,
				worth: Math.round(worth),
				earned: worth * rating.ratio,
				remaining: Math.round(worth * (1 - rating.ratio)),
				done: rating.ratio >= 1,
			});
		});

		const total = Math.round(items.reduce((sum, entry) => sum + entry.earned, 0));
		const allDone = items.every((entry) => entry.done);
		const tier = allDone ? 'excellent' : TIER_MINIMUMS.find(([, minimum]) => total >= minimum)[0];
		const next = items.filter((entry) => !entry.done)
			.reduce((best, entry) => (!best || entry.remaining > best.remaining) ? entry : best, null);

		return {score: allDone ? 100 : Math.min(total, 99), tier: tier, items: items, next: next, limits: limits};
	}

	const api = {score: score, WEIGHTS: WEIGHTS};
	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	} else {
		root.adniroListingStrength = api;
	}
})(typeof window !== 'undefined' ? window : globalThis);
