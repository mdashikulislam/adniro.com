{{-- configurable color picker --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'color-picker';
	$type = 'text'; // coloris
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	
	$pluginOptions ??= [];
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<div class="input-group">
				<input
						type="text"
						name="{{ $name }}"
						value="{{ $value }}" data-coloris
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

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	<link rel="stylesheet" href="{{ asset('assets/plugins/coloris/0.24.0/coloris.min.css') }}"/>
	<style>
		.coloris {
			/* display: flex; /* Buggy in v0.24.0 */
			/* flex-wrap: wrap; /* Buggy in v0.24.0 */
			flex-shrink: 0;
			margin-bottom: 30px;
		}
		
		.coloris input {
			width: 100%;
			height: 32px;
			padding: 0 10px;
			border-radius: 5px;
			font-family: inherit;
			font-size: inherit;
			font-weight: inherit;
			box-sizing: border-box;
		}
		
		.clr-field {
			width: 100%;
		}
		
		.square .clr-field button,
		.circle .clr-field button {
			width: 22px;
			height: 22px;
			left: 5px;
			right: auto;
			border-radius: 5px;
		}
		
		.square .clr-field input,
		.circle .clr-field input {
			padding-left: 36px;
		}
		
		.circle .clr-field button {
			border-radius: 50%;
		}
		
		.full .clr-field button {
			width: 100%;
			height: 100%;
			border-radius: 5px;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_assets_scripts")
	<script type="text/javascript" src="{{ asset('assets/plugins/coloris/0.24.0/coloris.min.js') }}"></script>
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_scripts")
	<script>
		onDocumentReady((event) => {
			/*
			 * More Information:
			 * https://github.com/mdbassit/Coloris
			 */
			let defaultConfig = {
				theme: 'pill',
				themeMode: 'dark',
				formatToggle: true,
				closeButton: true,
				clearButton: true,
			};
			let config = {};
			@if (isset($pluginOptions))
				config = {!! json_encode($pluginOptions) !!};
			@endif
			document.querySelector('[name="{{ $name }}"]').addEventListener('click', e => {
				Coloris(!isEmpty(config) ? config : defaultConfig);
			});
		});
	</script>
@endpush
