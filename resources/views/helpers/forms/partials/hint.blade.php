@php
	$hint ??= null;
	$hintClass ??= '';
	
	$hintClass = !empty($hintClass) ? " $hintClass" : '';
@endphp
@if (!empty($hint))
	<div class="form-text{{ $hintClass }}">{!! $hint !!}</div>
@endif
