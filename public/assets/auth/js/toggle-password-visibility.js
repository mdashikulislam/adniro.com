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

function togglePasswordVisibility() {
	const passwordFields = document.querySelectorAll('.password-field');
	if (passwordFields.length <= 0) {
		return;
	}
	
	// Ensure translations are available
	const translations = window.authTranslations || {
		hide_password: 'Hide the password',
		show_password: 'Show the password',
		hide: 'Hide',
		show: 'Show'
	};
	
	passwordFields.forEach(field => {
		const input = field.querySelector('input[type="password"], input[type="text"]');
		const toggleLink = field.querySelector('.toggle-password-link');
		
		if (!input || !toggleLink) return;
		
		// Function to toggle password visibility
		const togglePassword = () => {
			const isHidden = input.type === 'password';
			input.type = isHidden ? 'text' : 'password';
			
			// Get the icon element (might be before or inside the link)
			const icon = toggleLink.querySelector('i.fa-regular') ||
				(toggleLink.previousElementSibling?.classList?.contains('fa-regular') ?
					toggleLink.previousElementSibling : null);
			
			if (icon) {
				icon.classList.toggle('fa-eye-slash', !isHidden);
				icon.classList.toggle('fa-eye', isHidden);
			}
			
			// Update tooltip if it exists
			if (toggleLink.hasAttribute('data-bs-title')) {
				toggleLink.setAttribute('data-bs-title',
					isHidden ? translations.hide_password : translations.show_password);
			}
			
			// Update text if toggle-text is present
			if (toggleLink.hasAttribute('data-toggle-text')) {
				toggleLink.textContent = isHidden ? translations.hide : translations.show;
			}
			
			// Update state
			toggleLink.setAttribute('data-password-state', isHidden ? 'visible' : 'hidden');
		};
		
		// Set initial state if not already set
		if (!toggleLink.hasAttribute('data-password-state')) {
			toggleLink.setAttribute('data-password-state', 'hidden');
			// Set initial text based on translations
			if (toggleLink.hasAttribute('data-toggle-text')) {
				toggleLink.textContent = translations.show;
			}
			if (toggleLink.hasAttribute('data-bs-title')) {
				toggleLink.setAttribute('data-bs-title', translations.show_password);
			}
		}
		
		// Event listener
		toggleLink.addEventListener('click', (e) => {
			e.preventDefault();
			togglePassword();
			
			// Reinitialize Bootstrap tooltip if it exists
			if (toggleLink.getAttribute('data-bs-toggle') === 'tooltip') {
				const tooltip = bootstrap.Tooltip.getInstance(toggleLink);
				if (tooltip) {
					tooltip.dispose();
					new bootstrap.Tooltip(toggleLink);
				}
			}
		});
	});
}

/* Initialize when DOM is loaded */
document.addEventListener('DOMContentLoaded', togglePasswordVisibility);
