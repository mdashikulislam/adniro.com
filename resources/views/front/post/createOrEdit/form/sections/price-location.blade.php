{{-- adniro: price, country and city --}}

{{-- price: d.modal.category.js rewrites label[for=price] and hides #priceBloc for some categories --}}
@php
	$currencySymbol = config('currency.symbol', 'X');
	$price = old('price', $listingForm->price);
	$price = \App\Helpers\Common\Num::format($price, 2, '.', '');
	$isPriceMandatory = (config('settings.listing_form.price_mandatory') == '1');
	$priceHint = !$isPriceMandatory ? t('price_hint') : null;

	// negotiable
	$negotiable = old('negotiable', $listingForm->negotiable);
	$negotiableChecked = ($negotiable == '1') ? ' checked' : '';

	if ($lfV2 ?? false) {
		$priceSuffix = '<label class="form-check form-switch lf-suffix-switch" for="negotiable">';
		$priceSuffix .= '<input class="form-check-input" id="negotiable" name="negotiable" type="checkbox" role="switch" value="1"' . $negotiableChecked . '>';
		$priceSuffix .= '<span class="form-check-label">' . e(t('negotiable')) . '</span></label>';
	} else {
		$priceSuffix = '<input id="negotiable" name="negotiable" type="checkbox" value="1"' . $negotiableChecked . '>';
		$priceSuffix .= '&nbsp;<small>' . t('negotiable') . '</small>';
	}
@endphp
@include('helpers.forms.fields.number', [
	'label'       => t('price'),
	'name'        => 'price',
	'required'    => $isPriceMandatory,
	'placeholder' => t('enter_your_price'),
	'value'       => $price,
	'step'        => getInputNumberStep((int)config('currency.decimal_places', 2)),
	'prefix'      => $currencySymbol,
	'suffix'      => $priceSuffix,
	'hint'        => $priceHint,
	'attributes'  => $isPriceMandatory ? ['aria-required' => 'true'] : [],
	'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
	'wrapper'     => ['id' => 'priceBloc'],
])

{{-- country_code: an edit keeps the listing's country --}}
@if ($listingForm->isEdit)
	<input id="countryCode" name="country_code" type="hidden" value="{{ $listingForm->countryCode }}">
@elseif (empty(config('country.code')))
	@php
		$countryCodeOptions = collect($countries)
			->map(function($item) {
				return [
					'value'      => $item['code'] ?? null,
					'text'       => $item['name'] ?? null,
					'attributes' => ['data-admin-type' => $item['admin_type'] ?? 0],
				];
			})->toArray();
	@endphp
	@include('helpers.forms.fields.select2', [
		'label'       => t('your_country'),
		'id'          => 'countryCode',
		'name'        => 'country_code',
		'required'    => true,
		'placeholder' => t('select_a_country'),
		'options'     => $countryCodeOptions,
		'value'       => $listingForm->countryCode,
		'hint'        => null,
		'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
	])
@else
	<input id="countryCode" name="country_code" type="hidden" value="{{ config('country.code') }}">
@endif

@php
	$adminType = config('country.admin_type', 0);
@endphp
@if (config('settings.listing_form.city_selection') == 'select')
	@if (in_array($adminType, ['1', '2']))
		{{-- admin_code --}}
		@include('helpers.forms.fields.select2', [
			'label'        => t('location'),
			'id'           => 'adminCode',
			'name'         => 'admin_code',
			'required'     => true,
			'placeholder'  => t('select_your_location'),
			'options'      => [],
			'largeOptions' => true,
			'hint'         => null,
			'baseClass'    => ['wrapper' => 'mb-3 col-md-8'],
			'wrapper'      => ['id' => 'locationBox'],
		])
	@endif
@else
	{{-- filled by d.modal.location.js --}}
	<input type="hidden"
	       id="selectedAdminType"
	       name="selected_admin_type"
	       value="{{ old('selected_admin_type', $listingForm->adminType) }}"
	>
	<input type="hidden"
	       id="selectedAdminCode"
	       name="selected_admin_code"
	       value="{{ old('selected_admin_code', $listingForm->adminCode) }}"
	>
	<input type="hidden"
	       id="selectedCityId"
	       name="selected_city_id"
	       value="{{ old('selected_city_id', $listingForm->cityId) }}"
	>
	<input type="hidden"
	       id="selectedCityName"
	       name="selected_city_name"
	       value="{{ old('selected_city_name', $listingForm->cityName) }}"
	>
@endif

{{-- city_id --}}
@include('helpers.forms.fields.select2', [
	'label'        => t('city'),
	'id'           => 'cityId',
	'name'         => 'city_id',
	'required'     => true,
	'placeholder'  => t('select_a_city'),
	'options'      => [],
	'largeOptions' => true,
	'hint'         => null,
	'baseClass'    => ['wrapper' => 'mb-3 col-md-8'],
	'wrapper'      => ['id' => 'cityBox'],
])
