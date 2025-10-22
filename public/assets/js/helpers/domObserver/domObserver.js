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
 * SINGLE ELEMENT VERSION (domObserver-single.js)
 * - Fires callbacks for each element individually
 * - Better for simple cases with few elements
 * - Original implementation
 *
 * Note: Best Practices Implemented
 * 1. Reusability: Works with any selector or DOM node
 * 2. Flexibility: Configurable for different observation types
 * 3. Performance: Uses attributeFilter to observe only specific attributes when needed
 * 4. Cleanup: Returns observer instance for proper disconnection
 * 5. Error Handling: Validates inputs and provides clear errors
 * 6. Type Checking: Only processes ELEMENT_NODEs (skips text/comment nodes)
 * 7. Documentation: Full JSDoc comments for IDE support
 *
 * @param {string|Node} target - CSS selector or DOM node to observe
 * @param {Object} options - Configuration options
 * @param {Function|undefined} options.onAdd - Callback for added nodes (receives added node)
 * @param {Function|undefined} options.onChange - Callback for changed nodes (receives changed node)
 * @param {Function|undefined} options.onRemove - Callback for removed nodes (receives removed node)
 * @param {boolean|undefined} options.childList - Observe child nodes (default: true)
 * @param {boolean|undefined} options.subtree - Observe entire subtree (default: true)
 * @param {boolean|undefined} options.attributes - Observe attribute changes (default: false)
 * @param {array|undefined} options.attributeFilter - Specific attributes to observe
 * @param {boolean|undefined} options.characterData - Observe text content changes (default: false)
 * @returns {MutationObserver} The observer instance (can be used to disconnect later)
 */
function domObserver(target, options = {}) {
	// Default options
	const {
		onAdd = null,
		onChange = null,
		onRemove = null,
		childList = true,
		subtree = true,
		attributes = false,
		attributeFilter = undefined,
		characterData = false
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
	
	// Callback function to execute when mutations are observed
	const callback = (mutationsList, observer) => {
		for (const mutation of mutationsList) {
			// Handle added nodes
			if (onAdd && mutation.addedNodes && mutation.addedNodes.length > 0) {
				for (const node of mutation.addedNodes) {
					if (node.nodeType === Node.ELEMENT_NODE) {
						onAdd(node);
					}
				}
			}
			
			// Handle removed nodes
			if (onRemove && mutation.removedNodes && mutation.removedNodes.length > 0) {
				for (const node of mutation.removedNodes) {
					if (node.nodeType === Node.ELEMENT_NODE) {
						onRemove(node);
					}
				}
			}
			
			// Handle attribute changes
			if (onChange && mutation.type === 'attributes') {
				onChange(mutation.target);
			}
			
			// Handle character data changes (text content)
			if (onChange && mutation.type === 'characterData') {
				onChange(mutation.target.parentNode); // Return the parent element
			}
		}
	};
	
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
 1. Basic Usage - Observe Added Elements
 
 // Observe when new elements are added to the body
 const observer = domObserver('body', {
	 onAdd: (element) => {
	    console.log('Element added:', element);
	 }
 });
 
 // Later, if you need to stop observing:
 // observer.disconnect();
 
 2. Advanced Usage - Observe Changes and Removals
 
 // Observe a specific container for all changes
 const containerObserver = domObserver('#my-container', {
	 onAdd: (element) => {
		 console.log('Added:', element);
		 // Initialize any dynamic components in the new element
	 },
	 onChange: (element) => {
		 console.log('Changed:', element);
		 // Handle attribute or content changes
	 },
	 onRemove: (element) => {
		 console.log('Removed:', element);
		 // Clean up any resources tied to the removed element
	 },
	 attributes: true,
	 attributeFilter: ['class', 'data-status'],
	 characterData: true
 });
 
 3. Observing Specific Attributes
 
 // Only observe class changes on a specific element
 const element = document.getElementById('my-element');
 const attrObserver = domObserver(element, {
	 onChange: (element) => {
	    console.log('Class changed:', element.className);
	 },
	 attributes: true,
	 attributeFilter: ['class'],
	 childList: false,
	 subtree: false
 });
 */
