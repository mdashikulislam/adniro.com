{{-- adniro: the seller's contact details --}}
@unless ($lfV2 ?? false)
	<div class="my-4 col-md-12">
		<h5 class="listing-form-section">
			<span class="listing-form-section-icon"><i class="bi bi-person-fill"></i></span>
			<span>{{ t('seller_information') }}</span>
		</h5>
	</div>
@endunless

{{-- contact_name: a signed-in member posts under their own name --}}
@if ($listingForm->contactNameIsHidden)
	<input id="contactName" name="contact_name" type="hidden" value="{{ $listingForm->contactName }}">
@else
	@include('helpers.forms.fields.text', [
		'label'       => t('your_name'),
		'id'          => 'contactName',
		'name'        => 'contact_name',
		'placeholder' => t('enter_your_name'),
		'required'    => true,
		'value'       => $listingForm->contactName,
		'prefix'      => '<i class="fa-regular fa-user"></i>',
		'suffix'      => null,
		'attributes'  => ['id' => 'contactName', 'aria-required' => 'true'],
		'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
	])
@endif

{{-- auth_field (as notification channel) --}}
@php
	$authFields = getAuthFields(true);
	$authFieldOptions = collect($authFields)
		->map(fn($item, $key) => ['value' => $key, 'text' => $item])
		->toArray();

	$usersCanChooseNotifyChannel = isUsersCanChooseNotifyChannel();
	$authFieldValue = $usersCanChooseNotifyChannel
		? old('auth_field', $listingForm->authField)
		: $listingForm->authField;
@endphp
@if ($usersCanChooseNotifyChannel)
	@include('helpers.forms.fields.radio', [
		'label'      => trans('auth.notifications_channel'),
		'btnVariant' => 'secondary',
		'btnOutline' => true,
		'id'         => 'authField-',
		'name'       => 'auth_field',
		'inline'     => true,
		'required'   => true,
		'options'    => $authFieldOptions,
		'value'      => $authFieldValue,
		'attributes' => ['class' => 'auth-field-input'],
		'hint'       => trans('auth.notifications_channel_hint'),
	])
@else
	<input id="authField-{{ $authFieldValue }}" name="auth_field" type="hidden" value="{{ $authFieldValue }}">
@endif

@php
	$forceToDisplay = isBothAuthFieldsCanBeDisplayed() ? ' force-to-display' : '';
	$isEmailRequired = (getAuthField() == 'email');
@endphp

{{-- email --}}
@include('helpers.forms.fields.email', [
	'label'       => trans('auth.email'),
	'id'          => 'email',
	'name'        => 'email',
	'required'    => $isEmailRequired,
	'placeholder' => t('enter_your_email'),
	'value'       => $listingForm->email,
	'prefix'      => '<i class="fa-regular fa-envelope"></i>',
	'suffix'      => null,
	'attributes'  => $isEmailRequired ? ['id' => 'email', 'aria-required' => 'true'] : ['id' => 'email'],
	'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
	'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
])

{{-- phone --}}
@php
	$phoneHiddenValue = old('phone_hidden', $listingForm->phoneHidden);
	$phoneHiddenChecked = ($phoneHiddenValue == '1') ? ' checked' : '';
	if ($lfV2 ?? false) {
		$itiSuffix = '<label class="form-check form-switch lf-suffix-switch" for="phoneHidden">';
		$itiSuffix .= '<input class="form-check-input" id="phoneHidden" name="phone_hidden" type="checkbox" role="switch" value="1"' . $phoneHiddenChecked . '>';
		$itiSuffix .= '<span class="form-check-label">' . e(t('Hide')) . '</span></label>';
	} else {
		$itiSuffix = '<input id="phoneHidden" name="phone_hidden" type="checkbox" value="1"' . $phoneHiddenChecked . '>';
		$itiSuffix .= '&nbsp;<small>' . t('Hide') . '</small>';
	}
@endphp
@include('helpers.forms.fields.intl-tel-input', [
	'label'       => trans('auth.phone_number'),
	'id'          => 'phone',
	'name'        => 'phone',
	'required'    => (getAuthField() == 'phone'),
	'placeholder' => null,
	'value'       => $listingForm->phone,
	'countryCode' => $listingForm->phoneCountry,
	'suffix'      => $itiSuffix,
	'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
	'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
])

{{-- auto_registration (guests creating a listing) --}}
@if (!$listingForm->isEdit && !auth()->check())
	@if (in_array(config('settings.listing_form.auto_registration'), [1, 2]))
		@if (config('settings.listing_form.auto_registration') == 1)
			@include('helpers.forms.fields.checkbox', [
				'label'    => t('I want to register by submitting this listing'),
				'name'     => 'auto_registration',
				'required' => false,
				'value'    => 1,
				'hint'     => t('You will receive your authentication information by email'),
			])
		@else
			<input type="hidden" name="auto_registration" id="auto_registration" value="1">
		@endif
	@endif
@endif
