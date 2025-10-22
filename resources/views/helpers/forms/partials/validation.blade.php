@php
	use Illuminate\Support\ViewErrorBag;
	
	$name ??= 'field';
	$showError ??= false;
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$errorClass ??= '';
	
	$errorClass = !empty($errorClass) ? " $errorClass" : '';
@endphp
@if ($showError && $errorBag->has($name))
	<div class="invalid-feedback{{ $errorClass }}">{{ $errorBag->first($name) }}</div>
@endif
