@php
	$langDirection ??= config('lang.direction');
	$isRTLEnabled = ($langDirection == 'rtl');
	
	$carouselCtrl ??= 'false';
	$carouselCtrlContainerClass ??= '';
	$carouselCtrlClass ??= '';
	$carouselCtrlBtnClass ??= '';
	
	$prevClass = ' ctrl-prev';
	$prevLabel = t('prev');
	$prevIconClass = 'bi bi-arrow-left';
	
	$nextClass = $isRTLEnabled ? ' ctrl-next me-1' : ' ctrl-next ms-1';
	$nextLabel = t('next');
	$nextIconClass = 'bi bi-arrow-right';
@endphp
@if ($carouselCtrl == 'true' && !empty($carouselCtrlContainerClass))
	<div class="{{ "{$carouselCtrlContainerClass}{$carouselCtrlClass}" }}">
		<button type="button"
		        class="btn btn-outline-secondary{{ $carouselCtrlBtnClass . $prevClass }}"
		        title="{{ $prevLabel }}"
		        data-bs-toggle="tooltip"
		>
			<i class="{{ $prevIconClass }}"></i>
		</button>
		<button type="button"
		        class="btn btn-outline-secondary{{ $carouselCtrlBtnClass . $nextClass }}"
		        title="{{ $nextLabel }}"
		        data-bs-toggle="tooltip"
		>
			<i class="{{ $nextIconClass }}"></i>
		</button>
	</div>
@endif
