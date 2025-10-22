{{-- intl tel input --}}
{{-- https://github.com/jackocnr/intl-tel-input --}}
{{-- https://intl-tel-input.com/ --}}
@php
	use App\Helpers\Services\Referrer;
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'intl-tel-input';
	$type = 'tel'; // intl_tel_input
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$suffix ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$phonePlaceholderTypes = getPhonePlaceholderTypes();
	$defaultPlaceholderType = 'auto';
	$placeholderType ??= config('settings.sms.phone_placeholder_type');
	$placeholderType = !empty($placeholderType) ? $placeholderType : $defaultPlaceholderType;
	$placeholderType = array_key_exists($placeholderType, $phonePlaceholderTypes)
		? $placeholderType
		: $defaultPlaceholderType;
	$customPlaceholder = trans('auth.phone_number');
	$independentJs ??= false;
	
	$pluginOptions ??= [];
	
	$phoneHiddenInput = 'phone_intl';
	$countryHiddenInput = 'phone_country';
	
	$i18n = $pluginOptions['i18n'] ?? Referrer::getItiParameterData('i18n');
	$countrySearch = $pluginOptions['countrySearch'] ?? 'true';
	$hiddenInput = $pluginOptions['hiddenInput'] ?? ['phone' => $phoneHiddenInput, 'country' => $countryHiddenInput];
	$defaultInitialCountry = 'us';
	$initialCountry = $pluginOptions['countryCode'] ?? config('country.code');
	$initialCountry = !empty($initialCountry) ? $initialCountry : $defaultInitialCountry;
	$onlyCountries = $pluginOptions['onlyCountries'] ?? Referrer::getItiParameterData('onlyCountries');
	$countryOrder = $pluginOptions['countryOrder'] ?? [];
	$separateDialCode = $pluginOptions['separateDialCode'] ?? 'true';
	
	$phoneHiddenInput = $hiddenInput['phone'] ?? $phoneHiddenInput;
	$countryHiddenInput = $hiddenInput['country'] ?? $countryHiddenInput;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	$id = $independentJs ? 'iti_' . $id : $id;
	$dotSepCountryHiddenInput = arrayFieldToDotNotation($countryHiddenInput);
	
	$value = $value ?? ($default ?? null);
	$initialCountry = old($dotSepCountryHiddenInput, $initialCountry);
	$value = old($dotSepName, $value);
	$value = phoneE164($value, $initialCountry);
	
	$hasInputGroup = (!empty($prefix) || !empty($suffix));
	
	// Handle error class for "input-group"
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	
	$itiClass = !$independentJs ? 'iti-phone-number' : '';
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', $itiClass);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			@if (!empty($suffix))
				<div class="input-group {{ $isInvalidClass }}">
					@endif
					<input
							type="tel"
							id="{{ $id }}"
							name="{{ $name }}"
							value="{{ $value }}"
							autocomplete="off"
							@include('helpers.forms.attributes.field')
					>
					@if (!empty($suffix))
						<span class="input-group-text iti-group-text">{!! $suffix !!}</span>
					@endif
					@if (!empty($suffix))
				</div>
			@endif
			
			<input name="{{ $countryHiddenInput }}" type="hidden" value="{{ $initialCountry }}">
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	<link href="{{ asset('assets/plugins/intl-tel-input/25.3.1/css/intlTelInput.css') }}" rel="stylesheet" type="text/css"/>
	<link href="{{ asset('assets/plugins/intl-tel-input/25.3.1/css/custom.css') }}" rel="stylesheet" type="text/css"/>
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset('assets/plugins/intl-tel-input/25.3.1/js/intlTelInput.js') }}"></script>
	<script src="{{ asset('assets/plugins/intl-tel-input/25.3.1/js/custom.js') }}" defer></script>
@endpushonce

{{-- include field specific assets code --}}
@if ($independentJs)
	@push("{$viewName}_helper_scripts")
		<script>
			onDocumentReady((event) => {
				const itiElSelector = '{{ $id }}';
				const itiEl = document.getElementById(itiElSelector);
				const placeholderType = '{{ $placeholderType }}';
				const customPlaceholder = '{{ $customPlaceholder }}';
				
				if (itiEl) {
					// 'intl-tel-input' options
					const options = {
						i18n: {!! json_encode($i18n) !!},
						countrySearch: {{ $countrySearch }},
						hiddenInput: (telInputName) => ({
							phone: '{{ $phoneHiddenInput }}',
							country: '{{ $countryHiddenInput }}'
						}),
						initialCountry: '{{ $initialCountry }}',
						onlyCountries: {!! json_encode($onlyCountries) !!},
						countryOrder: [],
						separateDialCode: {{ $separateDialCode }},
					};
					
					// Initialization
					const iti = applyIntlTelInput(itiEl, options, placeholderType, customPlaceholder);
				}
			});
		</script>
	@endpush
@else
	@pushonce("shared_iti_assets_scripts")
		<script>
			onDocumentReady((event) => {
				const itiElsSelector = 'input.iti-phone-number:not([type=hidden])';
				const itiEls = document.querySelectorAll(itiElsSelector);
				const placeholderType = '{{ $placeholderType }}';
				const customPlaceholder = '{{ $customPlaceholder }}';
				
				if (itiEls.length) {
					// The 'intl-tel-input' options
					const options = {
						i18n: {!! json_encode($i18n) !!},
						countrySearch: {{ $countrySearch }},
						hiddenInput: (telInputName) => ({
							phone: '{{ $phoneHiddenInput }}',
							country: '{{ $countryHiddenInput }}'
						}),
						initialCountry: '{{ $initialCountry }}',
						onlyCountries: {!! json_encode($onlyCountries) !!},
						countryOrder: [],
						separateDialCode: {{ $separateDialCode }},
					};
					
					// Initialization (Multiple)
					let iti;
					itiEls.forEach((element) => {
						iti = applyIntlTelInput(element, options, placeholderType, customPlaceholder);
					});
				}
			});
		</script>
	@endpushonce
@endif
