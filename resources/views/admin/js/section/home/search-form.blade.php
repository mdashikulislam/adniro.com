<script>
	onDocumentReady((event) => {
		const enableExtendedFormAreaEl = document.querySelector("input[type=checkbox][name=enable_extended_form_area]");
		if (enableExtendedFormAreaEl) {
			toggleExtendedFormAreaFields(enableExtendedFormAreaEl);
			enableExtendedFormAreaEl.addEventListener("change", e => toggleExtendedFormAreaFields(e.target));
		}
		
		const fullHeightEl = document.querySelector("input[type=checkbox][name=full_height]");
		if (fullHeightEl) {
			toggleFullHeightFields(fullHeightEl);
			fullHeightEl.addEventListener("change", e => toggleFullHeightFields(e.target));
		}
	});
	
	function toggleExtendedFormAreaFields(extFormEl) {
		const action = extFormEl.checked ? "show" : "hide";
		setElementsVisibility(action, ".extended");
		
		const tabContainerEl = document.querySelector(".tab-container");
		if (tabContainerEl) {
			const classToAdd = extFormEl.checked ? "d-flex" : "d-none";
			removeClassesFromElement(tabContainerEl, "d-flex d-none");
			toggleElementsClass(tabContainerEl, 'add', classToAdd);
		}
		
		const fullHeightEl = document.querySelector("input[type=checkbox][name=full_height]");
		if (fullHeightEl) {
			toggleFullHeightFields(fullHeightEl);
		}
	}
	
	function toggleFullHeightFields(extFormEl) {
		const action = extFormEl.checked ? "show" : "hide";
		setElementsVisibility(action, ".full-height");
	}
</script>
