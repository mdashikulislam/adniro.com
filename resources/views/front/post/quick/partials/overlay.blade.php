{{-- Quick post: drafting overlay (adniro:ai) --}}
	<div id="qpOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none align-items-center justify-content-center"
     style="background: rgba(0,0,0,.55); z-index: 2000;" aria-live="polite">
	<div class="bg-body rounded p-4 text-center shadow" style="max-width: 320px;">
		<div class="spinner-border text-primary mb-3" role="status"></div>
		<p class="mb-0 fw-bold" id="qpProgress">{{ trans('ai.qp_progress_1') }}</p>
	</div>
</div>
