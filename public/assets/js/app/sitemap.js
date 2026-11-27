/*
 * adniro: the search of the sitemap page (front/sitemap/index.blade.php).
 *
 * Filters the page as one types: every link is a [data-sm-item]; links live in groups ([data-sm-group]: a
 * category card, the grid of cities, the blog panel) and groups in sections ([data-sm-section]).
 * A category whose own name matches keeps all of its sub-categories. What matches nothing is hidden, and when
 * nothing at all matches, the "nothing matches" block shows. Without this script the page is simply complete.
 */
(function () {
	'use strict';

	var page = document.getElementById('sitemapPage');
	var form = page ? page.querySelector('[data-sm-search]') : null;
	if (!form) {
		return;
	}

	var input = form.querySelector('[data-sm-search-input]');
	var status = page.querySelector('[data-sm-search-status]');
	var empty = page.querySelector('[data-sm-empty]');
	var emptyTitle = page.querySelector('[data-sm-empty-title]');
	var results = document.getElementById('sitemapResults');
	var clearButtons = page.querySelectorAll('[data-sm-search-clear]');
	var fieldClear = form.querySelector('[data-sm-search-clear]');
	var sections = Array.prototype.slice.call(page.querySelectorAll('[data-sm-section]'));
	var DEBOUNCE_MS = 90;

	/* Lower case, without accents, so "cafe" finds "Café" */
	function plain(text) {
		var value = String(text || '').toLowerCase();
		return value.normalize ? value.normalize('NFD').replace(/[̀-ͯ]/g, '') : value;
	}

	/* What a link hides with it: its grid cell or list item when it has one, else itself */
	function holder(item) {
		return item.closest('[data-sm-holder]') || item.closest('li') || item;
	}

	var groups = Array.prototype.slice.call(page.querySelectorAll('[data-sm-group]')).map(function (el) {
		var items = Array.prototype.slice.call(el.querySelectorAll('[data-sm-item]')).map(function (item) {
			return {el: item, text: plain(item.textContent), isHeading: item.hasAttribute('data-sm-heading')};
		});
		return {el: el, items: items};
	});

	function show(el, visible) {
		if (visible) {
			el.removeAttribute('hidden');
		} else {
			el.setAttribute('hidden', '');
		}
	}

	function filter() {
		var raw = input.value.trim();
		var query = plain(raw);
		var found = 0;

		groups.forEach(function (group) {
			var headingMatches = query !== '' && group.items.some(function (item) {
				return item.isHeading && item.text.indexOf(query) !== -1;
			});
			var visibleInGroup = 0;

			group.items.forEach(function (item) {
				var matches = query === '' || headingMatches || item.text.indexOf(query) !== -1;
				if (!item.isHeading) {
					show(holder(item.el), matches);
				}
				if (matches) {
					visibleInGroup++;
				}
			});

			show(group.el, visibleInGroup > 0);
			found += visibleInGroup;
		});

		var anySection = false;
		sections.forEach(function (section) {
			var hasVisible = Array.prototype.some.call(section.querySelectorAll('[data-sm-group]'), function (el) {
				return !el.hasAttribute('hidden');
			});
			show(section, hasVisible);
			anySection = anySection || hasVisible;
		});

		// While searching, the cards are all one column wide: what is left of a list no longer needs the room
		page.classList.toggle('is-filtering', query !== '');
		show(fieldClear, raw !== '');
		show(empty, query !== '' && !anySection);

		if (query === '') {
			status.textContent = '';
		} else if (found > 0) {
			status.textContent = form.getAttribute('data-found').replace(':count', String(found));
		} else {
			status.textContent = '';
			emptyTitle.textContent = form.getAttribute('data-none').replace(':query', raw);
		}
	}

	var timer = null;
	input.addEventListener('input', function () {
		clearTimeout(timer);
		timer = setTimeout(filter, DEBOUNCE_MS);
	});

	/* Find: straight to what was found */
	form.addEventListener('submit', function (event) {
		event.preventDefault();
		clearTimeout(timer);
		filter();
		if (input.value.trim() !== '' && results) {
			var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			results.scrollIntoView({behavior: reduce ? 'auto' : 'smooth', block: 'start'});
		}
	});

	Array.prototype.forEach.call(clearButtons, function (button) {
		button.addEventListener('click', function () {
			input.value = '';
			filter();
			input.focus();
		});
	});

	input.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && input.value !== '') {
			input.value = '';
			filter();
		}
	});

	form.removeAttribute('hidden');
	if (input.value.trim() !== '') {
		filter(); // the browser kept the text over a reload
	}
})();
