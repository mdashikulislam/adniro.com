@php
	$autoAdvertising ??= [];
@endphp
@if (!empty($autoAdvertising) && empty($hideAds) && \App\Helpers\Common\AdPlacement::allowedOnThisPage())
	<div class="row d-flex justify-content-center m-0 p-0">
		<div class="col-12 text-center m-0 p-0">
			@include('front.layouts.partials.advertising.code', ['code' => data_get($autoAdvertising, 'tracking_code_large')])
		</div>
	</div>
@endif
