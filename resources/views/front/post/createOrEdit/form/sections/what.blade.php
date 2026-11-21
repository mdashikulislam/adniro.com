{{-- adniro: what is being sold (category, listing type, title, description) --}}

{{-- category_id --}}
@php
	$categoryIdError = (isset($errors) && $errors->has('category_id')) ? ' is-invalid' : '';
	$catSelectionUrl = url('browsing/categories/select');

	$categoryId = old('category_id', $listingForm->categoryId);
	$categoryType = old('category_type', $listingForm->categoryType);

	$aModal = 'data-bs-toggle="modal"';
	$aHref = 'href="#browseCategories"';
	$aDataUrl = 'data-selection-url="' . $catSelectionUrl . '"';
	$aClass = 'class="modal-cat-link open-selection-url ' . linkClass() . '"';

	$customHtml = '<div id="catsContainer" class="form-control' . $categoryIdError . '">';
	$customHtml .= "<a {$aHref} {$aModal} {$aDataUrl} {$aClass}>";
	$customHtml .= t('select_a_category');
	$customHtml .= '</a>';
	$customHtml .= '</div>';
	$customHtml .= '<input type="hidden" name="category_id" id="categoryId" value="' . e($categoryId) . '">';
	$customHtml .= '<input type="hidden" name="category_type" id="categoryType" value="' . e($categoryType) . '">';
@endphp
@include('helpers.forms.fields.html', [
	'label'    => t('category'),
	'name'     => 'category_id', // <label for="name">
	'required' => true,
	'value'    => $customHtml,
])

{{-- post_type_id --}}
@if (config('settings.listing_form.show_listing_type'))
	@include('helpers.forms.fields.radio', [
		'label'           => t('type'),
		'id'              => 'postTypeId-',
		'name'            => 'post_type_id',
		'inline'          => true,
		'required'        => true,
		'options'         => $postTypes,
		'optionValueName' => 'id',
		'optionTextName'  => 'label',
		'value'           => $listingForm->postTypeId,
		'hint'            => t('post_type_hint'),
		'wrapper'         => ['id' => 'postTypeBloc'],
	])
@endif

{{-- title --}}
@include('helpers.forms.fields.text', [
	'label'       => t('title'),
	'name'        => 'title',
	'placeholder' => t('enter_your_title'),
	'required'    => true,
	'value'       => $listingForm->title,
	'hint'        => t('a_great_title_needs_at_least_60_characters'),
	'attributes'  => ['id' => 'title', 'aria-required' => 'true'],
])

{{-- description: rich editors render the real textarea as "{editor}_description" --}}
@php
	$descriptionEditor = config('settings.listing_form.wysiwyg_editor') ?: 'textarea';
	$descriptionEditor = view()->exists('helpers.forms.fields.' . $descriptionEditor) ? $descriptionEditor : 'textarea';
	$descriptionFieldId = ($descriptionEditor == 'textarea') ? 'description' : $descriptionEditor . '_description';
@endphp
@include('helpers.forms.fields.wysiwyg', [
	'label'       => t('Description'),
	'labelFor'    => $descriptionFieldId,
	'name'        => 'description',
	'placeholder' => t('enter_your_message'),
	'required'    => true,
	'value'       => $listingForm->description,
	'height'      => 350,
	'attributes'  => ['rows' => 15, 'aria-required' => 'true'],
	'hint'        => t('describe_what_makes_your_listing_unique'),
])
