{{-- datepicker-js-daterange (Vanilla JS Datepicker) --}}
{{-- https://mymth.github.io/vanillajs-datepicker/ --}}
{{-- https://github.com/mymth/vanillajs-datepicker --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'datepicker-js-daterange';
	$type = 'text';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$start ??= [];
	$end ??= [];
	$hint ??= null;
	
	$pluginOptions ??= [];
	
	$startName ??= null;
	$startValue ??= null;
	$startDefault ??= null;
	$startRequired ??= false;
	$startHint ??= null;
	$startValue = !empty($startValue) ? $startValue : (!empty($startDefault) ? $startDefault : null);
	$startValue = old($startName, $startValue);
	$startValue = ($startValue instanceof \Carbon\Carbon) ? $startValue->format('Y-m-d') : $startValue;
	
	$endName ??= null;
	$endValue ??= null;
	$endDefault ??= null;
	$endRequired ??= false;
	$endHint ??= null;
	$endValue = !empty($endValue) ? $endValue : (!empty($endDefault) ? $endDefault : null);
	$endValue = old($endName, $endValue);
	$endValue = ($endValue instanceof \Carbon\Carbon) ? $endValue->format('Y-m-d') : $endValue;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$moduleId = $pluginOptions['moduleId'] ?? $startName . '-' . $endName;
	$locale = $pluginOptions['locale'] ?? app()->getLocale();
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<input type="hidden" name="{{ $name }}" value="{{ $value }}">
			<div id="{{ $moduleId }}">
				<input
						type="text"
						name="{{ $startName }}"
						value="{{ $startValue }}"
						@include('helpers.forms.attributes.field')
				>
				<span>{{ t('to') }}</span>
				<input
						type="text"
						name="{{ $endName }}"
						value="{{ $endValue }}"
						@include('helpers.forms.attributes.field')
				>
			</div>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
	$pluginBasePath = 'assets/plugins/vanillajs-datepicker/1.3.4/';
	$pluginFullPath = public_path($pluginBasePath);
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("datepicker_js_assets_styles")
	<link rel="stylesheet" href="{{ asset($pluginBasePath . 'css/datepicker.min.css') }}">
	<link rel="stylesheet" href="{{ asset($pluginBasePath . 'css/datepicker-bs5.min.css') }}">
@endpushonce

@pushonce("datepicker_js_assets_scripts")
	<script src="{{ asset($pluginBasePath . 'js/datepicker.min.js') }}"></script>
	@php
		$foundLocale = '';
		if (file_exists($pluginFullPath . getLangTag($locale) . '.js')) {
			$foundLocale = getLangTag($locale);
		}
		if (empty($foundLocale)) {
			if (file_exists($pluginFullPath . strtolower($locale) . '.js')) {
				$foundLocale = strtolower($locale);
			}
		}
		if (empty($foundLocale)) {
			$foundLocale = 'en';
		}
	@endphp
	@if ($foundLocale != 'en')
		<script src="{{ asset($pluginBasePath . $foundLocale . '.js') }}"></script>
	@endif
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		onDocumentReady((event) => {
			const elem = document.getElementById('{{ $moduleId }}');
			const datepicker = new Datepicker(elem, {
				buttonClass: 'btn',
			});
		});
	</script>
@endpush
