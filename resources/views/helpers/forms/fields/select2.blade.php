{{-- select2 from array --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'select2';
	$type = 'select'; // select2
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$options ??= [];
	$optionValueName ??= 'value';
	$optionTextName ??= 'text';
	$largeSize = 30;
	$largeOptions ??= (is_array($options) && count($options) >= $largeSize);
	$allowsMultiple ??= false;
	
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
	$theme = $pluginOptions['theme'] ?? config('larapen.core.select2.theme', 'bootstrap5');
	$themeKey = $themes[$theme] ?? null;
	
	$name = $allowsMultiple ? $name . '[]' : $name;
	$multipleAttr = $allowsMultiple ? ' multiple' : '';
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '_', $dotSepName);
	
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$select2Class = $largeOptions ? 'select2-from-large-array' : 'select2-from-array';
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', $select2Class);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<select
					id="{{ $id }}"
					name="{{ $name }}"
					@if (!empty($placeholder))data-placeholder="{{ $placeholder }}"@endif
					style="width: 100%"
					@include('helpers.forms.attributes.field')
					{!! $multipleAttr !!}
			>
				@if (!empty($placeholder))
					<option value="" @selected(empty($value))>
						{{ $placeholder }}
					</option>
				@endif
				@if (!empty($options) && is_array($options))
					@foreach ($options as $key => $option)
						@php
							$optionValue = $option[$optionValueName] ?? null;
							$optionText = $option[$optionTextName] ?? null;
							$optionAttrs = $option['attributes'] ?? [];
							$optionAttrsStr = \App\Helpers\Common\Arr::toAttributes($optionAttrs);
							$optionAttrsStr = !empty($optionAttrsStr) ? ' ' . $optionAttrsStr : '';
							
							$isSelected = (
								$optionValue == $value ||
								(is_array($value) && in_array($optionValue, $value))
							);
						@endphp
						<option value="{{ $optionValue }}"{!! $optionAttrsStr !!} @selected($isSelected)>
							{!! $optionText !!}
						</option>
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
@pushonce("{$viewName}_assets_styles")
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

@pushonce("{$viewName}_assets_scripts")
	<script src="{{ asset($pluginBasePath . 'js/select2.full.min.js') }}"></script>
	@if ($foundLocale != 'en')
		<script src="{{ asset($localeFilesBasePath . $foundLocale . '.js') }}"></script>
	@endif
@endpushonce

{{-- include field specific assets code --}}
@pushonce("select2_basic_assets_scripts")
	<script>
		onDocumentReady((event) => {
			const lang = '{{ $foundLocale }}';
			const dir = '{{ $dir }}';
			const theme = {!! !empty($themeKey) ? "'{$themeKey}'" : 'undefined' !!};
			const select2Els = $('.select2-from-array');
			const largeSelect2Els = $('.select2-from-large-array');
			
			{{-- Simple select boxes --}}
			let options = {
				lang: lang, {{-- No effect, use the "options.language" property --}}
				dir: dir,
				width: '100%',
				dropdownAutoWidth: 'true',
				minimumResultsForSearch: Infinity, {{-- Hiding the search box --}}
			};
			
			if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
				options.language = langLayout.select2;
			}
			if (typeof theme !== 'undefined') {
				options.theme = theme;
			}
			
			/* Non-searchable select boxes */
			if (select2Els.length) {
				select2Els.each((index, element) => {
					if (!$(element).hasClass('select2-hidden-accessible')) {
						if (typeof theme !== 'undefined') {
							if (theme === 'bootstrap-5') {
								let widthOption = $(element).hasClass('w-100') ? '100%' : 'style';
								options.width = $(element).data('width') ? $(element).data('width') : widthOption;
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
			}
			
			/* Searchable select boxes */
			if (largeSelect2Els.length) {
				largeSelect2Els.each((index, element) => {
					if (!$(element).hasClass('select2-hidden-accessible')) {
						if (typeof theme !== 'undefined') {
							if (theme === 'bootstrap-5') {
								const widthOption = $(element).hasClass('w-100') ? '100%' : 'style';
								const width = $(element).data('width');
								options.width = width ? width : widthOption;
								options.placeholder = $(element).data('placeholder');
							}
						}
						
						delete options.minimumResultsForSearch;
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
			}
		});
	</script>
@endpushonce

@push("{$viewName}_helper_scripts")
	<script>
		onDocumentReady((event) => {
			{{-- select2: If error occured, apply Bootstrap's error class --}}
			@if ($errorBag->has($dotSepName))
				$('select[name="{{ $name }}"]').closest('div').addClass('is-invalid');
			@endif
		});
	</script>
@endpush
