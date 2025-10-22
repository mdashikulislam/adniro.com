@php
	$title ??= 'Page Title';
	$titleClass ??= 'h1 mb-1';
	$titleStyle ??= null;
	$bottomWrapperClass ??= 'mb-4';
	$subTitle ??= '<i class="bi bi-star-fill"></i>'; // ★ | Section Title
	$subTitleClass ??= 'fs-3 px-3 text-primary';
	$subTitleStyle ??= null;
	$lineClass ??= 'border-1 border-secondary opacity-25';
	
	$titleClass = !empty($titleClass) ? ' ' . $titleClass : '';
	$titleStyle = !empty($titleStyle) ? ' style="' . $titleStyle . '"' : '';
	$bottomWrapperClass = !empty($bottomWrapperClass) ? ' ' . $bottomWrapperClass : '';
	$subTitleStyle = !empty($subTitleStyle) ? ' style="' . $subTitleStyle . '"' : '';
	$lineClass = !empty($lineClass) ? ' ' . $lineClass : '';
@endphp
<h1 class="text-center{{ $titleClass }}"{!! $titleStyle !!}>
	{!! $title !!}
</h1>
<div class="d-flex align-items-center justify-content-center{{ $bottomWrapperClass }}">
	<hr class="flex-grow-1{{ $lineClass }}">
	<span class="{{ $subTitleClass }}">{!! $subTitle !!}</span>
	<hr class="flex-grow-1{{ $lineClass }}">
</div>
