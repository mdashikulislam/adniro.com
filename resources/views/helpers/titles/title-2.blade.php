@php
	$title ??= 'Page Title';
	$titleClass ??= 'fs-1 fw-bold mb-2';
	$titleStyle ??= null;
	$lineClass ??= 'border-4 border-secondary opacity-75 mb-5';
	$defaultLineWidth = 80;
	$lineWidth ??= $defaultLineWidth;
	
	$titleClass = !empty($titleClass) ? ' ' . $titleClass : '';
	$titleStyle = !empty($titleStyle) ? ' style="' . $titleStyle . '"' : '';
	$lineClass = !empty($lineClass) ? ' ' . $lineClass : '';
	$lineWidth = (!empty($lineWidth) && is_integer($lineWidth)) ? $lineWidth : $defaultLineWidth;
@endphp
<h1 class="text-center{{ $titleClass }}"{!! $titleStyle !!}>
	{!! $title !!}
</h1>
<hr class="mx-auto{{ $lineClass }}" style="max-width: {{ $lineWidth }}px;">
