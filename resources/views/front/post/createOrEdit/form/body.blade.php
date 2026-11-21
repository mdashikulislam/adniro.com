{{--
 * adniro: the listing form shared by the four create/edit pages (single-step and multi-step),
 * included by form/page.blade.php inside <form id="payableForm"> with:
 *   listingForm  App\Services\ListingForm\ListingFormState
 *   isSingleStep bool
 *   lfV2         bool  the redesigned layout (numbered section cards)
 *   actions      ['backUrl', 'backLabel' (HTML), 'submitId', 'submitLabel' (HTML)]
 *
 * Field names and element IDs are a contract with the server and with d.modal.category.js,
 * d.modal.location.js, package-payment.js and ai.formbox.js (tests/Feature/ListingForm).
--}}
@php
	$lfV2 ??= false;
	$hasPhotoField = $isSingleStep && isset($picturesLimit) && is_numeric($picturesLimit) && $picturesLimit > 0;
	$hasFinish = $isSingleStep || !$listingForm->isEdit;
	$card = 'front.post.createOrEdit.form.section-card';
	$section = 'front.post.createOrEdit.form.sections.';
	$moreOpen = !empty(old('tags', $listingForm->tags)) || (isset($errors) && $errors->hasAny(['tags', 'tags.*', 'is_permanent']));
@endphp
@if ($lfV2)

	@includeWhen(!$listingForm->isEdit, 'front.post.assistant.form-box')

	@component($card, ['key' => 'what', 'title' => trans('listing_form.section_what'), 'sub' => trans('listing_form.section_what_sub')])
		@include($section . 'what')
	@endcomponent

	@if ($hasPhotoField)
		@component($card, ['key' => 'photos', 'title' => trans('listing_form.section_photos'), 'sub' => trans('listing_form.section_photos_sub')])
			@include($section . 'photos')
		@endcomponent
	@endif

	{{-- hidden by listing-form.css until d.modal.category.js loads the category's fields --}}
	@component($card, ['key' => 'details', 'title' => trans('listing_form.section_details'), 'sub' => trans('listing_form.section_details_sub')])
		@include($section . 'details')
	@endcomponent

	@component($card, ['key' => 'price-location', 'title' => trans('listing_form.section_price_location'), 'sub' => trans('listing_form.section_price_location_sub')])
		@include($section . 'price-location')
	@endcomponent

	@component($card, ['key' => 'more', 'title' => trans('listing_form.section_more'), 'sub' => trans('listing_form.section_more_sub'), 'collapsible' => true, 'open' => $moreOpen])
		@include($section . 'more')
	@endcomponent

	@component($card, ['key' => 'contact', 'title' => trans('listing_form.section_contact'), 'sub' => trans('listing_form.section_contact_sub')])
		@include($section . 'contact')
	@endcomponent

	@if ($hasFinish)
		@component($card, ['key' => 'finish', 'title' => trans('listing_form.section_finish'), 'sub' => trans('listing_form.section_finish_sub')])
			@include($section . 'finish')
		@endcomponent
	@endif

	@include('front.post.createOrEdit.form.actions')

@else
	<div class="row">

		@includeWhen(!$listingForm->isEdit, 'front.post.assistant.form-box')

		@include($section . 'what')

		@includeWhen($isSingleStep, $section . 'photos')

		@include($section . 'details')

		@include($section . 'price-location')

		@include($section . 'more')

		@include($section . 'contact')

		@include($section . 'finish')

		@include('front.post.createOrEdit.form.actions')

	</div>
@endif
