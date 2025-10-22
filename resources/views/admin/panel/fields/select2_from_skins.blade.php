{{-- select2 from array --}}
@php
	$field ??= [];
	
	// Available themes (path => key)
	$themes = [
		'bootstrap5' => 'bootstrap-5',
		'bootstrap4' => 'bootstrap4',
		'bootstrap3' => 'bootstrap',
	];
	$dir = $field['dir'] ?? config('lang.direction');
	$isRtlDirection = ($dir == 'rtl');
	$theme = $field['theme'] ?? config('larapen.core.select2.theme', 'bootstrap5');
	$themeKey = $themes[$theme] ?? null;
	
	$field['allows_null'] ??= false;
	$field['allows_multiple'] ??= false;
	
	$name = $field['name'];
	$name = $field['allows_multiple'] ? $name . '[]' : $name;
	
	$field['options'] ??= [];
	
	$multipleAttr = $field['allows_multiple'] ? ' multiple' : '';
	
	$fieldValue = $field['value'] ?? ($field['default'] ?? null);
	$fieldValue = old($field['name'], $fieldValue);
	
	// Convert to JSON and escape for HTML attribute
	$skins = $field['skins'];
	$skins = is_array($skins) ? json_encode($field['skins']) : $skins;
	$skinsJsonString = htmlspecialchars($skins, ENT_QUOTES, 'UTF-8');
@endphp
<div @include('admin.panel.inc.field_wrapper_attributes') >
	<label class="form-label fw-bolder">
		{!! $field['label'] !!}
		@if (isset($field['required']) && $field['required'])
			<span class="text-danger">*</span>
		@endif
	</label>
	@include('admin.panel.fields.inc.translatable_icon')
	<select
			name="{{ $name }}"
			style="width: 100%"
			data-skins="{!! $skinsJsonString !!}"
			@include('admin.panel.inc.field_attributes', ['default_class' => 'form-select select2_from_skins'])
			{!! $multipleAttr !!}
	>
		@if ($field['allows_null'])
			<option value="">-</option>
		@endif
		@if (!empty($field['options']))
			@foreach ($field['options'] as $key => $value)
				@php
					$selectedAttr = ($key == $fieldValue || (is_array($fieldValue) && in_array($key, $fieldValue))) ? ' selected' : '';
				@endphp
				<option value="{{ $key }}"{!! $selectedAttr !!}>{!! $value !!}</option>
			@endforeach
		@endif
	</select>
	
	{{-- HINT --}}
	@if (isset($field['hint']))
		<div class="form-text">{!! $field['hint'] !!}</div>
	@endif
</div>

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields))
	
	{{-- FIELD CSS - will be loaded in the after_styles section --}}
	@push('crud_fields_styles')
		{{-- include select2 css--}}
		<link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
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
	@endpush
	
	{{-- FIELD JS - will be loaded in the after_scripts section --}}
	@push('crud_fields_scripts')
	{{-- include select2 js--}}
	<script src="{{ asset('assets/plugins/select2/js/select2.js') }}"></script>
	<script>
		onDocumentReady((event) => {
			const theme = {!! !empty($themeKey) ? "'{$themeKey}'" : 'undefined' !!};
			const options = {};
			
			if (typeof theme !== 'undefined') {
				options.theme = theme;
			}
			
			// trigger select2 for each untriggered select2 box
			$('.select2_from_skins').each(function (i, obj) {
				// Get field's skin/color list
				let skins = $(obj).data('skins');
				
				const fnFormatColor = (result, container) => {
					return formatColor(result, skins);
				};
				
				options.templateResult = fnFormatColor;
				options.templateSelection = fnFormatColor;
				
				if (!$(obj).hasClass("select2-hidden-accessible")) {
					$(obj).select2(options);
				}
			});
		});
		
		function formatColor(data, skins) {
			if (!data.id) {
				return data.text;
			}
			
			{{--
			console.log('{{ $name }}');
			console.log(data.id);
			console.log(skins);
			--}}
			
			let hex = '#000000';
			if (
				typeof skins[data.id] !== 'undefined'
				&& typeof skins[data.id].color !== 'undefined'
				&& skins[data.id].color != null
			) {
				hex = skins[data.id].color;
			}
			if (data.id === 'default') {
				hex = '#CCCCCC';
			}
			
			const colorIcon = `<div class="d-inline-block me-2" style="width: 30px; height: 20px; background-color: ${hex};"></div>`;
			const colorText = data.text;
			const dataText = `<div style="display: flex; align-items: center;">${colorIcon}${colorText}</div>`;
			
			return $(dataText);
		}
	</script>
	@endpush

@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
