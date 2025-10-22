@php
	$wrapperClass ??= 'my-4';
	$title ??= 'Section Title';
	$titleClass ??= 'px-3 m-0 text-muted small text-uppercase';
	$titleStyle ??= null;
	$lineClass ??= 'border-top';
	
	$wrapperClass = !empty($wrapperClass) ? ' ' . $wrapperClass : '';
	$titleStyle = !empty($titleStyle) ? ' style="' . $titleStyle . '"' : '';
	$lineClass = !empty($lineClass) ? ' ' . $lineClass : '';
@endphp
<div class="d-flex align-items-center{{ $wrapperClass }}">
	<div class="flex-grow-1{{ $lineClass }}"></div>
	<h2 class="{{ $titleClass }}"{!! $titleStyle !!}>
		{!! $title !!}
	</h2>
	<div class="flex-grow-1{{ $lineClass }}"></div>
</div>
