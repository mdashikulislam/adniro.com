@php
	$wrapperClass ??= 'mb-4';
	$title ??= 'Page Title';
	$titleClass ??= 'mb-0 me-3';
	$titleStyle ??= null;
	$lineClass ??= 'border-1 opacity-25';
	$rightContent ??= '<i class="bi bi-asterisk ms-3"></i>';
	
	$wrapperClass = !empty($wrapperClass) ? ' ' . $wrapperClass : '';
	$titleClass = !empty($titleClass) ? ' ' . $titleClass : '';
	$titleStyle = !empty($titleStyle) ? ' style="' . $titleStyle . '"' : '';
	$lineClass = !empty($lineClass) ? ' ' . $lineClass : '';
@endphp
<div class="d-flex align-items-center{{ $wrapperClass }}">
	<h1 class="{{ $titleClass }}"{!! $titleStyle !!}>
		{!! $title !!}
	</h1>
	<div class="flex-grow-1">
		<hr class="border{{ $lineClass }}">
	</div>
	{!! $rightContent !!}
</div>
