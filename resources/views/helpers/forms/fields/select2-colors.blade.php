{{-- select2 color --}}
@php
	use App\Helpers\Common\JsonUtils;
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'select2-colors';
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
	
	$options ??= [];
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
	$skins = $pluginOptions['skins'] ?? [];
	
	$name = $allowsMultiple ? $name . '[]' : $name;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
	
	$value = $value ?? ($default ?? false);
	$value = old($dotSepName, $value);
	
	$skins = is_array($skins) ? json_encode($skins) : $skins;
	$skins = JsonUtils::isJson($skins) ? $skins : '{}';
	
	$multipleAttr = $allowsMultiple ? ' multiple' : '';
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'select2-colors');
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
				@if (!empty($options) && is_array($options))
					@foreach ($options as $key => $label)
						@php
							$isSelected = ($key == $value || (is_array($value) && in_array($key, $value)));
						@endphp
						<option value="{{ $key }}" @selected($isSelected)>
							{!! $label !!}
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

@pushonce("{$viewName}_assets_scripts")
	<script>
		function formatColor(color) {
			if (!color.id) {
				return color.text;
			}
			
			let hex = '#000000';
			if (
				typeof skins[color.id] !== 'undefined' &&
				typeof skins[color.id].color !== 'undefined' &&
				skins[color.id].color != null
			) {
				hex = skins[color.id].color;
			}
			if (color.id === 'default') {
				hex = '#CCCCCC';
			}
			
			let colorIcon = `<div style="display:inline-block; width:30px; height:20px; background-color:${hex};"></div>`;
			let colorText = `&nbsp;${color.text}`;
			
			let fullColor = `${colorIcon} ${colorText}`;
			
			return $(`<div style="display: flex; align-items: center;">${fullColor}</div>`);
		}
	</script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		var skins = jQuery.parseJSON('{!! $skins !!}');
		
		onDocumentReady((event) => {
			const lang = '{{ $foundLocale }}';
			const dir = '{{ $dir }}';
			const theme = {!! !empty($themeKey) ? "'{$themeKey}'" : 'undefined' !!};
			const select2Els = $('.select2-colors');
			
			let options = {
				lang: lang, {{-- No effect, use the "options.language" property --}}
				dir: dir,
				templateResult: formatColor,
				templateSelection: formatColor
			};
			
			if (typeof langLayout !== 'undefined' && typeof langLayout.select2 !== 'undefined') {
				options.language = langLayout.select2;
			}
			if (typeof theme !== 'undefined') {
				options.theme = theme;
			}
			
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
			}
			
			{{-- select2: If error occured, apply Bootstrap's error class --}}
			@if ($errorBag->has($dotSepName))
				$('select[name="{{ $name }}"]').closest('div').addClass('is-invalid');
			@endif
		});
	</script>
@endpush
