{{-- adniro: tags and the permanent-listing switch --}}

{{-- tags --}}
@php
	$tagHint = ($lfV2 ?? false)
		? trans('listing_form.hint_tags')
		: t('tags_hint', ['limit' => '{limit}', 'min' => '{min}', 'max' => '{max}']);
@endphp
@include('helpers.forms.fields.select2-tagging', [
	'label'       => t('Tags'),
	'id'          => 'tags',
	'name'        => 'tags',
	'placeholder' => t('enter_tags'),
	'options'     => $listingForm->tags,
	'hint'        => $tagHint,
])

{{-- is_permanent: d.modal.category.js shows #isPermanentBox for the categories that allow it --}}
@if (config('settings.listing_form.permanent_listings_enabled') == '3')
	<input id="isPermanent"
	       name="is_permanent"
	       type="hidden"
	       value="{{ $listingForm->isEdit ? old('is_permanent', $listingForm->isPermanent) : 0 }}"
	>
@else
	@include('helpers.forms.fields.checkbox', [
		'label'    => t('is_permanent_label'),
		'id'       => 'isPermanent',
		'name'     => 'is_permanent',
		'switch'   => true,
		'required' => false,
		'value'    => $listingForm->isPermanent,
		'hint'     => t('is_permanent_hint'),
		'wrapper'  => ['id' => 'isPermanentBox', 'class' => 'hide']
	])
@endif
