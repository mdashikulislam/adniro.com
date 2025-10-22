<script>
	// Get all the form fields selector as comma separated string
	const allFieldSelectors = '{!! $allFieldSelectors !!}';
	
	// Get the menu item types classes as JSON object, then its values as array
	const menuItemTypeClasses = {!! $menuItemTypeClasses !!};
	const menuItemTypeClassList = Object.values(menuItemTypeClasses);
	
	// Get the link types classes as JSON object, then its values as array
	const linkTypeClasses = {!! $linkTypeClasses !!};
	const linkTypeClassList = Object.values(linkTypeClasses);
	
	onDocumentReady((event) => {
		const menuTypeEl = document.querySelector('select[name="type"].select2_from_array');
		const linkTypeEl = document.querySelector('select[name="url_type"].select2_from_array');
		const menuItemTypeClassPrefix = 'type-';
		const linkTypeClassPrefix = 'link-';
		
		// Handle Menu Type Selection
		if (menuTypeEl) {
			updateFieldVisibility(menuTypeEl, menuItemTypeClasses, menuItemTypeClassList, menuItemTypeClassPrefix);
			$(menuTypeEl).on("change", e => {
				const thisElement = e.target;
				updateFieldVisibility(thisElement, menuItemTypeClasses, menuItemTypeClassList, menuItemTypeClassPrefix);
				
				if (thisElement.value === 'link') {
					if (linkTypeEl) {
						linkTypeEl.value = "route";
						linkTypeEl.dispatchEvent(new Event("change"));
					}
				}
			});
		}
		
		// Handle Link/URL Type Selection
		if (linkTypeEl) {
			updateFieldVisibility(linkTypeEl, linkTypeClasses, linkTypeClassList, linkTypeClassPrefix);
			$(linkTypeEl).on("change", e => {
				const thisElement = e.target;
				updateFieldVisibility(thisElement, linkTypeClasses, linkTypeClassList, linkTypeClassPrefix);
			});
		}
	});
	
	function updateFieldVisibility(selectEl, typeClasses, typeClassList, classPrefix = '') {
		// Get all elements that could potentially be fields
		const allFields = document.querySelectorAll(allFieldSelectors);
		
		if (allFields.length <= 0) {
			return;
		}
		
		allFields.forEach(field => {
			const hasAnyTypeClass = hasTypeClass(field, typeClassList);
			const hasSelectedTypeClass = hasTypeClassInParents(field, selectEl.value, typeClasses, classPrefix);
			
			// Show field if:
			// 1. It has no type classes at all, OR
			// 2. It has the class that matches the selected type
			const shouldShow = !hasAnyTypeClass || hasSelectedTypeClass;
			
			// Find the parent element to show/hide
			let targetElement;
			if (hasAnyTypeClass || hasSelectedTypeClass) {
				// If field has type classes or selected type, find the parent with type class
				const parentWithClass = hasSelectedTypeClass
					? findParentWithTypeClass(field, selectEl.value, typeClasses, classPrefix)
					: findParentWithAnyTypeClass(field, typeClassList);
				
				if (parentWithClass) {
					targetElement = parentWithClass;
				}
			}
			
			if (!targetElement) {
				return;
			}
			
			// Apply visibility to the target element
			if (shouldShow) {
				targetElement.style.display = '';
				targetElement.classList.remove('hidden');
			} else {
				targetElement.style.display = 'none';
				targetElement.classList.add('hidden');
			}
		});
	}
	
	// Function to check if an element or any of its parents has one or more type classes
	function hasTypeClass(element, typeClassList) {
		let currentElement = element;
		
		// Traverse up the DOM tree until we reach the document
		while (currentElement && currentElement !== document) {
			if (currentElement.classList) {
				// Check if current element has any of the type classes
				const hasClass = typeClassList.some(className => {
					// Remove the dot from className for classList.contains()
					const cleanClassName = className.substring(1);
					return currentElement.classList.contains(cleanClassName);
				});
				
				if (hasClass) {
					return true;
				}
			}
			currentElement = currentElement.parentElement;
		}
		
		return false;
	}
	
	// Function to check if an element or any of its parents has a specific type class
	function hasTypeClassInParents(element, type, typeClasses, classPrefix = '') {
		if (!type) return false;
		
		let currentElement = element;
		// Get the class name from the typeClasses object or fallback to classPrefix + type format
		const targetClass = typeClasses[type] ? typeClasses[type].substring(1) : `${classPrefix}${type}`;
		
		// Traverse up the DOM tree until we reach the document
		while (currentElement && currentElement !== document) {
			if (currentElement.classList && currentElement.classList.contains(targetClass)) {
				return true;
			}
			currentElement = currentElement.parentElement;
		}
		
		return false;
	}
	
	// Function to find the parent element that has the matching type class
	function findParentWithTypeClass(element, type, typeClasses, classPrefix = '') {
		if (!type) return null;
		
		let currentElement = element;
		// Get the class name from the typeClasses object or fallback to classPrefix + type format
		const targetClass = typeClasses[type] ? typeClasses[type].substring(1) : `${classPrefix}${type}`;
		
		// Traverse up the DOM tree until we reach the document
		while (currentElement && currentElement !== document) {
			if (currentElement.classList && currentElement.classList.contains(targetClass)) {
				return currentElement;
			}
			currentElement = currentElement.parentElement;
		}
		
		return null;
	}
	
	// Function to find the parent element that has any of the type classes
	function findParentWithAnyTypeClass(element, typeClassList) {
		let currentElement = element;
		
		// Traverse up the DOM tree until we reach the document
		while (currentElement && currentElement !== document) {
			if (currentElement.classList) {
				// Check if current element has any of the type classes
				const hasClass = typeClassList.some(className => {
					// Remove the dot from className for classList.contains()
					const cleanClassName = className.substring(1);
					return currentElement.classList.contains(cleanClassName);
				});
				
				if (hasClass) {
					return currentElement;
				}
			}
			currentElement = currentElement.parentElement;
		}
		
		return null;
	}
</script>
