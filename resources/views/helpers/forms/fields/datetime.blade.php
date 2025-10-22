{{-- html5 datetime input --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'datetime';
	$type = 'datetime-local';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	$hint ??= null;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
	
	// If the column has been cast to Carbon or Date (using attribute casting),
	// Get the value as a date string
	$value = ($value instanceof \Carbon\Carbon) ? $value->toDateTimeString() : $value;
	
	try {
		$date = new \DateTime($value, new \DateTimeZone('UTC'));
		$value = $date->format('Y-m-d\TH:i:s');
	} catch (Exception $e) {
		$value = date('Y-m-d\TH:i:s', strtotime($value));
	}
@endphp
<div @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<input
					type="datetime-local"
					name="{{ $name }}"
					value="{{ $value }}"
					@include('helpers.forms.attributes.field')
			>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')
