@php
	$title ??= 'Page Title';
	$titleClass ??= 'h1 mb-0';
	$titleStyle ??= null;
	$linesWrapperClass ??= 'mb-4';
	$line1Class ??= 'w-50 border-2 border-dark opacity-75 mt-0 mb-2';
	$line2Class ??= 'w-25 border-2 border-dark opacity-75 mt-0';
	
	$titleClass = !empty($titleClass) ? ' ' . $titleClass : '';
	$titleStyle = !empty($titleStyle) ? ' style="' . $titleStyle . '"' : '';
	$linesWrapperClass = !empty($linesWrapperClass) ? ' ' . $linesWrapperClass : '';
@endphp
<h1 class="text-center{{ $titleClass }}"{!! $titleStyle !!}>
	{!! $title !!}
</h1>
<div class="d-flex flex-column align-items-center{{ $linesWrapperClass }}">
	<hr class="{{ $line1Class }}">
	<hr class="{{ $line2Class }}">
</div>
