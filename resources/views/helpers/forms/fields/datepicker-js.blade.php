{{-- datepicker-js (Vanilla JS Datepicker) --}}
{{-- https://mymth.github.io/vanillajs-datepicker/ --}}
{{-- https://github.com/mymth/vanillajs-datepicker --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'datepicker-js';
	$type = 'text';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	
	$pluginOptions ??= [];
	
	$locale = $pluginOptions['locale'] ?? app()->getLocale();
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	// If the column has been cast to Carbon or Date (using attribute casting),
	// Get the value as a date string
	$value = ($value instanceof \Carbon\Carbon) ? $value->format('Y-m-d') : $value;
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<input type="hidden" name="{{ $name }}" value="{{ $value }}">
			@if (!empty($prefix) || !empty($suffix))
				<div class="input-group">
					@endif
					@if (!empty($prefix))
						<div class="input-group-addon">{!! $prefix !!}</div>
					@endif
					<input
							type="text"
							name="{{ $name }}"
							value="{{ $value }}"
							@include('helpers.forms.attributes.field')
					>
					@if (!empty($suffix))
						<div class="input-group-addon">{!! $suffix !!}</div>
					@endif
					@if (!empty($prefix) || !empty($suffix))
				</div>
			@endif
			
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
@pushonce("{$viewName}_assets_styles")
	<link rel="stylesheet" href="{{ asset($pluginBasePath . 'css/datepicker.min.css') }}">
	<link rel="stylesheet" href="{{ asset($pluginBasePath . 'css/datepicker-bs5.min.css') }}">
@endpushonce

@pushonce("{$viewName}_assets_scripts")
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
			const elem = document.querySelector('input[name="{{ $name }}"]');
			const datepicker = new Datepicker(elem, {
				buttonClass: 'btn',
			});
		});
	</script>
@endpush
