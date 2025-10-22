{{-- select2 from ajax --}}
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
	
	$entityModel = !empty($xPanel) ? $xPanel->model : null;
	
	$connectedEntity = new $field['model'];
	$connectedEntityKeyName = $connectedEntity->getKeyName();
	
	$oldValue = $field['value'] ?? ($field['default'] ?? false);
	$oldValue = old($field['name'], $oldValue);
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
			name="{{ $field['name'] }}"
			style="width: 100%"
			id="select2_ajax_{{ $field['name'] }}"
			@include('admin.panel.inc.field_attributes', ['default_class' => 'form-control'])
	>
		
		@if ($oldValue)
			@php
				$item = $connectedEntity->find($oldValue);
			@endphp
			@if ($item)
				<option value="{{ $item->getKey() }}" selected>
					{{ $item->{$field['attribute']} }}
				</option>
			@endif
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
	@endpush

@endif

{{-- include field specific select2 js--}}
@push('crud_fields_scripts')
<script>
	onDocumentReady((event) => {
		const theme = {!! !empty($themeKey) ? "'{$themeKey}'" : 'undefined' !!};
		const options = {
			multiple: false,
			placeholder: "{{ $field['placeholder'] }}",
			minimumInputLength: "{{ $field['minimum_input_length'] }}",
			ajax: {
				url: "{{ $field['data_source'] }}",
				dataType: 'json',
				quietMillis: 250,
				data: function (params) {
					return {
						q: params.term, // search term
						page: params.page
					};
				},
				processResults: function (data, params) {
					params.page = params.page || 1;

					return {
						results: $.map(data.data, function (item) {
							const textField = "{{ $field['attribute'] }}";
							return {
								text: item[textField],
								id: item["{{ $connectedEntityKeyName }}"]
							}
						}),
						more: data.current_page < data.last_page
					};
				},
				cache: true
			},
		};
		
		if (typeof theme !== 'undefined') {
			options.theme = theme;
		}
		
		// trigger select2 for each untriggered select2 box
		$("#select2_ajax_{{ $field['name'] }}").each(function (i, obj) {
			if (!$(obj).hasClass("select2-hidden-accessible")) {
				$(obj).select2(options);
			}
		});
	});
</script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
