{{-- text input --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'address';
	$type = 'text';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$prefix ??= null;
	$suffix ??= null;
	$required ??= false;
	$hint ??= null;
	
	$pluginOptions ??= [];
	
	$storeAsJson = $pluginOptions['storeAsJson'] ?? false;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	// The field should work whether Laravel attribute casting is used
	$value = (is_array($value) || is_object($value)) ? json_encode($value) : $value;
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	$storeAsJsonStr = $storeAsJson ? 'true' : 'false';
	
	$hasInputGroup = (!empty($prefix) || !empty($suffix));
	
	// Handle error class for "input-group"
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($dotSepName) ? 'is-invalid' : '';
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			<input type="hidden" name="{{ $name }}" value="{{ $value }}" id="{{ $id }}">
			@if ($hasInputGroup)
				<div class="input-group {{ $isInvalidClass }}">
					@endif
					@if (!empty($prefix))
						<div class="input-group-addon">{!! $prefix !!}</div>
					@endif
					@if ($storeAsJson)
						<input
								type="text"
								data-address="{&quot;field&quot;: &quot;{{$name}}&quot;, &quot;full&quot;: {{ $storeAsJsonStr }} }"
								@include('helpers.forms.attributes.field')
						>
					@else
						<input
								type="text"
								data-address="{&quot;field&quot;: &quot;{{$name}}&quot;, &quot;full&quot;: {{ $storeAsJsonStr }} }"
								name="{{ $name }}"
								value="{{ $value }}"
								@include('helpers.forms.attributes.field')
						>
					@endif
					@if (!empty($suffix))
						<div class="input-group-addon">{!! $suffix !!}</div>
					@endif
					@if ($hasInputGroup)
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
@endphp

{{-- Note: you can use  to only load some CSS/JS once, even though there are multiple instances of it --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_helper_styles")
	<style>
		.ap-input-icon.ap-icon-pin {
			right: 5px !important;
		}
		
		.ap-input-icon.ap-icon-clear {
			right: 10px !important;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_helper_scripts")
	<script src="{{ asset('assets/plugins/places.js/1.19.0/places.min.js') }}"></script>
	<script>
		onDocumentReady((event) => {
			window.AlgoliaPlaces = window.AlgoliaPlaces || {};
			
			$('[data-address]').each(function() {
				const $this = $(this),
					$addressConfig = $this.data('address'),
					$field = $('[name="' + $addressConfig.field + '"]'),
					$place = places({
						container: $this[0]
					});
				
				function clearInput() {
					if (!$this.val().length) {
						$field.val('');
					}
				}
				
				if ($addressConfig.full) {
					$place.on('change', function (e) {
						const result = JSON.parse(JSON.stringify(e.suggestion));
						delete (result.highlight);
						delete (result.hit);
						delete (result.hitIndex);
						delete (result.rawAnswer);
						delete (result.query);
						$field.val(JSON.stringify(result));
					});
					
					$this.on('change blur', clearInput);
					$place.on('clear', clearInput);
					
					if ($field.val().length) {
						const existingData = JSON.parse($field.val());
						$this.val(existingData.value);
					}
				}
				
				window.AlgoliaPlaces[$addressConfig.field] = $place;
			});
		});
	</script>
@endpushonce
