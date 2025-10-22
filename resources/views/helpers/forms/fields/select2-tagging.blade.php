{{-- select2 tagging from array --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'select2-tagging';
	$type = 'select';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= [];
	$default ??= [];
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$options ??= [];
	$allowsMultiple ??= true;
	
	$pluginOptions ??= [];
	
	$defaultTagsLimit = (int)config('settings.listing_form.tags_limit', 15);
	$defaultTagsMinLength = (int)config('settings.listing_form.tags_min_length', 2);
	$defaultTagsMaxLength = (int)config('settings.listing_form.tags_max_length', 30);
	
	// Available themes (path => key)
	$themes = [
		'bootstrap5' => 'bootstrap-5',
		'bootstrap4' => 'bootstrap4',
		'bootstrap3' => 'bootstrap',
	];
	$dir = $pluginOptions['dir'] ?? config('lang.direction');
	$isRtlDirection = ($dir == 'rtl');
	$language = $pluginOptions['language'] ?? app()->getLocale();
	$tagsLimit = $pluginOptions['tagsLimit'] ?? $defaultTagsLimit;
	$tagsMinLength = $pluginOptions['tagsMinLength'] ?? $defaultTagsMinLength;
	$tagsMaxLength = $pluginOptions['tagsMaxLength'] ?? $defaultTagsMaxLength;
	$tokenSeparators = $pluginOptions['tokenSeparators'] ?? [',', ';', ':', '/', '\\', '#'];
	$invalidChars = $pluginOptions['invalidChars'] ?? [',', ';', '_', '/', '\\', '#'];
	$theme = $pluginOptions['theme'] ?? config('larapen.core.select2.theme', 'bootstrap5');
	$themeKey = $themes[$theme] ?? null;
	
	$name = $allowsMultiple ? $name . '[]' : $name;
	$multipleAttr = $allowsMultiple ? ' multiple' : '';
	
	$rootWildcardName = arrayFieldToDotNotation($name, true);
	$rootName = rtrim($rootWildcardName, '.*');
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	
	$value = $value ?? ($default ?? []);
	$value = old($dotSepName, $options);
	
	$hint = str_replace('{limit}', $tagsLimit, $hint);
	$hint = str_replace('{min}', $tagsMinLength, $hint);
	$hint = str_replace('{max}', $tagsMaxLength, $hint);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'select2-tagging');
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<select
					name="{{ $name }}"
					@if (!empty($placeholder))data-placeholder="{{ $placeholder }}"@endif
					style="width: 100%"
					@include('helpers.forms.attributes.field')
					{!! $multipleAttr !!}
			>
				@if (!empty($value) && is_array($value))
					@foreach ($value as $key => $label)
						<option selected="selected">{{ $label }}</option>
					@endforeach
				@endif
			</select>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
	$pluginBasePath = 'assets/plugins/select2/';
	$pluginFullPath = public_path($pluginBasePath);
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("select2_assets_styles")
	<link href="{{ asset($pluginBasePath . 'css/select2.min.css') }}" rel="stylesheet" type="text/css"/>
	@if ($theme == 'bootstrap5')
		<link href="{{ asset('assets/plugins/select2-bootstrap5-theme/1.3.0/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" type="text/css"/>
		@if ($isRtlDirection)
			<link href="{{ asset('assets/plugins/select2-bootstrap5-theme/1.3.0/select2-bootstrap-5-theme.rtl.min.css') }}" rel="stylesheet" type="text/css"/>
		@endif
	@elseif ($theme == 'bootstrap4')
		<link href="{{ asset('assets/plugins/select2-bootstrap4-theme/1.5.2/select2-bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>
	@elseif ($theme == 'bootstrap3')
		<link href="{{ asset('assets/plugins/select2-bootstrap3-theme/0.1.0-beta.10/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
	@else
		<link href="{{ asset('assets/plugins/select2/css/custom.css') }}" rel="stylesheet" type="text/css"/>
	@endif
@endpushonce

@php
	$localeFilesBasePath = $pluginBasePath . 'js/i18n/';
	$localeFilesFullPath = public_path($localeFilesBasePath);
	
	$foundLocale = '';
	if (file_exists($localeFilesFullPath . getLangTag($language) . '.js')) {
		$foundLocale = getLangTag($language);
	}
	if (empty($foundLocale)) {
		if (file_exists($localeFilesFullPath . strtolower($language) . '.js')) {
			$foundLocale = strtolower($language);
		}
	}
	if (empty($foundLocale)) {
		$foundLocale = 'en';
	}
@endphp

@pushonce("select2_assets_scripts")
	<script src="{{ asset($pluginBasePath . 'js/select2.full.min.js') }}"></script>
	@if ($foundLocale != 'en')
		<script src="{{ asset($localeFilesBasePath . $foundLocale . '.js') }}"></script>
	@endif
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		onDocumentReady((event) => {
			const lang = '{{ $foundLocale }}';
			const dir = '{{ $dir }}';
			const theme = {!! !empty($themeKey) ? "'{$themeKey}'" : 'undefined' !!};
			const rootName = '{{ $rootName }}';
			const fieldName = '{{ $dotSepName }}';
			const tagsMinLength = {{ $tagsMinLength }};
			const tagsMaxLength = {{ $tagsMaxLength }};
			const tagsLimit = {{ $tagsLimit }};
			const tokenSeparators = {!! Illuminate\Support\Js::from($tokenSeparators) !!};
			const invalidChars = {!! Illuminate\Support\Js::from($invalidChars) !!};
			
			let options1 = {
				lang: lang, {{-- No effect, use the "options.language" property --}}
				dir: dir,
			};
			
			if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
				options1.language = langLayout.select2;
			}
			if (typeof theme !== 'undefined') {
				options1.theme = theme;
			}
			
			const tagsEl = $('.select2-tagging');
			if (tagsEl.length) {
				tagsEl.each((index, element) => {
					if (!$(element).hasClass('select2-hidden-accessible')) {
						if (typeof theme !== 'undefined') {
							if (theme === 'bootstrap-5') {
								const widthOption = $(element).hasClass('w-100') ? '100%' : 'style';
								const width = $(element).data('width');
								options1.width = width ? width : widthOption;
								options1.placeholder = $(element).data('placeholder');
							}
						}
						
						if (typeof getSelect2OptionsWithOffcanvas === 'function') {
							options1 = getSelect2OptionsWithOffcanvas(element, options1);
						}
						
						$(element).select2(options1);
						
						/* Indicate that the value of this field has changed */
						$(element).on('select2:select', (e) => {
							element.dispatchEvent(new Event('input', {bubbles: true}));
						});
					}
				});
				
				let options2 = {
					lang: lang, {{-- No effect, use the "options.language" property --}}
					dir: dir,
					tags: true,
					maximumSelectionLength: tagsLimit,
					tokenSeparators: tokenSeparators,
					createTag: (params) => {
						const term = $.trim(params.term);
						
						{{-- Don't offset to create a tag if there is some symbols/characters --}}
						let arrayLength = invalidChars.length;
						for (let i = 0; i < arrayLength; i++) {
							let invalidChar = invalidChars[i];
							if (term.indexOf(invalidChar) !== -1) {
								return null;
							}
						}
						
						{{-- Don't offset to create empty tag --}}
						{{-- Return null to disable tag creation --}}
						if (term === '') {
							return null;
						}
						
						{{-- Don't allow tags which are less than 2 characters or more than 50 characters --}}
						if (term.length < tagsMinLength || term.length > tagsMaxLength) {
							return null;
						}
						
						return {
							id: term,
							text: term
						}
					}
				};
				
				if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
					options2.language = langLayout.select2;
				}
				if (typeof theme !== 'undefined') {
					options2.theme = theme;
				}
				
				if (typeof getSelect2OptionsWithOffcanvas === 'function') {
					options2 = getSelect2OptionsWithOffcanvas(tagsEl, options2);
				}
				
				{{-- Tagging with multi-value Select Boxes --}}
				const selectTagging = tagsEl.select2(options2);
				
				{{-- Apply tags limit --}}
				selectTagging.on('change', e => {
					const currEl = e.target;
					if ($(currEl).val().length > tagsLimit) {
						$(currEl).val($(currEl).val().slice(0, tagsLimit));
					}
				});
			}
			
			{{-- select2: If error occured, apply Bootstrap's error class --}}
			@if ($errorBag->has($rootWildcardName))
				const rootNameEl = $(`select[name^="${rootName}"]`);
				if (rootNameEl.length) {
					rootNameEl.closest('div').addClass('is-invalid');
					rootNameEl.next('.select2.select2-container').addClass('is-invalid');
				}
			@endif
		});
	</script>
@endpush
