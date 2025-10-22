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

/**
 * Observes DOM changes and executes callbacks when matching elements are added or changed
 *
 * BATCH PROCESSING VERSION (domObserver-batch.js)
 * - Processes additions/removals in arrays
 * - Includes debouncing option
 * - Better for performance with bulk DOM changes
 *
 * @param {string|Node} target - CSS selector or DOM node to observe
 * @param {Object} options - Configuration options
 * @param {Function|undefined} options.onAdd - Callback for added nodes (receives array of added nodes)
 * @param {Function|undefined} options.onChange - Callback for changed nodes (receives array of changed nodes)
 * @param {Function|undefined} options.onRemove - Callback for removed nodes (receives array of removed nodes)
 * @param {boolean|undefined} options.childList - Observe child nodes (default: true)
 * @param {boolean|undefined} options.subtree - Observe entire subtree (default: true)
 * @param {boolean|undefined} options.attributes - Observe attribute changes (default: false)
 * @param {array|undefined} options.attributeFilter - Specific attributes to observe
 * @param {boolean|undefined} options.characterData - Observe text content changes (default: false)
 * @param {number|undefined} options.debounce - Debounce time in ms for rapid changes (default: 0)
 * @returns {MutationObserver} The observer instance (can be used to disconnect later)
 */
function domObserverBatch(target, options = {}) {
	// Default options
	const {
		onAdd = null,
		onChange = null,
		onRemove = null,
		childList = true,
		subtree = true,
		attributes = false,
		attributeFilter = undefined,
		characterData = false,
		debounce = 0
	} = options;
	
	// Validate callbacks
	if (!onAdd && !onChange && !onRemove) {
		throw new Error('At least one callback (onAdd, onChange, or onRemove) must be provided');
	}
	
	// Get the target node to observe
	const targetNode = typeof target === 'string'
		? document.querySelector(target)
		: target;
	
	if (!targetNode) {
		throw new Error(`Target element not found: ${target}`);
	}
	
	// Process mutations in batches
	const processMutations = (mutationsList) => {
		const addedElements = [];
		const removedElements = [];
		const changedElements = new Set();
		
		for (const mutation of mutationsList) {
			// Handle added nodes
			if (onAdd && mutation.addedNodes && mutation.addedNodes.length > 0) {
				Array.from(mutation.addedNodes).forEach(node => {
					if (node.nodeType === Node.ELEMENT_NODE) {
						addedElements.push(node);
					}
				});
			}
			
			// Handle removed nodes
			if (onRemove && mutation.removedNodes && mutation.removedNodes.length > 0) {
				Array.from(mutation.removedNodes).forEach(node => {
					if (node.nodeType === Node.ELEMENT_NODE) {
						removedElements.push(node);
					}
				});
			}
			
			// Handle attribute changes
			if (onChange && mutation.type === 'attributes') {
				changedElements.add(mutation.target);
			}
			
			// Handle character data changes (text content)
			if (onChange && mutation.type === 'characterData') {
				changedElements.add(mutation.target.parentNode);
			}
		}
		
		// Execute callbacks with batches of elements
		if (onAdd && addedElements.length > 0) onAdd(addedElements);
		if (onRemove && removedElements.length > 0) onRemove(removedElements);
		if (onChange && changedElements.size > 0) onChange(Array.from(changedElements));
	};
	
	// Debounce wrapper for callback
	let debounceTimer;
	const callback = (debounce > 0)
		? (mutationsList) => {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(() => {
				processMutations(mutationsList);
			}, debounce);
		}
		: processMutations;
	
	// Create an observer instance linked to the callback function
	const observer = new MutationObserver(callback);
	
	// Start observing the target node for configured mutations
	observer.observe(targetNode, {
		childList,
		subtree,
		attributes,
		attributeFilter,
		characterData
	});
	
	return observer;
}

/*
 Usage Examples
 --------------
 Example 1: Basic Element Addition/Removal Tracking
 ==================================================
 
 // Track all div additions/removals in the document
 const observer = domObserverBatch(document.body, {
	 onAdd: (addedElements) => {
		 console.log('Added elements:', addedElements);
		 addedElements.forEach(el => {
		 if (el.matches('.important-widget')) {
		    initializeWidget(el);
		 }
	 });
	 },
	 onRemove: (removedElements) => {
		 console.log('Removed elements:', removedElements);
		 removedElements.forEach(el => {
			 if (el.matches('.important-widget')) {
			    cleanupWidgetResources(el);
			 }
		 });
	 }
 });
 
 // Later when you want to stop observing
 // observer.disconnect();
 
 --------------------------------
 Example 2: Shopping Cart Updates
 ================================
 
 // Track items being added/removed from a shopping cart
 const cartObserver = domObserverBatch('#cart-items', {
	 onAdd: (addedElements) => {
		 addedElements.forEach(item => {
			 if (item.matches('.cart-item')) {
		        animateCartAddition(item);
		        updateCartTotal();
			 }
		 });
	 },
	 onRemove: (removedElements) => {
		 removedElements.forEach(item => {
			 if (item.matches('.cart-item')) {
			    updateCartTotal();
			 }
		 });
	 },
	 attributes: true,
	 attributeFilter: ['data-quantity'],
	 onChange: (changedElements) => {
		 changedElements.forEach(el => {
			 if (el.matches('.cart-item')) {
			    updateCartTotal();
			 }
		 });
	 }
 });
 */
