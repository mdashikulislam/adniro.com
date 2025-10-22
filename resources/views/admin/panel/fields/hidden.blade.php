{{-- hidden input --}}
@php
	$field ??= [];
	
	$label = $field['label'] ?? '';
	$name = $field['name'];
	
	$dotSepName = arrayFieldToDotNotation($name);
	
	$value = $field['value'] ?? $field['default'] ?? null;
	$value = old($dotSepName, $value);
@endphp
<input
		type="hidden"
		name="{{ $name }}"
		value="{{ $value }}"
		@include('admin.panel.inc.field_attributes')
>
