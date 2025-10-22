@php
	$topAdvertising ??= [];
	$style ??= '';
	$htmlAttr ??= '';
	$cssClasses ??= '';
	$isFromPostDetails ??= false;
	
	$cssClasses = empty($cssClasses) ? ' mb-3' : $cssClasses;
	$cssClasses = $isFromPostDetails ? ' mt-3 mb-3' : $cssClasses;
@endphp
@if (!empty($topAdvertising))
	<div class="container{{ $cssClasses }}" style="{!! $style !!}">
		<div class="row"{!! $htmlAttr !!}>
			@php
				$responsiveClass = (data_get($topAdvertising, 'is_responsive') != 1) ? ' d-none d-xl-block d-lg-block d-md-none d-sm-none' : '';
			@endphp
			{{-- Desktop --}}
			<div class="col-12 ads-parent-responsive{{ $responsiveClass }}">
				<div class="text-center">
					{!! data_get($topAdvertising, 'tracking_code_large') !!}
				</div>
			</div>
			@if (data_get($topAdvertising, 'is_responsive') != 1)
				{{-- Tablet --}}
				<div class="col-12 ads-parent-responsive d-none d-xl-none d-lg-none d-md-block d-sm-none">
					<div class="text-center">
						{!! data_get($topAdvertising, 'tracking_code_medium') !!}
					</div>
				</div>
				{{-- Mobile --}}
				<div class="col-12 ads-parent-responsive d-block d-xl-none d-lg-none d-md-none d-sm-block">
					<div class="text-center">
						{!! data_get($topAdvertising, 'tracking_code_small') !!}
					</div>
				</div>
			@endif
		</div>
	</div>
@endif
