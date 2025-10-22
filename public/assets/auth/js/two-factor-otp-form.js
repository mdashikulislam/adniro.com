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

// OTP Form (Focusing on next input and auto-submit)
(function () {
	"use strict";
	
	// OTP Form handling
	const otpForm = document.getElementById('twoFactorOtpForm');
	const otpInputs = document.querySelectorAll('#otpInputs .form-control');
	const codeInput = document.getElementById('otpCode');
	const submitBtn = document.getElementById('otpButton');
	
	// Ensure translations are available
	const translations = window.authTranslations || {
		verify: 'Verify',
		submitting: 'Submitting...'
	};
	
	otpInputs.forEach((input, index) => {
		// Handle keyup for navigation and single character input
		input.addEventListener('keyup', function (e) {
			if (this.value.length === 0) {
				const prevInput = otpInputs[index - 1];
				if (prevInput) {
					prevInput.focus();
				}
			} else if (this.value.length === this.maxLength) {
				const nextInput = otpInputs[index + 1];
				if (nextInput) {
					nextInput.focus();
				} else {
					submitOtpForm();
				}
			}
			// Ensure only single digits
			if (this.value.length > 0) {
				this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
			}
		});
		
		// Handle paste event
		input.addEventListener('paste', function (e) {
			e.preventDefault();
			const pastedData = (e.clipboardData || window.clipboardData).getData('text');
			const digits = pastedData.replace(/[^0-9]/g, ''); // Only keep numbers
			
			// Calculate how many inputs remain from current position
			const remainingInputs = otpInputs.length - index;
			const codeToPaste = digits.slice(0, remainingInputs);
			
			// Fill inputs from current position
			for (let i = 0; i < codeToPaste.length; i++) {
				const targetInput = otpInputs[index + i];
				if (targetInput) {
					targetInput.value = codeToPaste[i];
				}
			}
			
			// Focus the last filled input or submit if at end
			const lastFilledIndex = Math.min(index + codeToPaste.length - 1, otpInputs.length - 1);
			otpInputs[lastFilledIndex].focus();
			
			if (lastFilledIndex === otpInputs.length - 1) {
				submitOtpForm();
			}
		});
	});
	
	function submitOtpForm() {
		if (!otpForm) return;
		
		// Check if form is already submitting
		if (otpForm.dataset.submitting === 'true') return;
		
		const allFilled = Array.from(otpInputs).every(input => input.value.length === 1);
		if (!allFilled) return;
		
		const code = Array.from(otpInputs).map(input => input.value).join('');
		
		if (codeInput) {
			codeInput.value = code;
		}
		
		// Disable button and submit
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = translations.submitting; // Optional: visual feedback
		}
		
		// Set data attribute before submission
		otpForm.dataset.submitting = 'true';
		otpForm.submit();
	}
	
	// Handle form submission
	// Updated submit handler to check data attribute
	if (otpForm) {
		otpForm.addEventListener('submit', function (e) {
			if (otpForm.dataset.submitting === 'true') {
				e.preventDefault(); // Prevent duplicate submission
				return;
			}
			
			const allFilled = Array.from(otpInputs).every(input => input.value.length === 1);
			if (!allFilled) {
				e.preventDefault();
				return;
			}
			
			const code = Array.from(otpInputs).map(input => input.value).join('');
			
			if (codeInput) {
				codeInput.value = code;
			}
			
			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.textContent = translations.submitting;
			}
			
			otpForm.dataset.submitting = 'true'; // Mark as submitting
			// No need to call otpForm.submit() here since it’s the natural submission
		});
	}
	
	// Reset data attribute and button state on page load
	window.addEventListener('load', function () {
		if (otpForm) {
			otpForm.dataset.submitting = 'false'; // Initialize/reset submitting state
		}
		if (submitBtn) {
			submitBtn.disabled = false;
			submitBtn.textContent = translations.verify;
		}
	});
	
	// Re-enable button if submission fails (optional)
	if (otpForm) {
		otpForm.addEventListener('submit', function (e) {
			// This assumes you're using AJAX or want to handle failed submissions
			// Remove if using standard form submission
			setTimeout(() => {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = translations.verify; // Reset to original text
				}
			}, 5000); // Adjust timeout as needed
		});
	}
	
})();

