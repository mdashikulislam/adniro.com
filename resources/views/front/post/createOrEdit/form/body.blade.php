{{--
 * adniro: the listing form shared by the four create/edit pages (single-step and multi-step).
 * Each page keeps its own shell (layout, wizard, <form id="payableForm">, csrf, modals, assets)
 * and includes this with:
 *   listingForm  App\Services\ListingForm\ListingFormState
 *   isSingleStep bool
 *   actions      ['backUrl', 'backLabel' (HTML), 'submitId', 'submitLabel' (HTML)]
 *
 * Field names and element IDs are a contract with the server and with d.modal.category.js,
 * d.modal.location.js, package-payment.js and ai.formbox.js (tests/Feature/ListingForm).
--}}
<div class="row">

	@includeWhen(!$listingForm->isEdit, 'front.post.assistant.form-box')

	@include('front.post.createOrEdit.form.sections.what')

	@includeWhen($isSingleStep, 'front.post.createOrEdit.form.sections.photos')

	@include('front.post.createOrEdit.form.sections.details')

	@include('front.post.createOrEdit.form.sections.price-location')

	@include('front.post.createOrEdit.form.sections.more')

	@include('front.post.createOrEdit.form.sections.contact')

	@include('front.post.createOrEdit.form.sections.finish')

	@include('front.post.createOrEdit.form.actions')

</div>
