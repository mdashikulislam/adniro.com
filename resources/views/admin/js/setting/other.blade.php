<script>
	let gmapsIntegrationTypesSelectors = {!! $gmapsIntegrationTypesSelectorsJson !!};
	let gmapsIntegrationTypesSelectorsList = Object.values(gmapsIntegrationTypesSelectors);
	
	onDocumentReady((event) => {
		let gmapsIntegrationEl = document.querySelector("select[name=google_maps_integration_type].select2_from_array");
		if (gmapsIntegrationEl) {
			gmapsIntegrationTypeFields(gmapsIntegrationEl);
			$(gmapsIntegrationEl).on("change", e => gmapsIntegrationTypeFields(e.target));
		}
	});
	
	function gmapsIntegrationTypeFields(gmapsIntegrationEl) {
		const selectedIntegrationTypeSelector = gmapsIntegrationTypesSelectors[gmapsIntegrationEl.value] ?? "";
		const integrationTypesSelectorsListToHide = gmapsIntegrationTypesSelectorsList.filter(item => item !== selectedIntegrationTypeSelector);
		
		setElementsVisibility("hide", integrationTypesSelectorsListToHide);
		setElementsVisibility("show", selectedIntegrationTypeSelector);
	}
</script>
