<script>
	onDocumentReady(function(event) {
		let headerFixedTopEl = document.querySelector("input[type=checkbox][name=fixed_top]");
		if (headerFixedTopEl) {
			toggleFixedHeaderFields(headerFixedTopEl, event.type);
			headerFixedTopEl.addEventListener("change", e => toggleFixedHeaderFields(e.target, e.type));
		}
		
		let headerStaticRecopyDefaultEl = document.querySelector("input[type=checkbox][name=static_recopy_default]");
		if (headerStaticRecopyDefaultEl) {
			toggleStaticHeaderFields(headerStaticRecopyDefaultEl, event.type);
			headerStaticRecopyDefaultEl.addEventListener("change", e => toggleStaticHeaderFields(e.target, e.type));
		}
	});
	
	function toggleFixedHeaderFields(element) {
		if (!element) return;
		
		let action = element.checked ? "show" : "hide";
		setElementsVisibility(action, ".fixed-header");
	}
	
	function toggleStaticHeaderFields(element) {
		if (!element) return;
		
		let action = !element.checked ? "show" : "hide";
		setElementsVisibility(action, ".static-header");
	}
</script>
