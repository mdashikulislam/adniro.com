{{-- select2 from ajax --}}
@php
	use App\Helpers\Common\JsonUtils;
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'select2-ajax';
	$type = 'select';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$pluginOptions ??= [];
	
	// Available themes (path => key)
	$themes = [
		'bootstrap5' => 'bootstrap-5',
		'bootstrap4' => 'bootstrap4',
		'bootstrap3' => 'bootstrap',
	];
	$dir = $pluginOptions['dir'] ?? config('lang.direction');
	$isRtlDirection = ($dir == 'rtl');
	$language = $pluginOptions['language'] ?? app()->getLocale();
	$ajaxUrl = $pluginOptions['ajaxUrl'] ?? 'https://domain.tld/data-source-as-json';
	$minInputLength = $pluginOptions['minInputLength'] ?? 2;
	$selectedEntry = $pluginOptions['selectedEntry'] ?? [];
	$optionKeyName = $pluginOptions['optionKeyName'] ?? 'id';
	$optionLabelName = $pluginOptions['optionLabelName'] ?? 'name';
	$theme = $pluginOptions['theme'] ?? config('larapen.core.select2.theme', 'bootstrap5');
	$themeKey = $themes[$theme] ?? null;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '_', $dotSepName);
	
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	
	$value = $value ?? ($default ?? false);
	$value = old($dotSepName, $value);
	
	$selectedEntry = JsonUtils::isJson($selectedEntry) ? json_decode($selectedEntry, true) : $selectedEntry;
	
	$entryId = data_get($selectedEntry, $optionKeyName);
	$entryLabel = data_get($selectedEntry, $optionLabelName);
	
	$selectedEntry = ($value == $entryId) ? $selectedEntry : [];
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'select2-ajax');
@endphp

<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<select
					id="select2_ajax_{{ $id }}"
					name="{{ $name }}"
					@if (!empty($placeholder))data-placeholder="{{ $placeholder }}"@endif
					style="width: 100%"
					@include('helpers.forms.attributes.field')
			>
				@if (!empty($selectedEntry))
					<option value="{{ $entryId }}" selected>
						{{ $entryLabel }}
					</option>
				@else
					@if (!empty($placeholder))
						<option value="">{{ $placeholder }}</option>
					@endif
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
			const languageCode = '{{ $language }}';
			const lang = '{{ $foundLocale }}';
			const dir = '{{ $dir }}';
			const theme = {!! !empty($themeKey) ? "'{$themeKey}'" : 'undefined' !!};
			const ajaxUrl = '{{ $ajaxUrl }}';
			const placeholder = '{{ $placeholder }}';
			const minInputLength = '{{ $minInputLength }}';
			const optionKeyName = '{{ $optionKeyName }}';
			const optionLabelName = '{{ $optionLabelName }}';
			
			let options = {
				lang: lang, {{-- No effect, use the "options.language" property --}}
				dir: dir,
				multiple: false,
				placeholder: placeholder,
				minimumInputLength: minInputLength,
				ajax: {
					url: ajaxUrl,
					dataType: 'json',
					delay: 50,
					quietMillis: 250,
					data: (params) => {
						return {
							languageCode: languageCode,
							q: params.term, /* Search Term */
							page: params.page || 1
						};
					},
					processResults: (data, params) => {
						/*
						 // parse the results into the format expected by Select2
						 // since we are using custom formatting functions we do not need to
						 // alter the remote JSON data, except to indicate that infinite
						 // scrolling can be used
						 */
						params.page = params.page || 1;
						
						return {
							results: $.map(data.data, function (item) {
								return {
									text: item[optionLabelName],
									id: item[optionKeyName]
								}
							}),
							pagination: {
								more: data.current_page < data.last_page
							},
						};
					},
					error: function (jqXHR, status, error) {
						bsModalAlert(jqXHR, error);
						
						return { results: [] }; /* Return dataset to load after error */
					},
					cache: true
				},
			};
			
			if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
				options.language = langLayout.select2;
			}
			if (typeof theme !== 'undefined') {
				options.theme = theme;
			}
			
			const select2ElsSelector = '#select2_ajax_{{ $id }}';
			const select2Els = $(select2ElsSelector);
			if (select2Els.length) {
				select2Els.each((index, element) => {
					if (!$(element).hasClass('select2-hidden-accessible')) {
						if (typeof theme !== 'undefined') {
							if (theme === 'bootstrap-5') {
								const widthOption = $(element).hasClass('w-100') ? '100%' : 'style';
								const width = $(element).data('width');
								options.width = width ? width : widthOption;
								options.placeholder = $(element).data('placeholder');
							}
						}
						
						if (typeof getSelect2OptionsWithOffcanvas === 'function') {
							options = getSelect2OptionsWithOffcanvas(element, options);
						}
						
						$(element).select2(options);
						
						/* Indicate that the value of this field has changed */
						$(element).on('select2:select', (e) => {
							element.dispatchEvent(new Event('input', {bubbles: true}));
						});
					}
				});
				
				{{-- select2: If error occured, apply Bootstrap's error class --}}
				@if ($errorBag->has($dotSepName))
					select2Els.closest('div').addClass('is-invalid');
				@endif
			}
		});
	</script>
@endpush
