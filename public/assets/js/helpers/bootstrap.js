/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

onDocumentReady((event) => {
	// Tooltip
	// -------
	// Enable tooltips everywhere (Default trigger: 'hover focus')
	const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
	const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl);
	});
	
	// Enable tooltips everywhere (Default trigger: 'hover focus')
	// Select only elements that have both "auto-tooltip" class and a "title" attribute
	const autoTooltipTriggerList = [].slice.call(document.querySelectorAll('.auto-tooltip[title]'));
	const autoTooltipList = autoTooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl);
	});
	
	// Enable tooltips everywhere (Default trigger: 'hover')
	const tooltipHoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltipHover"]'));
	const tooltipHoverList = tooltipHoverTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl, {
			trigger: 'hover'
		});
	});
	
	// Popover
	// -------
	/* popovers in Bootstrap 5.x requires vanilla JavaScript */
	const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
	const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl, {
			html: true
		});
	});
	
	// Modal
	// -----
	// Fix Bootstrap Modal Focus Issue on Close
	// This ensures that when any Bootstrap modal is closed, focus is removed from active elements inside it.
	// Prevents the "Blocked aria-hidden" error and improves accessibility.
	document.addEventListener("hide.bs.modal", function () {
		document.activeElement.blur(); // Remove focus from active element before hiding any modal
	});
});

/**
 * Initialize all tooltips in the DOM element (including the new one)
 *
 * Enable tooltips everywhere in the DOM element
 * Usage: initElementTooltips(domElement, {html: true});
 *        initElementTooltips(domElement, {trigger: 'hover'});
 *        initElementTooltips(domElement, {trigger: 'hover focus'});
 *
 * @param element
 * @param config
 * @param toggle
 */
function initElementTooltips(element, config = {}, toggle = 'tooltip') {
	if (!element) return;
	
	const tooltipTriggerList = [].slice.call(element.querySelectorAll(`[data-bs-toggle="${toggle}"]`));
	const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl, config);
	});
}

/**
 * Initialize all popovers in the DOM element (including the new one)
 *
 * Enable popovers everywhere
 * Usage: initElementPopovers(domElement, {html: true});
 *
 * @param element
 * @param config
 * @param toggle
 */
function initElementPopovers(element, config = {}, toggle = 'popover') {
	if (!element) return;
	
	const popoverTriggerList = [].slice.call(element.querySelectorAll(`[data-bs-toggle="${toggle}"]`));
	const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl, config);
	});
}

/**
 * Initialize Bootstrap tabs with URL fragment support
 * @param {string} tabSelector
 * @param {string} tabParentSelector
 */
function initTabsWithUrlHash(tabSelector = 'button[data-bs-toggle="tab"]', tabParentSelector = '') {
	tabParentSelector = (tabParentSelector !== null) ? tabParentSelector.trim() : '';
	tabParentSelector = (tabParentSelector !== '') ? `${tabParentSelector} ` : '';
	
	// Activate tab from URL hash
	function activateTabFromHash() {
		const hash = window.location.hash;
		if (hash) {
			const tabPaneName = `${hash}-tab-pane`;
			const tabEl = document.querySelector(`${tabParentSelector}${tabSelector}[data-bs-target="${tabPaneName}"]`);
			if (tabEl) {
				// Open (or show) the tab & its pane with tabEl.click(); or separately (verbose).
				try {
					
					// Trigger click event, that open tab and its pane (works with pills too)
					tabEl.click();
					
				} catch (e) {
					// Open the tab (not open pills' pane automatically)
					const tab = new bootstrap.Tab(tabEl);
					tab.show();
					
					// Manually open the tab's target pane (Required, when data-bs-toggle="pill" is used instead of data-bs-toggle="tab")
					const targetPaneSelector = tabEl.getAttribute('data-bs-target');
					const targetPane = document.querySelector(targetPaneSelector);
					if (targetPane) {
						// Remove active from all panes & Add active to target pane
						removeClassesFromElement(`${tabParentSelector}.tab-pane`, 'show active');
						addClassesToElement(targetPane, 'show active');
					}
				}
			}
		}
	}
	
	// Update URL hash when tab is shown
	function attachHashUpdateListeners() {
		document.querySelectorAll(tabSelector).forEach(tabEl => {
			tabEl.addEventListener('shown.bs.tab', (e) => {
				const tabPaneName = e.target.getAttribute('data-bs-target');
				if (tabPaneName) {
					const hash = tabPaneName.replace('-tab-pane', '');
					history.replaceState(null, null, hash);
				}
			});
		});
	}
	
	// Initialize
	activateTabFromHash();
	attachHashUpdateListeners();
	
	// Handle browser back/forward buttons
	window.addEventListener('hashchange', activateTabFromHash);
}
