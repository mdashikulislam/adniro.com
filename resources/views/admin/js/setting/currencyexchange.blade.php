<script>
	const currencyexchangeDriversSelectors = {!! $currencyexchangeDriversSelectorsJson !!};
	const currencyexchangeDriversSelectorsList = Object.values(currencyexchangeDriversSelectors);
	
	const activationElSelector = 'input[type="checkbox"][name="activation"]';
	const driverElSelector = 'select[name="driver"].select2_from_array';
	
	onDocumentReady((event) => {
		const activationEl = document.querySelector(activationElSelector);
		if (activationEl) {
			toggleExchangeFields(activationEl);
			activationEl.addEventListener("change", e => toggleExchangeFields(e.target));
		}
		
		const driverEl = document.querySelector(driverElSelector);
		if (driverEl) {
			getDriverFields(driverEl);
			$(driverEl).on("change", e => getDriverFields(e.target));
		}
	});
	
	function toggleExchangeFields(activationEl) {
		const action = activationEl.checked ? "show" : "hide";
		setElementsVisibility(action, ".ex-enabled");
		
		if (activationEl.checked) {
			const driverEl = document.querySelector(driverElSelector);
			if (driverEl) {
				getDriverFields(driverEl);
			}
		}
	}
	
	function getDriverFields(driverEl) {
		const selectedDriverSelector = currencyexchangeDriversSelectors[driverEl.value] ?? "";
		const driversSelectorsListToHide = currencyexchangeDriversSelectorsList.filter(item => item !== selectedDriverSelector);
		
		setElementsVisibility("hide", driversSelectorsListToHide);
		setElementsVisibility("show", selectedDriverSelector);
		
		// When a driver is selected but the CurrencyExchange add-on is disabled,
		// run the toggleExchangeFields() to hide all the drivers fields
		// Note: Don't call here toggleExchangeFields() if activationEl.checked === true
		const activationEl = document.querySelector(activationElSelector);
		if (activationEl) {
			if (!activationEl.checked) {
				toggleExchangeFields(activationEl);
			}
		}
	}
</script>
