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
 * @fileoverview Enhanced Navbar Scroll Effect for Bootstrap 5.3.6
 *
 * This module provides a sophisticated scroll-based navbar transformation system
 * that changes the navbar's appearance when users scroll past a defined threshold.
 * It supports theme switching, custom styling, and smooth transitions.
 *
 * @version 2.1.0
 * @requires Bootstrap 5.3.6
 * @requires onDocumentReady function (custom DOM ready utility)
 * @requires window.headerOptions global variable (optional configuration)
 *
 * @example
 * // HTML structure required:
 * // <header>
 * //   <nav class="navbar" id="mainNavbar">
 * //     <div class="navbar-brand">
 * //       <img class="light-logo" src="logo-light.png" alt="Logo">
 * //       <img class="dark-logo" src="logo-dark.png" alt="Logo">
 * //     </div>
 * //   </nav>
 * // </header>
 * // <main>
 * //   <div>Content...</div>
 * // </main>
 *
 * // Optional configuration:
 * // window.headerOptions = {
 * //   animationEnabled: true,
 * //   default: { darkThemeEnabled: false, bgColorClass: 'bg-light' },
 * //   fixed: { enabled: true, darkThemeEnabled: true, bgColorClass: 'bg-dark' }
 * // };
 *
 * // Include this script after Bootstrap JS:
 * // <script src="js/components/navbar-scroll.js"></script>
 */

/**
 * @typedef {Object} NavbarThemeConfig
 * @property {boolean} darkThemeEnabled - Whether dark theme is enabled
 * @property {int} height - Navbar minimum height
 * @property {string} cssClasses - Navbar classes
 * @property {string} containerCssClasses - Navbar container classes
 * @property {string|null} bgColor - Custom background color CSS value
 * @property {string|null} borderColor - Custom border color CSS value
 * @property {string|null} linkColorClass - Bootstrap link color class
 * @property {string|null} linkColor - Custom link color CSS value
 * @property {string|null} linkColorHover - Custom link hover color CSS value
 * @property {string|null} textColorClass - Bootstrap text color class
 * @property {string|null} textColor - Custom text color CSS value
 * @property {string} itemShadowClass - Custom text shadow class
 */

/**
 * @typedef {Object} NavbarScrollConfig
 * @property {boolean} animationEnabled - Whether navbar visibility animations are enabled
 * @property {NavbarThemeConfig} default - Default state configuration
 * @property {NavbarThemeConfig & {enabled: boolean}} fixed - Fixed state configuration
 * @property {string|null} expandedBgColorClass - Bootstrap background color class (for Expanded Collapse Navbar)
 * @property {string|null} expandedLinkColorClass - Bootstrap link color class (for Expanded Collapse Navbar)
 * @property {string|null} expandedTextColorClass - Bootstrap text color class (for Expanded Collapse Navbar)
 */

/**
 * Default configuration for navbar scroll effects
 * @type {NavbarScrollConfig}
 * @constant
 */
const DEFAULT_NAVBAR_CONFIG = {
	// Shared configuration options
	animationEnabled: true,
	navbarHeightOffset: null, // Offset value in pixels for scroll threshold
	
	default: {
		darkThemeEnabled: false,
		height: 65,
		cssClasses: 'fixed-top navbar-sticky bg-body-tertiary border-bottom',
		containerCssClasses: 'container',
		bgColor: null,
		borderColor: null,
		linkColorClass: null,
		linkColor: null,
		linkColorHover: null,
		textColorClass: null,
		textColor: null,
		itemShadowClass: null,
	},
	fixed: {
		enabled: false,
		darkThemeEnabled: false,
		height: 65,
		cssClasses: 'bg-body-tertiary shadow',
		containerCssClasses: 'container',
		bgColor: null,
		borderColor: null,
		linkColorClass: null,
		linkColor: null,
		linkColorHover: null,
		textColorClass: null,
		textColor: null,
		itemShadowClass: null,
	},
	
	expandedBgColorClass: null,
	expandedLinkColorClass: null,
	expandedTextColorClass: null,
};

/**
 * CSS class names used for navbar state management
 * @type {Object}
 * @constant
 */
const NAVBAR_CSS_CLASSES = {
	STUCK: 'navbar-stuck',
	FIXED_TOP: 'fixed-top',
};

/**
 * Configuration for scroll behavior
 * @type {Object}
 * @constant
 */
const SCROLL_CONFIG = {
	NAVBAR_HEIGHT_OFFSET: 200, // Arbitrary offset value in pixels
	VISIBILITY_DELAY: 100,
};

/**
 * Initializes the enhanced navbar scroll effect system
 * @function initializeNavbarScrollEffect
 * @description Sets up scroll event listeners and navbar state management
 * @param {Event} event - The document ready event
 */
onDocumentReady((event) => {
	// Merge user configuration with defaults
	const userConfig = typeof window.headerOptions !== 'undefined' ? window.headerOptions : {};
	const config = mergeDeep(DEFAULT_NAVBAR_CONFIG, userConfig);
	
	// console.log('Navbar scroll effect initialized with config:', config);
	
	// Initialize navbar scroll controller
	const navbarController = new NavbarScrollController(config);
	
	// Always initialize basic navbar visibility
	if (!config.fixed.enabled) {
		// console.log('Fixed navbar effect is disabled - showing navbar in default state');
		navbarController.initializeBasicNavbar();
		return;
	}
	
	navbarController.initialize();
});


/* ----- HELPERS ----- */


/**
 * Class responsible for managing navbar scroll effects
 * @class NavbarScrollController
 */
class NavbarScrollController {
	/**
	 * @param {NavbarScrollConfig} config - Configuration object for navbar behavior
	 */
	constructor(config) {
		this.config = config;
		this.elements = {};
		this.scrollThreshold = 0;
		this.isThrottling = false;
		this.eventListeners = new Map();
		
		// Bind methods to preserve context
		this.handleScroll = this.handleScroll.bind(this);
		this.throttledScrollHandler = this.throttledScrollHandler.bind(this);
	}
	
	/**
	 * Initializes basic navbar visibility when fixed effect is disabled
	 * @returns {boolean} Success status
	 */
	initializeBasicNavbar() {
		if (!this.cacheElements()) {
			console.warn('Required elements not found. Basic navbar initialization failed.');
			return false;
		}
		
		this.setupInitialState();
		this.applyDefaultConfiguration(); // Apply all default styling
		
		// console.log('Basic navbar initialized successfully');
		return true;
	}
	
	/**
	 * Initializes the navbar scroll effect
	 * @returns {boolean} Success status
	 */
	initialize() {
		if (!this.cacheElements()) {
			console.warn('Required elements not found. Navbar scroll effect disabled.');
			return false;
		}
		
		this.calculateScrollThreshold();
		this.setupInitialState();
		this.applyDefaultConfiguration(); // Apply all default styling
		this.adjustMainContentLayout();
		this.attachScrollListener();
		
		// console.log('Navbar scroll effect successfully initialized');
		return true;
	}
	
	/**
	 * Applies the complete default configuration including classes and custom styling
	 */
	applyDefaultConfiguration() {
		const {navbar, navbarContainer} = this.elements;
		
		// Apply default CSS classes
		if (CSSUtils.isNonEmptyString(this.config.default.cssClasses)) {
			addClassesToElement(navbar, this.config.default.cssClasses);
		}
		
		if (CSSUtils.isNonEmptyString(this.config.default.containerCssClasses)) {
			addClassesToElement(navbarContainer, this.config.default.containerCssClasses);
		}
		
		// Apply default custom styling
		this.applyCustomStyling(this.config.default);
		
		// Apply default link colors
		this.updateCssColors(this.config.default, true);
		
		// Adds event listeners for Bootstrap collapse events
		this.addCollapsedEventListener(this.config);
	}
	
	/**
	 * Caches references to required DOM elements
	 * @returns {boolean} True if all required elements are found
	 */
	cacheElements() {
		this.elements = {
			header: document.querySelector('header'),
			navbar: document.getElementById('mainNavbar'),
			navbarContainer: document.getElementById('mainNavbarContainer'),
			logoLight: document.querySelector('.navbar-brand .light-logo'),
			logoDark: document.querySelector('.navbar-brand .dark-logo'),
			main: document.querySelector('main'),
			firstMainChild: document.querySelector('main > div.sectionable'), // Only .sectionable
		};
		
		// Check if all required elements exist
		const requiredElements = ['header', 'navbar', 'navbarContainer', 'logoLight', 'logoDark', 'main'];
		const missingElements = requiredElements.filter(key => !this.elements[key]);
		
		if (missingElements.length > 0) {
			console.error('Missing required elements:', missingElements);
			return false;
		}
		
		// Cache the prevent-header-overlap container if it exists
		if (this.elements.firstMainChild) {
			// Get the first div child (of the firstMainChild element)
			const firstDiv = this.elements.firstMainChild.querySelector('div');
			
			// Only set preventHeaderOverlapContainer if the first div has the prevent-header-overlap class
			if (firstDiv && firstDiv.classList.contains('prevent-header-overlap')) {
				this.elements.preventHeaderOverlapContainer = firstDiv;
			} else {
				this.elements.preventHeaderOverlapContainer = null;
			}
		}
		
		return true;
	}
	
	/**
	 * Calculates the scroll threshold based on navbar height
	 */
	calculateScrollThreshold() {
		const navbarHeight = this.elements.navbar.offsetHeight;
		
		// Use config value if set, otherwise use default
		const offsetValue = (typeof this.config.navbarHeightOffset === 'number')
			? this.config.navbarHeightOffset
			: SCROLL_CONFIG.NAVBAR_HEIGHT_OFFSET;
		
		this.scrollThreshold = navbarHeight + offsetValue;
		// console.log(`Scroll threshold set to: ${this.scrollThreshold}px`);
	}
	
	/**
	 * Sets up the initial state of the navbar
	 */
	setupInitialState() {
		this.applyThemeConfiguration(this.config.default);
		
		// Show navbar with animation or immediately based on configuration
		if (this.config.animationEnabled) {
			// Show navbar with a slight delay for smooth appearance
			setTimeout(() => {
				this.elements.navbar.classList.add(NAVBAR_CSS_CLASSES.STUCK);
			}, SCROLL_CONFIG.VISIBILITY_DELAY);
		} else {
			// Skip animation entirely - don't add the visible class
			// The navbar will be visible by default without CSS animations
			// console.log('Navbar animation disabled - skipping visibility class');
		}
	}
	
	/**
	 * Adjusts the main content layout to accommodate the navbar
	 */
	adjustMainContentLayout() {
		const navbarHeight = this.elements.navbar.offsetHeight;
		const {navbar, main, firstMainChild, preventHeaderOverlapContainer} = this.elements;
		
		// Get the height taken by the navbar using its offsetHeight (height + paddings) & its margins
		const navbarComputedStyle = window.getComputedStyle(navbar);
		const navbarMarginTop = CSSUtils.parseCssSize(navbarComputedStyle.marginTop);
		const navbarMarginBottom = CSSUtils.parseCssSize(navbarComputedStyle.marginBottom);
		const navbarFullHeight = navbarHeight + navbarMarginTop + navbarMarginBottom;
		
		// Not sectionable element found (as first element)
		if (!firstMainChild) {
			
			// Adjust main content positioning
			main.style.setProperty('margin-top', `${navbarFullHeight}px`);
			
		} else {
			// Sectionable element found as first element
			// ---
			// If the first element of the sectionable element has the "prevent-header-overlap" class,
			// then, adjust the sectionable element's margin-top to prevent header overlapping
			if (preventHeaderOverlapContainer) {
				// Adjust main content positioning
				main.style.setProperty('margin-top', `${navbarFullHeight}px`);
				
				// Retrieve the sectionable element margin-top value
				// and add it to the navbarFullHeight to calculate the new margin-top value to set to the sectionable element
				const computedStyle = window.getComputedStyle(firstMainChild);
				const currentMarginTop = CSSUtils.parseCssSize(computedStyle.marginTop);
				let newMarginTop = navbarFullHeight + currentMarginTop;
				
				// Retrieve the margin-top value of first element of the sectionable element
				// and add it to the newMarginTop to calculate the new margin-top value to set to the sectionable element
				const firstSectionStyle = window.getComputedStyle(preventHeaderOverlapContainer);
				const firstSectionMarginTop = CSSUtils.parseCssSize(firstSectionStyle.marginTop);
				newMarginTop = newMarginTop + firstSectionMarginTop;
				
				// Set the calculated margin-top to the sectionable element
				firstMainChild.style.setProperty('margin-top', `${newMarginTop}px`, 'important');
			}
		}
	}
	
	/**
	 * Attaches the throttled scroll event listener
	 */
	attachScrollListener() {
		window.addEventListener('scroll', this.throttledScrollHandler, {passive: true});
	}
	
	/**
	 * Throttled scroll handler to improve performance
	 */
	throttledScrollHandler() {
		if (!this.isThrottling) {
			requestAnimationFrame(() => {
				this.handleScroll();
				this.isThrottling = false;
			});
			this.isThrottling = true;
		}
	}
	
	/**
	 * Main scroll handler that manages navbar state transitions
	 */
	handleScroll() {
		const scrollPosition = window.scrollY;
		const navbarHeight = this.elements.navbar.offsetHeight;
		
		if (scrollPosition > this.scrollThreshold) {
			this.activateFixedState();
		} else {
			this.handleNonFixedState(scrollPosition, navbarHeight);
		}
	}
	
	/**
	 * Activates the fixed navbar state (during the runtime, on scroll)
	 */
	activateFixedState() {
		const {navbar} = this.elements;
		
		// Apply fixed state theme and styling
		this.applyThemeConfiguration(this.config.fixed);
		if (!navbar.classList.contains(NAVBAR_CSS_CLASSES.FIXED_TOP)) {
			navbar.classList.add(NAVBAR_CSS_CLASSES.FIXED_TOP);
		}
		
		// Transition CSS classes from default to fixed
		this.transitionCssClasses(this.config.default, this.config.fixed);
		
		// Apply custom CSS properties
		this.applyCustomStyling(this.config.fixed);
		
		// Update link colors with event listeners
		this.updateCssColors(this.config.fixed);
		
		// Only add visible class if animations are enabled
		if (this.config.animationEnabled) {
			navbar.classList.add(NAVBAR_CSS_CLASSES.STUCK);
		}
	}
	
	/**
	 * Handles navbar state when not in fixed position
	 * @param {number} scrollPosition - Current scroll position
	 * @param {number} navbarHeight - Height of the navbar
	 */
	handleNonFixedState(scrollPosition, navbarHeight) {
		const {navbar} = this.elements;
		
		if (scrollPosition <= navbarHeight) {
			// Only add visible class if animations are enabled
			if (this.config.animationEnabled) {
				navbar.classList.add(NAVBAR_CSS_CLASSES.STUCK);
			}
		} else {
			this.deactivateFixedState();
		}
	}
	
	/**
	 * Deactivates the fixed navbar state (during the runtime, on scroll)
	 */
	deactivateFixedState() {
		const {navbar} = this.elements;
		
		// Apply default state theme and styling
		this.applyThemeConfiguration(this.config.default);
		// navbar.classList.remove(NAVBAR_CSS_CLASSES.FIXED_TOP);
		
		// Transition CSS classes from fixed to default
		this.transitionCssClasses(this.config.fixed, this.config.default);
		
		// Apply or remove custom CSS properties
		this.applyCustomStyling(this.config.default, true);
		
		// Update link colors with event listeners
		this.updateCssColors(this.config.default, true);
		
		// Only remove visible class if animations are enabled
		if (this.config.animationEnabled) {
			navbar.classList.remove(NAVBAR_CSS_CLASSES.STUCK);
		}
	}
	
	/**
	 * Applies theme configuration (dark/light theme and logo visibility)
	 * @param {NavbarThemeConfig} themeConfig - Theme configuration object
	 */
	applyThemeConfiguration(themeConfig) {
		const {header, logoLight, logoDark} = this.elements;
		
		if (themeConfig.darkThemeEnabled) {
			header.setAttribute('data-bs-theme', 'dark');
		} else {
			header.removeAttribute('data-bs-theme');
		}
		
		if (themeConfig.darkThemeEnabled || isDarkThemeEnabledInDomRoot()) {
			logoDark.style.setProperty('display', 'none');
			logoLight.style.setProperty('display', 'block');
		} else {
			logoDark.style.setProperty('display', 'block');
			logoLight.style.setProperty('display', 'none');
		}
	}
	
	/**
	 * Transitions CSS classes between states
	 * @param {NavbarThemeConfig} fromConfig - Source configuration
	 * @param {NavbarThemeConfig} toConfig - Target configuration
	 */
	transitionCssClasses(fromConfig, toConfig) {
		const {navbar, navbarContainer} = this.elements;
		
		// Handle navbar classes
		this.updateCssClass(navbar, fromConfig.cssClasses, toConfig.cssClasses);
		
		// Handle navbar container classes
		this.updateCssClass(navbarContainer, fromConfig.containerCssClasses, toConfig.containerCssClasses);
		
		// Handle links classes
		const links = navbar.querySelectorAll('a:not(.btn):not(.dropdown-item)');
		links.forEach(link => {
			this.updateCssClass(link, fromConfig.linkColorClass, toConfig.linkColorClass);
			this.updateCssClass(link, fromConfig.itemShadowClass, toConfig.itemShadowClass);
		});
		
		// Handle text classes
		const textElements = navbar.querySelectorAll('.menu-type-title');
		textElements.forEach(element => {
			this.updateCssClass(element, fromConfig.textColorClass, toConfig.textColorClass);
			this.updateCssClass(element, fromConfig.itemShadowClass, toConfig.itemShadowClass);
		});
	}
	
	/**
	 * Updates a CSS class on an element
	 * @param {HTMLElement} element - Target element
	 * @param {string} removeClass - Class to remove
	 * @param {string} addClass - Class to add
	 */
	updateCssClass(element, removeClass, addClass) {
		if (CSSUtils.isNonEmptyString(removeClass)) {
			// element.classList.remove(removeClass);
			removeClassesFromElement(element, removeClass);
		}
		if (CSSUtils.isNonEmptyString(addClass)) {
			// element.classList.add(addClass);
			addClassesToElement(element, addClass);
		}
	}
	
	/**
	 * Applies custom CSS styling
	 * @param {NavbarThemeConfig} config - Configuration object
	 * @param {boolean} removeProperties - Whether to remove properties instead of setting them
	 */
	applyCustomStyling(config, removeProperties = false) {
		const {navbar} = this.elements;
		const styleProperties = [
			{property: 'min-height', value: config.height, defaultValue: this.config.default.height},
			{property: 'background-color', value: config.bgColor, defaultValue: this.config.default.bgColor},
			{property: 'border-color', value: config.borderColor, defaultValue: this.config.default.borderColor},
		];
		
		styleProperties.forEach(({property, value, defaultValue}) => {
			if (property === 'min-height') {
				value = `${value}px`;
				defaultValue = `${defaultValue}px`;
			}
			
			if (removeProperties) {
				// If there's a default value, restore it; otherwise remove the property
				if (CSSUtils.isNonEmptyString(defaultValue)) {
					navbar.style.setProperty(property, defaultValue, 'important');
				} else {
					navbar.style.removeProperty(property);
				}
				
				if (property === 'background-color') {
					this.toggleGlassEffect(defaultValue);
				}
			} else {
				// Apply the value if it's not empty
				if (CSSUtils.isNonEmptyString(value)) {
					navbar.style.setProperty(property, value, 'important');
				} else {
					// @+
					navbar.style.removeProperty(property);
				}
				
				if (property === 'background-color') {
					this.toggleGlassEffect(value);
				}
			}
		});
	}
	
	/**
	 * Toggle glass effect to the navbar when the background color is transparent
	 * @param bgColor
	 */
	toggleGlassEffect(bgColor) {
		const {navbar} = this.elements;
		
		if (isTransparentRgba(bgColor)) {
			navbar.style.setProperty('backdrop-filter', 'blur(10px)', 'important');
		} else {
			navbar.style.removeProperty('backdrop-filter');
		}
	}
	
	/**
	 * Adds event listeners for Bootstrap collapse events to toggle navbar styling
	 * @param {NavbarScrollConfig} config - Configuration object
	 * @param {string} [config.expandedBgColorClass] - CSS class to add when navbar is expanded
	 * @param {string} [config.expandedLinkColorClass] - CSS class for links when navbar is expanded
	 * @param {string} [config.expandedTextColorClass] - CSS class for text elements when navbar is expanded
	 * @param {Object} config.default - Default state configuration
	 * @param {string} [config.default.cssClasses] - CSS class to add when navbar is collapsed
	 * @param {string} [config.default.linkColorClass] - CSS class for links when navbar is collapsed
	 * @param {string} [config.default.textColorClass] - CSS class for text elements when navbar is collapsed
	 */
	addCollapsedEventListener(config) {
		const {navbar} = this.elements;
		const collapseElement = navbar.querySelector('.navbar-collapse');
		
		if (!collapseElement) {
			console.error('Collapse element not found');
			return;
		}
		
		// Get navbar links and text elements
		const links = navbar.querySelectorAll('a:not(.btn):not(.dropdown-item)');
		const textElements = navbar.querySelectorAll('.menu-type-title');
		
		// Attach the Bootstrap show event to the collapseElement
		collapseElement.addEventListener('show.bs.collapse', () => {
			// Handle navbar classes
			if (CSSUtils.isNonEmptyString(config.expandedBgColorClass)) {
				updateClassesWithPrefix(navbar, 'bg-', config.expandedBgColorClass);
			}
			
			// Handle links color class
			if (CSSUtils.isNonEmptyString(config.expandedLinkColorClass)) {
				links.forEach(link => {
					updateClassesWithPrefix(link, 'link-', config.expandedLinkColorClass);
				});
			}
			
			// Handle text elements color class
			if (CSSUtils.isNonEmptyString(config.expandedTextColorClass)) {
				textElements.forEach(element => {
					updateClassesWithPrefix(element, 'text-', config.expandedTextColorClass);
				});
			}
		});
		
		// Attach the Bootstrap hide event to the collapseElement
		collapseElement.addEventListener('hide.bs.collapse', () => {
			// Handle navbar classes
			if (CSSUtils.isNonEmptyString(config.default.cssClasses)) {
				updateClassesWithPrefix(navbar, 'bg-', config.default.cssClasses);
			}
			
			// Handle links color class
			if (CSSUtils.isNonEmptyString(config.default.linkColorClass)) {
				links.forEach(link => {
					updateClassesWithPrefix(link, 'link-', config.default.linkColorClass);
				});
			}
			
			// Handle text elements color class
			if (CSSUtils.isNonEmptyString(config.default.textColorClass)) {
				textElements.forEach(element => {
					updateClassesWithPrefix(element, 'text-', config.default.textColorClass);
				});
			}
		});
	}
	
	/**
	 * Updates elements CSS colors and hover effects
	 * @param {NavbarThemeConfig} config - Configuration object
	 * @param {boolean} isDefaultState - Whether this is the default state
	 */
	updateCssColors(config, isDefaultState = false) {
		// Handle link elements
		const links = this.elements.navbar.querySelectorAll('a:not(.btn):not(.dropdown-item)');
		links.forEach(link => {
			// Clean up existing event listeners
			this.cleanupLinkEventListeners(link);
			
			if (isDefaultState) {
				this.applyDefaultElementStyling(link, config);
			} else {
				this.applyFixedElementStyling(link, config);
			}
		});
		
		// Handle text elements
		const textElements = this.elements.navbar.querySelectorAll('.menu-type-title');
		textElements.forEach(element => {
			if (isDefaultState) {
				this.applyDefaultElementStyling(element, config);
			} else {
				this.applyFixedElementStyling(element, config);
			}
		});
	}
	
	/**
	 * Applies default state element styling
	 * @param {HTMLElement} element - HTML element
	 * @param {NavbarThemeConfig} config - Configuration object
	 */
	applyDefaultElementStyling(element, config) {
		// Handle link element
		const isLink = (element.tagName === 'A' && !element.classList.contains('menu-type-title'));
		if (isLink) {
			if (CSSUtils.isNonEmptyString(config.linkColor)) {
				element.style.setProperty('color', config.linkColor, 'important');
				this.addLinkEventListener(element, 'mouseout', () => {
					element.style.setProperty('color', config.linkColor, 'important');
				});
			} else {
				element.style.removeProperty('color');
			}
			
			if (CSSUtils.isNonEmptyString(config.linkColorHover)) {
				this.addLinkEventListener(element, 'mouseover', () => {
					element.style.setProperty('color', config.linkColorHover, 'important');
				});
			}
		}
		
		// Handle text element
		const isText = (element.classList.contains('menu-type-title'));
		if (isText) {
			if (CSSUtils.isNonEmptyString(config.textColor)) {
				element.style.setProperty('color', config.textColor, 'important');
			} else {
				element.style.removeProperty('color');
			}
		}
	}
	
	/**
	 * Applies fixed state element styling
	 * @param {HTMLElement} element - HTML element
	 * @param {NavbarThemeConfig} config - Configuration object
	 */
	applyFixedElementStyling(element, config) {
		// Handle link element
		const isLink = (element.tagName === 'A' && !element.classList.contains('menu-type-title'));
		if (isLink) {
			if (CSSUtils.isNonEmptyString(config.linkColor)) {
				element.style.setProperty('color', config.linkColor, 'important');
				this.addLinkEventListener(element, 'mouseout', () => {
					element.style.setProperty('color', config.linkColor, 'important');
				});
			}
			
			if (CSSUtils.isNonEmptyString(config.linkColorHover)) {
				this.addLinkEventListener(element, 'mouseover', () => {
					element.style.setProperty('color', config.linkColorHover, 'important');
				});
			}
		}
		
		// Handle text element
		const isText = (element.classList.contains('menu-type-title'));
		if (isText) {
			if (CSSUtils.isNonEmptyString(config.textColor)) {
				element.style.setProperty('color', config.textColor, 'important');
			}
		}
	}
	
	/**
	 * Adds an event listener to a link and tracks it for cleanup
	 * @param {HTMLElement} link - Link element
	 * @param {string} event - Event type
	 * @param {Function} handler - Event handler
	 */
	addLinkEventListener(link, event, handler) {
		link.addEventListener(event, handler);
		
		if (!this.eventListeners.has(link)) {
			this.eventListeners.set(link, []);
		}
		this.eventListeners.get(link).push({event, handler});
	}
	
	/**
	 * Cleans up event listeners for a link
	 * @param {HTMLElement} link - Link element
	 */
	cleanupLinkEventListeners(link) {
		if (this.eventListeners.has(link)) {
			const listeners = this.eventListeners.get(link);
			listeners.forEach(({event, handler}) => {
				link.removeEventListener(event, handler);
			});
			this.eventListeners.delete(link);
		}
	}
	
	/**
	 * Destroys the navbar scroll controller and cleans up resources
	 */
	destroy() {
		// Remove scroll event listener
		window.removeEventListener('scroll', this.throttledScrollHandler);
		
		// Clean up all link event listeners
		this.eventListeners.forEach((listeners, link) => {
			this.cleanupLinkEventListeners(link);
		});
		
		// console.log('Navbar scroll effect destroyed');
	}
}

/**
 * Utility class for CSS-related operations
 * @class CSSUtils
 */
class CSSUtils {
	/**
	 * Parses CSS size values and converts them to pixels
	 * @param {string} value - CSS size value (e.g., '16px', '1rem')
	 * @returns {number} Value in pixels
	 */
	static parseCssSize(value) {
		if (typeof value !== 'string') {
			return 0;
		}
		
		let result;
		
		if (value.endsWith('rem')) {
			result = CSSUtils.remToPx(value);
		} else if (value.endsWith('px')) {
			result = parseFloat(value);
		} else if (value.endsWith('em')) {
			result = CSSUtils.emToPx(value);
		} else {
			// Handle unitless values (like "15" or "0") and CSS keywords (like "auto")
			// Will return NaN for keywords, number for unitless values
			result = parseFloat(value);
		}
		
		// Return 0 if result is NaN, otherwise return the result
		return Number.isNaN(result) ? 0 : result;
	}
	
	/**
	 * Converts rem units to pixels
	 * @param {string} remValue - Value in rem units
	 * @returns {number} Value in pixels
	 */
	static remToPx(remValue) {
		const rootFontSize = parseFloat(
			window.getComputedStyle(document.documentElement).fontSize
		);
		return parseFloat(remValue) * rootFontSize;
	}
	
	/**
	 * Converts em units to pixels (relative to current element)
	 * @param {string} emValue - Value in em units
	 * @param {HTMLElement} element - Reference element (optional)
	 * @returns {number} Value in pixels
	 */
	static emToPx(emValue, element = document.documentElement) {
		const fontSize = parseFloat(window.getComputedStyle(element).fontSize);
		return parseFloat(emValue) * fontSize;
	}
	
	/**
	 * Checks if a value is a non-empty string
	 * @param {*} value - Value to check
	 * @returns {boolean} True if value is a non-empty string
	 */
	static isNonEmptyString(value) {
		return typeof value === 'string' && value.trim().length > 0;
	}
	
	/**
	 * Checks if a value is an empty string
	 * @param {*} value - Value to check
	 * @returns {boolean} True if value is an empty string
	 */
	static isEmptyString(value) {
		return !CSSUtils.isNonEmptyString(value);
	}
}

/**
 * Deep merges two objects, with the second object taking precedence
 * @param {Object} target - Target object
 * @param {Object} source - Source object
 * @returns {Object} Merged object
 */
function mergeDeep(target, source) {
	const result = {...target};
	
	for (const key in source) {
		if (source.hasOwnProperty(key)) {
			if (typeof source[key] === 'object' && source[key] !== null && !Array.isArray(source[key])) {
				result[key] = mergeDeep(result[key] || {}, source[key]);
			} else {
				result[key] = source[key];
			}
		}
	}
	
	return result;
}
