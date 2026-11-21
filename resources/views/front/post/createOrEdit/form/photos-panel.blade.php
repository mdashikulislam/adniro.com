{{--
 * adniro: the listing form's side panel on the multi-step Photos step (create and edit): listing quality,
 * the live buyer preview and the tips, filled from the details saved in step 1 (summary.js), with the
 * photos on this page scored live. Expects $panelState from App\Services\ListingForm\PanelState.
--}}
@include('front.post.createOrEdit.form.panel', ['panelState' => $panelState])

@pushOnce('after_styles_stack', 'listing_form_panel_css')
	<link href="{{ url('assets/css/listing-form-panel.css') }}?v={{ @filemtime(public_path('assets/css/listing-form-panel.css')) }}" rel="stylesheet">
	<link href="{{ url('assets/css/listing-form-preview.css') }}?v={{ @filemtime(public_path('assets/css/listing-form-preview.css')) }}" rel="stylesheet">
@endPushOnce
@pushOnce('after_scripts_stack', 'listing_form_photos_panel_js')
	@foreach (['summary', 'strength-score', 'panel', 'preview'] as $panelScript)
		<script src="{{ url('assets/js/app/listing-form/' . $panelScript . '.js') }}?v={{ @filemtime(public_path('assets/js/app/listing-form/' . $panelScript . '.js')) }}"></script>
	@endforeach
@endPushOnce
