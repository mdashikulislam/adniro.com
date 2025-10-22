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
 * Class to manage dark mode based on app settings, user (database), cookie, or system preferences
 */
class ThemeDetector {
	#cookieManager;
	#isFromAdminPanel;
	#isDarkThemeEnabled;
	#isSystemThemeEnabled;
	#isLoggedUser;
	#userPreference;
	#cookieName;
	#cookieExpiresMinutes;
	#showIconOnly;
	#mediaQuery;
	#validPreferences = ['dark', 'light'];
	
	/**
	 * Initializes ThemeDetector
	 * @param {CookieManager} cookieManager - Instance of CookieManager for cookie operations
	 * @param {Object} [config={}] - Configuration object
	 * @param {boolean} [config.isFromAdminPanel=false] - Whether user is from the admin panel
	 * @param {boolean} [config.isDarkThemeEnabled=true] - Whether dark theme is allowed by app admin
	 * @param {boolean} [config.isSystemThemeEnabled=true] - Whether system/auto theme is allowed by app admin
	 * @param {boolean} [config.isLoggedUser=false] - Whether the current user is logged in
	 * @param {string|null} [config.userPreference=null] - User's theme preference from database ('dark', 'light', 'system', or null)
	 * @param {string} [config.cookieName='themePreference'] - Name of the cookie for theme preference
	 * @param {number} [config.cookieExpiresMinutes=60] - Cookie expiration in minutes
	 * @param {boolean} [config.showIconOnly=false] - Whether showing only icon is allowed
	 */
	constructor(cookieManager, config = {}) {
		if (!(cookieManager instanceof CookieManager)) {
			throw new Error('A valid CookieManager instance is required.');
		}
		
		this.#cookieManager = cookieManager;
		this.#isFromAdminPanel = config.isFromAdminPanel ?? false;
		this.#isDarkThemeEnabled = config.isDarkThemeEnabled ?? true;
		this.#isSystemThemeEnabled = config.isSystemThemeEnabled ?? true;
		this.#isLoggedUser = config.isLoggedUser ?? false;
		if (this.#isSystemThemeEnabled) {
			this.#validPreferences.push('system');
		}
		this.#userPreference = this.#validPreferences.includes(config.userPreference) ? config.userPreference : null;
		this.#cookieName = config.cookieName ?? 'themePreference';
		this.#cookieExpiresMinutes = config.cookieExpiresMinutes ?? 365 * 24 * 60; // 1 year in minutes
		this.#showIconOnly = config.showIconOnly ?? false;
		this.#mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
		
		// Initial theme setup
		this.#updateTheme();
		
		// Update the theme switcher on document load or on theme change manually
		const themeSwitcherEl = document.getElementById('themeSwitcher');
		if (themeSwitcherEl) {
			this.updateThemeSwitcher(themeSwitcherEl, null);
		}
		
		// Listen for system theme changes
		this.#mediaQuery.addEventListener('change', this.#handleSystemThemeChange.bind(this));
	}
	
	/**
	 * Check if dark mode is enabled based on preferences without updating the theme
	 * @static
	 * @param {CookieManager} cookieManager - Instance of CookieManager for cookie operations
	 * @param {Object} [config={}] - Configuration object
	 * @param {boolean} [config.isDarkThemeEnabled=true] - Whether dark theme is allowed by app admin
	 * @param {boolean} [config.isSystemThemeEnabled=true] - Whether system/auto theme is allowed by app admin
	 * @param {boolean} [config.isLoggedUser=false] - Whether the current user is logged in
	 * @param {string|null} [config.userPreference=null] - User's theme preference from database ('dark', 'light', 'system', or null)
	 * @param {string} [config.cookieName='themePreference'] - Name of the cookie for theme preference
	 * @returns {boolean} True if dark mode should be applied
	 */
	static checkDarkTheme(cookieManager, config = {}) {
		if (!(cookieManager instanceof CookieManager)) {
			throw new Error('A valid CookieManager instance is required.');
		}
		
		const isDarkThemeEnabled = config.isDarkThemeEnabled ?? true;
		const isSystemThemeEnabled = config.isSystemThemeEnabled ?? true;
		const isLoggedUser = config.isLoggedUser ?? false;
		const validPreferences = ['dark', 'light'];
		if (isSystemThemeEnabled) {
			validPreferences.push('system');
		}
		const userPreference = validPreferences.includes(config.userPreference) ? config.userPreference : null;
		const cookieName = config.cookieName ?? 'themePreference';
		const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
		
		// If dark mode is disabled by app settings, return false
		if (!isDarkThemeEnabled) return false;
		
		// If user is logged in and has a database preference, use it
		if (isLoggedUser && userPreference !== null) {
			if (userPreference === 'system') return mediaQuery.matches;
			return userPreference === 'dark';
		}
		
		// Check cookie preference
		const cookiePreference = cookieManager.getCookie(cookieName);
		if (validPreferences.includes(cookiePreference)) {
			if (cookiePreference === 'system') return mediaQuery.matches;
			return cookiePreference === 'dark';
		}
		
		// Fall back to system preference
		return (isSystemThemeEnabled && mediaQuery.matches);
	}
	
	/**
	 * Check if dark mode is enabled based on preferences
	 * @returns {boolean} True if dark mode should be applied
	 */
	isDarkThemeEnabled() {
		const currentTheme = this.getTheme();
		
		if (currentTheme === 'system') {
			return this.#mediaQuery.matches;
		}
		
		return currentTheme === 'dark';
	}
	
	/**
	 * Get the current theme
	 * @returns {*|string|null}
	 */
	getTheme() {
		// If dark mode is disabled by app settings, always return false
		if (!this.#isFromAdminPanel && !this.#isDarkThemeEnabled) return null;
		
		// If user is logged in and has a database preference, use it
		if (this.#isLoggedUser && this.#userPreference !== null) {
			return this.#userPreference;
		}
		
		// Check cookie preference
		const cookiePreference = this.#cookieManager.getCookie(this.#cookieName);
		if (this.#validPreferences.includes(cookiePreference)) {
			return cookiePreference;
		}
		
		// Fall back to system preference
		return this.#isSystemThemeEnabled ? 'system' : null;
	}
	
	/**
	 * Handle theme changes (system or user-initiated)
	 * @private
	 */
	#updateTheme() {
		if (this.isDarkThemeEnabled()) {
			this.#applyDarkMode();
		} else {
			this.#removeDarkMode();
		}
	}
	
	/**
	 * Apply dark mode styles
	 * @private
	 */
	#applyDarkMode() {
		// [Business Code]
		// [Add any dark mode styling here]
		if (this.#isFromAdminPanel) {
			document.documentElement.setAttribute('data-bs-theme', 'dark');
			document.documentElement.setAttribute('data-theme', 'dark');
			document.body.setAttribute('data-theme', 'dark');
			
			const mainWrapperEl = $('#main-wrapper');
			if (mainWrapperEl.length) {
				const themeConfig = {...adminPanelSettings || {}};
				themeConfig.Theme = true;
				mainWrapperEl.AdminSettings(themeConfig);
			}
		} else {
			// document.documentElement.setAttribute('theme', 'dark');
			document.documentElement.setAttribute('data-bs-theme', 'dark');
			
			const logoDarkEl = document.querySelector('.navbar .logo img.dark-logo');
			const logoLightEl = document.querySelector('.navbar .logo img.light-logo');
			if (logoDarkEl && logoLightEl) {
				logoDarkEl.style.setProperty('display', 'none');
				logoLightEl.style.setProperty('display', 'block');
			}
		}
		
		const recaptchaEls = document.querySelectorAll('.g-recaptcha');
		if (recaptchaEls.length > 0) {
			recaptchaEls.forEach(item => {
				item.setAttribute('data-theme', 'dark');
			});
		}
	}
	
	/**
	 * Remove dark mode styles
	 * @private
	 */
	#removeDarkMode() {
		// [Business Code]
		// [Add any light mode styling here]
		if (this.#isFromAdminPanel) {
			document.documentElement.removeAttribute('data-bs-theme');
			document.documentElement.setAttribute('data-theme', 'light');
			document.body.setAttribute('data-theme', 'light');
			
			const mainWrapperEl = $('#main-wrapper');
			if (mainWrapperEl.length) {
				const themeConfig = {...adminPanelSettings || {}};
				themeConfig.Theme = false;
				mainWrapperEl.AdminSettings(themeConfig);
			}
		} else {
			// document.documentElement.setAttribute('theme', 'light');
			document.documentElement.removeAttribute('data-bs-theme');
			
			const logoDarkEl = document.querySelector('.navbar .logo img.dark-logo');
			const logoLightEl = document.querySelector('.navbar .logo img.light-logo');
			if (logoDarkEl && logoLightEl) {
				logoDarkEl.style.setProperty('display', 'block');
				logoLightEl.style.setProperty('display', 'none');
			}
		}
		
		const recaptchaEls = document.querySelectorAll('.g-recaptcha');
		if (recaptchaEls.length > 0) {
			recaptchaEls.forEach(item => {
				item.setAttribute('data-theme', 'light');
			});
		}
	}
	
	/**
	 * Update the theme switcher selection (If it is available)
	 * @param themeSwitcherEl
	 * @param selectedTheme
	 */
	updateThemeSwitcher(themeSwitcherEl, selectedTheme = null) {
		const currentTheme = this.getTheme();
		
		// [Business Code]
		// If the theme switcher is available, then update its UI.
		if (!themeSwitcherEl) {
			return;
		}
		
		const buttonEl = themeSwitcherEl.querySelector('.dropdown-toggle');
		const buttonElSpanLarge = themeSwitcherEl.querySelector('.dropdown-toggle span.large-screen');
		const buttonElSpanSmall = themeSwitcherEl.querySelector('.dropdown-toggle span.small-screen');
		const menuItemsEls = themeSwitcherEl.querySelectorAll('a.dropdown-item, li.dropdown-item a.nav-link');
		
		if (!buttonEl || menuItemsEls.length <= 0) {
			return;
		}
		
		const buttonTheme = buttonEl.getAttribute('data-theme');
		menuItemsEls.forEach(item => {
			// Get the selected theme data
			const itemTheme = item.getAttribute('data-theme');
			const itemLabel = item.innerHTML.trim();
			
			let formattedItemLabel = itemLabel;
			if (this.#showIconOnly) {
				const icon = item.querySelector('i');
				if (icon) {
					formattedItemLabel = icon.outerHTML;
				}
			}
			
			const doesSwitcherCanBeUpdated = (
				(
					selectedTheme === null
					&& itemTheme === currentTheme
					&& buttonTheme !== currentTheme
				)
				|| (
					selectedTheme !== null
					&& itemTheme === selectedTheme
					&& buttonTheme !== selectedTheme
				)
			);
			
			if (doesSwitcherCanBeUpdated) {
				const newTheme = selectedTheme ? selectedTheme : currentTheme;
				
				// Update button data and HTML label
				buttonEl.setAttribute('data-theme', newTheme);
				
				if (buttonElSpanLarge && buttonElSpanSmall) {
					buttonElSpanLarge.innerHTML = DOMPurify.sanitize(formattedItemLabel);
					buttonElSpanSmall.innerHTML = DOMPurify.sanitize(itemLabel);
				} else {
					// Sanitization of the HTML to prevent potential XSS attacks by escaping unsafe HTML.
					// DOMPurify is used for better security.
					buttonEl.innerHTML = DOMPurify.sanitize(formattedItemLabel);
				}
				
				// Remove active class from all items
				menuItemsEls.forEach(i => i.classList.remove('active'));
				
				// Add active class to selected item
				item.classList.add('active');
			}
		});
	}
	
	/**
	 * Handle system theme changes
	 * @private
	 * @param {MediaQueryListEvent} e - The media query change event
	 */
	#handleSystemThemeChange(e) {
		// Only update if no user (database) or cookie preference is set, or if either is 'system'
		if (this.#isLoggedUser && this.#userPreference !== null && this.#userPreference !== 'system') {
			return;
		}
		
		if (this.#cookieManager.hasCookie(this.#cookieName) && this.#cookieManager.getCookie(this.#cookieName) !== 'system') {
			return;
		}
		
		this.#updateTheme();
		
		// [Business Code]
		// If the theme switcher is available, then update its UI.
		const themeSwitcherEl = document.getElementById('themeSwitcher');
		if (themeSwitcherEl) {
			const buttonEl = themeSwitcherEl.querySelector('.dropdown-toggle');
			if (buttonEl) {
				const selectedTheme = buttonEl.getAttribute('data-theme');
				this.updateThemeSwitcher(themeSwitcherEl, selectedTheme);
			}
		}
	}
	
	/**
	 * Set user theme preference (cookie or database) and update theme
	 * @param {string} theme - 'dark', 'light', or 'system'
	 * @param {Function} [saveToDatabase] - Optional callback to save preference to database
	 */
	setUserTheme(theme, saveToDatabase) {
		if (!this.#validPreferences.includes(theme)) {
			throw new Error("Theme must be " + this.#validPreferences.join(', ') + ".");
		}
		
		if (theme === 'system') {
			if (this.#isSystemThemeEnabled) {
				this.#cookieManager.removeCookie(this.#cookieName);
				if (this.#isLoggedUser && saveToDatabase) {
					saveToDatabase('system'); // Save to database
					this.#userPreference = 'system'; // Update local preference
				}
			}
		} else {
			this.#cookieManager.setCookie(this.#cookieName, theme, this.#cookieExpiresMinutes);
			if (this.#isLoggedUser && saveToDatabase) {
				saveToDatabase(theme); // Save to database
				this.#userPreference = theme; // Update local preference
			}
		}
		
		this.#updateTheme();
	}
	
	/**
	 * Save user preference to database (for logged-in users)
	 * @param {string} theme - 'dark', 'light', or 'system'
	 * @param {Function} saveToDatabase - Callback to save preference to database
	 */
	saveUserPreference(theme, saveToDatabase) {
		if (!this.#isLoggedUser) {
			throw new Error('Cannot save user preference: user is not logged in.');
		}
		if (!saveToDatabase) {
			throw new Error('A saveToDatabase callback is required.');
		}
		if (!this.#validPreferences.includes(theme)) {
			throw new Error("Theme must be " + this.#validPreferences.join(', ') + ".");
		}
		
		saveToDatabase(theme);
		this.#userPreference = theme; // Update local userPreference
		
		this.#updateTheme();
	}
}

// Export for module-based projects or attach to window for global use
if (typeof module !== 'undefined' && module.exports) {
	module.exports = ThemeDetector;
} else {
	window.ThemeDetector = ThemeDetector;
}

// Usage example:
// const cookieManager = new CookieManager({ expires: 60, path: '/', secure: true, sameSite: 'Strict' });
// const config = {
//     isDarkThemeEnabled: true,
//     isLoggedUser: true,
//     userPreference: 'dark',
//     cookieName: 'custom_theme',
//     cookieExpiresMinutes: 30 * 24 * 60 // 30 days
// };

// Check dark theme without updating
// const isDark = ThemeDetector.checkDarkTheme(cookieManager, config);
// console.log(isDark); // true or false, use for third-party plugins

// Initialize ThemeDetector for theme management
// const theme = new ThemeDetector(cookieManager, config);
// theme.setUserTheme('light', (theme) => {
//     // Example: API call to save theme to database
//     fetch('/api/user/preferences', {
//         method: 'POST',
//         body: JSON.stringify({ theme })
//     });
// });
// theme.saveUserPreference('system', (theme) => {
//     // Example: API call to save theme to database
//     fetch('/api/user/preferences', {
//         method: 'POST',
//         body: JSON.stringify({ theme })
//     });
// });
