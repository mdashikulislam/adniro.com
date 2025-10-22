@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	
	$newline ??= false;
@endphp
@if ($newline && !$isHorizontal)
	{{-- forces a line-break inside the flex row --}}
	<div class="w-100"></div>
@endif
