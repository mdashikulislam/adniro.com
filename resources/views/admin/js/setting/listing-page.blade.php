<script>
	const enableWhatsappBtnElSelector = "input[type=checkbox][name=enable_whatsapp_btn]";
	const hidePhoneNumberElSelector = "select[name=hide_phone_number].select2_from_array";
	
	const activatingWhatsappBtn = `{{ trans('admin.activating_whatsapp_btn') }}`;
	const activatingHidePhoneNumber = `{{ trans('admin.activating_hide_phone_number') }}`;
	
	onDocumentReady((event) => {
		let showSecurityTipsEl = document.querySelector("input[type=checkbox][name=show_security_tips]");
		if (showSecurityTipsEl) {
			toggleSecurityTipsFields(showSecurityTipsEl);
			showSecurityTipsEl.addEventListener("change", e => toggleSecurityTipsFields(e.target));
		}
		
		let enableWhatsappBtnEl = document.querySelector(enableWhatsappBtnElSelector);
		if (enableWhatsappBtnEl) {
			toggleWhatsappBtnFields(enableWhatsappBtnEl);
			enableWhatsappBtnEl.addEventListener("change", e => toggleWhatsappBtnFields(e.target));
		}
		
		let hidePhoneNumberEl = document.querySelector(hidePhoneNumberElSelector);
		if (hidePhoneNumberEl) {
			disableWhatsappBtn(hidePhoneNumberEl);
			$(hidePhoneNumberEl).on("change", e => disableWhatsappBtn(e.target));
		}
		
		let hideDateEl = document.querySelector("input[type=checkbox][name=hide_date]");
		if (hideDateEl) {
			toggleDateFields(hideDateEl);
			hideDateEl.addEventListener("change", e => toggleDateFields(e.target));
		}
		
		let similarListingsEl = document.querySelector("select[name=similar_listings].select2_from_array");
		if (similarListingsEl) {
			toggleSimilarListingsFields(similarListingsEl);
			$(similarListingsEl).on("change", e => toggleSimilarListingsFields(e.target));
		}
	});
	
	function toggleSecurityTipsFields(showSecurityTipsEl) {
		let action = !showSecurityTipsEl.checked ? "show" : "hide";
		setElementsVisibility(action, ".security-tips-field");
	}
	
	function toggleWhatsappBtnFields(enableWhatsappBtnEl) {
		let action = enableWhatsappBtnEl.checked ? "show" : "hide";
		setElementsVisibility(action, ".whatsapp-btn-field");
		unsetHidePhoneNumber(enableWhatsappBtnEl);
	}
	
	function disableWhatsappBtn(hidePhoneNumberEl) {
		if (!hidePhoneNumberEl) return;
		
		let enableWhatsappBtnEl = document.querySelector(enableWhatsappBtnElSelector);
		if (!enableWhatsappBtnEl) return;
		
		if (
			!(hidePhoneNumberEl.value === 0 || hidePhoneNumberEl.value === "0")
			&& enableWhatsappBtnEl.checked
		) {
			enableWhatsappBtnEl.checked = false;
			
			pnotifyAlertClient("notice", activatingHidePhoneNumber);
		}
	}
	
	function unsetHidePhoneNumber(enableWhatsappBtnEl) {
		if (!enableWhatsappBtnEl) return;
		
		let hidePhoneNumberEl = document.querySelector(hidePhoneNumberElSelector);
		if (!hidePhoneNumberEl) return;
		
		if (
			enableWhatsappBtnEl.checked
			&& !(hidePhoneNumberEl.value === 0 || hidePhoneNumberEl.value === "0")
		) {
			hidePhoneNumberEl.value = "0";
			/*
			 * Trigger Change event when the Input value changed programmatically (for select2)
			 * https://stackoverflow.com/a/36084475
			 */
			hidePhoneNumberEl.dispatchEvent(new Event("change"));
			
			pnotifyAlertClient("info", activatingWhatsappBtn);
		}
	}
	
	function toggleDateFields(hideDateEl) {
		let action = !hideDateEl.checked ? "show" : "hide";
		setElementsVisibility(action, ".date-field");
	}
	
	function toggleSimilarListingsFields(similarListingsEl) {
		setElementsVisibility("hide", ".similar-listings-field");
		if (similarListingsEl.value !== 0 && similarListingsEl.value !== '0') {
			setElementsVisibility("show", ".similar-listings-field");
		}
	}
</script>
