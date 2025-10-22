// ==============================================================
// Auto select left navbar
// ==============================================================
onDocumentReady((event) => {
	'use strict';
	
	// Get the current URL without hash
	const url = window.location.href;
	const urlWithoutHash = url.split('#')[0];
	
	// Extract the path portion of the URL (remove protocol and host)
	const origin = window.location.protocol + '//' + window.location.host + '/';
	const path = urlWithoutHash.replace(origin, '');
	
	// Find all sidebar links
	const sidebarLinks = document.querySelectorAll('ul#sidebarnav a');
	
	// Find the matching link in the sidebar nav
	// i.e. Find the link that matches current page
	let matchingElement = null;
	for (const link of sidebarLinks) {
		const linkUrl = link.getAttribute('href');
		if (linkUrl === urlWithoutHash || linkUrl === path) {
			matchingElement = link;
			break;
		}
	}
	
	/*
	 * Admin panel entity other internal links finding (e.g. /create, /123/edit, etc.)
	 *
	 * Note: Make sure that anchor links (i.e. links starting by #) are ignored
	 * Using getAttribute('href') returns the raw attribute value (e.g. #section)
	 * instead of full URL (e.g. https://example.com/foo/bar/baz#section).
	 */
	if (!matchingElement) {
		const urlWithFirstSegments = urlBuilder(urlWithoutHash)
		.removeAllParameters()
		.removeFragment()
		.keepFirstPathSegments(2)
		.toString();
		
		for (const link of sidebarLinks) {
			const linkUrl = link.getAttribute('href');
			if (!linkUrl.startsWith('https://') && !linkUrl.startsWith('http://')) {
				continue;
			}
			
			let linkUrlWithFirstSegments;
			try {
				linkUrlWithFirstSegments = urlBuilder(linkUrl)
				.removeAllParameters()
				.removeFragment()
				.keepFirstPathSegments(2)
				.toString();
			} catch (e) {
				linkUrlWithFirstSegments = linkUrl;
			}
			
			if (linkUrlWithFirstSegments === urlWithFirstSegments) {
				matchingElement = link;
				break;
			}
		}
	}
	
	// If a matching link is found, traverse up the DOM tree to highlight parent elements
	if (matchingElement) {
		// Get all parent elements up to .sidebar-nav
		let currentElement = matchingElement.parentElement;
		const sidebarNav = document.querySelector('.sidebar-nav');
		
		// Traverse up the DOM tree until we reach .sidebar-nav
		while (currentElement && currentElement !== sidebarNav) {
			// If parent is a list item with a direct child link
			// i.e. For <li> elements with child <a>
			const childLink = currentElement.querySelector('a');
			
			if (currentElement.tagName === 'LI' && childLink) {
				childLink.classList.add('active');
				
				// Check if it's directly under #sidebarnav
				// i.e. Check if this LI is a direct child of ul#sidebarnav
				if (currentElement.parentElement.id !== 'sidebarnav') {
					currentElement.classList.add('selected');
				} else {
					currentElement.classList.add('active');
				}
			}
			// If parent doesn't have a direct child link and is not a UL
			// i.e. For non-<ul> elements without child <a>
			else if (currentElement.tagName !== 'UL' && !childLink) {
				currentElement.classList.add('selected');
			}
			// If parent is a UL, mark it to show (expand)
			// i.e. For <ul> elements
			else if (currentElement.tagName === 'UL') {
				currentElement.classList.add('show');
			}
			
			currentElement = currentElement.parentElement;
		}
		
		// Add 'active' class to the matched link itself
		matchingElement.classList.add('active');
	}
	
	// Handle click events on sidebar links for accordion behavior
	// Event listener for clicks on sidebar links
	const allSidebarLinks = document.querySelectorAll('#sidebarnav a');
	allSidebarLinks.forEach(function (link) {
		link.addEventListener('click', function (e) {
			
			// If clicking an inactive link, close siblings and open this one
			if (!this.classList.contains('active')) {
				// Find the parent UL (first level up)
				const parentUl = this.closest('ul');
				
				if (parentUl) {
					// Close all sibling submenus in the same parent UL
					const siblingUls = parentUl.querySelectorAll('ul');
					siblingUls.forEach(ul => ul.classList.remove('show'));
					
					// Remove active class from all sibling links
					const siblingLinks = parentUl.querySelectorAll('a');
					siblingLinks.forEach(link => link.classList.remove('active'));
				}
				
				// Open the submenu for this link (if it exists)
				const nextUl = this.nextElementSibling;
				if (nextUl && nextUl.tagName === 'UL') {
					nextUl.classList.add('show');
				}
				
				// Mark this link as active
				this.classList.add('active');
			}
			// If clicking an already active link, close it
			else {
				// Remove active and show classes from this item and its parent
				this.classList.remove('active');
				
				// Remove active from parent UL
				const parentUl = this.closest('ul');
				if (parentUl) {
					parentUl.classList.remove('active');
				}
				
				// Close the submenu for this link (if it exists)
				const nextUl = this.nextElementSibling;
				if (nextUl && nextUl.tagName === 'UL') {
					nextUl.classList.remove('show');
				}
			}
			
		});
	});
	
	// Prevent default action for top-level links with dropdowns
	const arrowLinks = document.querySelectorAll('#sidebarnav > li > a.has-arrow');
	arrowLinks.forEach(link => {
		link.addEventListener('click', function (e) {
			e.preventDefault();
		});
	});
});
