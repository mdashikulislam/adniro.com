<script>
	let formTypesSelectors = {!! $formTypesSelectorsJson !!};
	let formTypesSelectorsList = Object.values(formTypesSelectors);
	
	onDocumentReady((event) => {
		let formTypeEl = document.querySelector("select[name=publication_form_type].select2_from_array");
		if (formTypeEl) {
			getFormTypeFields(formTypeEl);
			$(formTypeEl).on("change", e => getFormTypeFields(e.target));
		}
		
		let utf8mb4EnabledEl = document.querySelector("input[type=checkbox][name=utf8mb4_enabled]");
		if (utf8mb4EnabledEl) {
			toggle4ByteCharsFields(utf8mb4EnabledEl);
			utf8mb4EnabledEl.addEventListener("change", e => toggle4ByteCharsFields(e.target));
		}
	});
	
	function getFormTypeFields(formTypeEl) {
		const selectedFormTypeSelector = formTypesSelectors[formTypeEl.value] ?? "";
		const formTypesSelectorsListToHide = formTypesSelectorsList.filter(item => item !== selectedFormTypeSelector);
		
		setElementsVisibility("hide", formTypesSelectorsListToHide);
		setElementsVisibility("show", selectedFormTypeSelector);
	}
	
	function toggle4ByteCharsFields(utf8mb4EnabledEl) {
		let action = utf8mb4EnabledEl.checked ? "show" : "hide";
		setElementsVisibility(action, ".utf8mb4-field");
	}
</script>
