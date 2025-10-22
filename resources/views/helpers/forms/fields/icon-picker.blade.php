{{-- icon picker input --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'icon-picker';
	$type = 'iconpicker';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	
	$pluginOptions ??= [];
	
	$iconSet = $pluginOptions['iconSet'] ?? 'fontawesome6';
	$iconVersion = $pluginOptions['iconVersion'] ?? 'lastest';
	$searchText = $pluginOptions['searchText'] ?? 'Search icon';
	$iconSetArray = $pluginOptions['iconSetArray'] ?? [ // Supported Icons Fonts
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
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
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
@endphp

@if ($iconSet == 'bootstrapfontawesome')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/bootstrapicons/1.13.1/css/bootstrap-icons.css') }}"/>
		<link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome6/6.5.2/css/all.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-bootstrapfontawesome-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'bootstrapicons')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/bootstrapicons/1.13.1/css/bootstrap-icons.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-bootstrapicons-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'elusiveicons')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/elusiveicons/2.0.0/css/elusive-icons.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-elusiveicons-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'flagicon')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/flagicon/3.5.0/css/flag-icon.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-flagicon-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'fontawesome4')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome4/4.7.0/css/font-awesome.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome4-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'fontawesome5')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome5/5.15.4/css/all.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome5-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'fontawesome6')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome6/6.5.2/css/all.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome6-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'glyphicon')
	
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-glyphicon-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'ionicons')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/ionicons/2.0.1/css/ionicons.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-ionicons-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'mapicons')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/mapicons/2.1.0/css/map-icons.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-mapicons-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'materialdesign')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/materialdesign/2.2.0/css/material-design-iconic-font.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-materialdesign-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'octicons')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/octicons/4.4.0/css/octicons.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-octicons-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'typicons')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/typicons/2.0.9/css/typicons.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-typicons-all.js') }}"></script>
	@endpushonce

@elseif ($iconSet == 'weathericons')
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/weathericons/2.0.10/css/weather-icons.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-weathericons-all.js') }}"></script>
	@endpushonce

@else
	
	@pushonce("{$viewName}_helper_styles")
		<link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome6/6.5.2/css/all.min.css') }}"/>
	@endpushonce
	@pushonce("{$viewName}_helper_scripts")
		<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome6-all.js') }}"></script>
	@endpushonce

@endif

{{-- FIELD EXTRA CSS  --}}
@pushonce("{$viewName}_helper_styles")
	{{-- Iconpicker --}}
	<link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap-iconpicker/css/bootstrap-iconpicker.css') }}"/>
@endpushonce

{{-- FIELD EXTRA JS --}}
@pushonce("{$viewName}_helper_scripts")
	<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/bootstrap/4.1.3/js/bootstrap.bundle.min.js') }}"></script>
	{{-- Iconpicker Bundle --}}
	<script type="text/javascript" src="{{ asset('assets/plugins/bootstrap-iconpicker/js/bootstrap-iconpicker.js') }}"></script>
	
	{{-- Iconpicker - set hidden input value --}}
	<script>
		onDocumentReady((event) => {
			$('button[role=iconpicker]').on('change', function (e) {
				$(this).siblings('input[type=hidden]').val(e.icon);
			});
		});
	</script>
@endpushonce
