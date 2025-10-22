@php
	$form ??= null;
@endphp
@foreach ($fields as $field)
	@include('admin.panel.fields.' . $field['type'], ['field' => $field])
	
	{{--
		The fields 'new_line/newline' element can be:
		- true (boolean) or 'both' (string) for both of 'create' or 'update' forms
		- 'create' (string) for create form only
		- 'update' (string) for update form only
	--}}
	@php
		$newLine = $field['new_line'] ?? $field['newline'] ?? 'undefined';
	@endphp
	@if ($newLine === true || $newLine === 'both' || $newLine === $form)
		<div style="clear: both; margin: 0; padding: 0;"></div>
	@endif
@endforeach
