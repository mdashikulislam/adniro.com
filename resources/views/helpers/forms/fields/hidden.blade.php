{{-- hidden input --}}
@php
	$viewName = 'hidden';
	$type = 'hidden';
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$required ??= false;
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($dotSepName, $value);
@endphp
<input
		type="hidden"
		name="{{ $name }}"
		value="{{ $value }}"
		@include('helpers.forms.attributes.field')
>
