{{-- icon picker input --}}
@php
    $field ??= [];
	
    $name = $field['name'] ?? 'field_name';
	$id = $field['attributes']['id'] ?? null;
	$default = $field['default'] ?? null;
	$value = $field['value'] ?? null;
	
    $iconSet = $field['iconSet'] ?? 'fontawesome6';
	$iconVersion = $field['iconVersion'] ?? 'lastest';
	$searchText = $field['searchText'] ?? 'Search icon';
	$iconSetArray = $field['iconSetArray'] ?? [ // Supported Icons Fonts
		'bootstrapfontawesome',
		'bootstrapicons',
		'elusiveicons',
		'flagicon',
		'fontawesome4',
		'fontawesome5',
		'fontawesome6',
		'glyphicon', // Bootstrap 3
		'ionicons',
		'mapicons',
		'materialdesign',
		'octicons',
		'typicons',
		'weathericons',
	];
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
    
    // Options Integrity
	$iconSet = in_array($iconSet, $iconSetArray) ? $iconSet : 'fontawesome6';
	$iconVersion = !empty($iconVersion) ? $iconVersion : 'lastest';
	$searchText = !empty($searchText) ? $searchText : 'Search icon';
@endphp

<div @include('admin.panel.inc.field_wrapper_attributes') >
    <label class="form-label fw-bolder">
        {!! $field['label'] !!}
        @if (isset($field['required']) && $field['required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    @include('admin.panel.fields.inc.translatable_icon')

    <div>
        <button class="btn btn-secondary"
                role="iconpicker"
                data-icon="{{ $value }}"
                data-iconset="{{ $iconSet }}"
                data-iconset-version="{{ $iconVersion }}"
                data-search-text="{{ $searchText }}"
        ></button>
        <input
                type="hidden"
                name="{{ $name }}"
                value="{{ $value }}"
                @include('admin.panel.inc.field_attributes')
        >
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <div class="form-text">{!! $field['hint'] !!}</div>
    @endif
</div>


@if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields))
    
    @if ($iconSet == 'bootstrapfontawesome')
        @push('crud_fields_styles')
            {{-- Bootstrap Icons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/bootstrapicons/1.13.1/css/bootstrap-icons.css') }}"/>
            {{-- Font Awesome Free 6 --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome6/6.5.2/css/all.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Bootstrap & Font Awesome 6 -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-bootstrapfontawesome-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'bootstrapicons')
        @push('crud_fields_styles')
            {{-- Bootstrap Icons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/bootstrapicons/1.13.1/css/bootstrap-icons.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Bootstrap Icons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-bootstrapicons-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'elusiveicons')
        @push('crud_fields_styles')
            {{-- Elusive Icons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/elusiveicons/2.0.0/css/elusive-icons.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Elusive Icons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-elusiveicons-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'flagicon')
        @push('crud_fields_styles')
            <!-- Flag Icons CDN -->
            <link rel="stylesheet" href="{{ asset('assets/fonts/flagicon/3.5.0/css/flag-icon.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Elusive Icons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-flagicon-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'fontawesome4')
        @push('crud_fields_styles')
            {{-- Font Awesome Free 4 --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome4/4.7.0/css/font-awesome.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Font Awesome Free 4 -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome4-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'fontawesome5')
        @push('crud_fields_styles')
            {{-- Font Awesome Free 5 --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome5/5.15.4/css/all.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Font Awesome Free 5 -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome5-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'fontawesome6')
        @push('crud_fields_styles')
            {{-- Font Awesome Free 6 --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome6/6.5.2/css/all.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Font Awesome Free 6 -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome6-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'glyphicon')
        @push('crud_fields_scripts')
            <!-- Iconpicker Bundle -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-glyphicon-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'ionicons')
        @push('crud_fields_styles')
            {{-- Ionicons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/ionicons/2.0.1/css/ionicons.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Ionicons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-ionicons-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'mapicons')
        @push('crud_fields_styles')
            {{-- Map Icons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/mapicons/2.1.0/css/map-icons.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Map Icons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-mapicons-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'materialdesign')
        @push('crud_fields_styles')
            {{-- Material Icons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/materialdesign/2.2.0/css/material-design-iconic-font.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Material Design -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-materialdesign-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'octicons')
        @push('crud_fields_styles')
            {{-- Octicons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/octicons/4.4.0/css/octicons.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Octicons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-octicons-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'typicons')
        @push('crud_fields_styles')
            {{-- Typicons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/typicons/2.0.9/css/typicons.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Typicons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-typicons-all.js') }}"></script>
        @endpush
    @elseif ($iconSet == 'weathericons')
        @push('crud_fields_styles')
            {{-- Weather Icons --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/weathericons/2.0.10/css/weather-icons.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Weather Icons -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-weathericons-all.js') }}"></script>
        @endpush
    @else
        @push('crud_fields_styles')
            {{-- Font Awesome Free 6 --}}
            <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome6/6.5.2/css/all.min.css') }}"/>
        @endpush
        
        @push('crud_fields_scripts')
            <!-- Iconpicker Iconset for Font Awesome Free 6 -->
            <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome6-all.js') }}"></script>
        @endpush
    @endif
    
    {{-- FIELD EXTRA CSS --}}
    @push('crud_fields_styles')
        <!-- Iconpicker -->
        <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap-iconpicker/css/bootstrap-iconpicker.css') }}"/>
    @endpush
    
    {{-- FIELD EXTRA JS --}}
    @push('crud_fields_scripts')
        <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/bootstrap/4.1.3/js/bootstrap.bundle.min.js') }}"></script>
        <!-- Iconpicker Bundle -->
        <script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/bootstrap-iconpicker.js') }}"></script>
        
        {{-- Iconpicker - set hidden input value --}}
        <script>
            onDocumentReady((event) => {
                $('button[role="iconpicker"]').on('change', function(e) {
                    let iconClass = e.icon;
                    iconClass = (typeof iconClass === 'string') ? iconClass.trim() : '';
                    iconClass = (iconClass === 'empty') ? '' : iconClass;
                    
                    $(this).siblings('input[type="hidden"]').val(iconClass);
                });
            });
        </script>
    @endpush
    
@endif

{{-- Note: you can use @if ($xPanel->checkIfFieldIsFirstOfItsType($field, $fields)) to only load some CSS/JS once, even though there are multiple instances of it --}}
