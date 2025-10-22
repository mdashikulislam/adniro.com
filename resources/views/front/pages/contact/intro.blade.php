@php
	// Google Maps
	$useGeocodingApi = (config('settings.other.google_maps_integration_type') == 'geocoding');
	$mapsJavascriptApiKey = config('services.google_maps_platform.maps_javascript_api_key');
	$mapsEmbedApiKey = config('services.google_maps_platform.maps_embed_api_key');
	$geocodingApiKey = config('services.google_maps_platform.geocoding_api_key');
	$useAsyncGeocoding = (config('settings.other.use_async_geocoding') == '1');
	
	$mapsEmbedApiKey ??= $mapsJavascriptApiKey;
	$geocodingApiKey ??= $mapsJavascriptApiKey;
	$geocodingApiKey = $useAsyncGeocoding ? $geocodingApiKey : $mapsJavascriptApiKey;
	
	$mapHeight = 400;
	$city ??= [];
	$geoMapAddress = getItemAddressForMap($city);
	
	$mapsEmbedApiUrl = getGoogleMapsEmbedApiUrl($mapsEmbedApiKey, $geoMapAddress);
	$geocodingApiUrl = getGoogleMapsApiUrl($geocodingApiKey, $useAsyncGeocoding);
@endphp

@if (!empty($geocodingApiKey))
	<div class="container-fluid px-0" style="height: {{ $mapHeight }}px;">
		@if ($useGeocodingApi)
			<div id="googleMaps" style="width: 100%; height: {{ $mapHeight }}px;"></div>
		@else
			<iframe
					id="googleMaps"
					width="100%"
					height="{{ $mapHeight }}"
					style="border:0;"
					loading="lazy"
					title="{{ $geoMapAddress }}"
					aria-label="{{ $geoMapAddress }}"
					src="{{ $mapsEmbedApiUrl }}"
			></iframe>
		@endif
	</div>
@endif

@section('after_scripts')
	@parent
	@if ($useGeocodingApi)
		{{-- Google Geocoding API script --}}
		@if (!empty($geocodingApiUrl))
			<script async defer src="{{ $geocodingApiUrl }}"></script>
		@endif
		
		{{-- JS code to append the map --}}
		<script>
			var geocodingApiKey = '{{ $geocodingApiKey }}';
			var locationAddress = '{{ $geoMapAddress }}';
			var locationMapElId = 'googleMaps';
			var locationMapId = '{{ generateUniqueCode(16) }}';
		</script>
		@if ($useAsyncGeocoding)
			<script src="{{ url('assets/js/app/google-maps-async.js') }}"></script>
		@else
			<script src="{{ url('assets/js/app/google-maps.js') }}"></script>
		@endif
	@endif
@endsection
