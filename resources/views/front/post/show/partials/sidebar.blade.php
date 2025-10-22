@php
	$authUser = auth()->check() ? auth()->user() : null;
	$authUserId = !empty($authUser) ? $authUser->getAuthIdentifier() : 0;
	
	$post ??= [];
	$user ??= [];
	$countPackages ??= 0;
	$countPaymentMethods ??= 0;
	
	$isPostOwner = (!empty($authUserId) && $authUserId == data_get($post, 'user_id'));
	
	// Google Maps
	$isMapEnabled = (config('settings.listing_page.show_listing_on_googlemap') == '1');
	$useGeocodingApi = (config('settings.other.google_maps_integration_type') == 'geocoding');
	$mapsJavascriptApiKey = config('services.google_maps_platform.maps_javascript_api_key');
	$mapsEmbedApiKey = config('services.google_maps_platform.maps_embed_api_key');
	$geocodingApiKey = config('services.google_maps_platform.geocoding_api_key');
	$useAsyncGeocoding = (config('settings.other.use_async_geocoding') == '1');
	
	$mapsEmbedApiKey ??= $mapsJavascriptApiKey;
	$geocodingApiKey ??= $mapsJavascriptApiKey;
	$geocodingApiKey = $useAsyncGeocoding ? $geocodingApiKey : $mapsJavascriptApiKey;
	
	$mapHeight = 250;
	$city = data_get($post, 'city', []);
	$geoMapAddress = getItemAddressForMap($city);
	
	$mapsEmbedApiUrl = getGoogleMapsEmbedApiUrl($mapsEmbedApiKey, $geoMapAddress);
	$geocodingApiUrl = getGoogleMapsApiUrl($geocodingApiKey, $useAsyncGeocoding);
	
	$linkClass = linkClass();
@endphp
<aside class="vstack gap-md-4 gap-3">
	<div class="card">
		@if ($isPostOwner)
			<div class="card-header fw-bold">
				{{ t('Manage Listing') }}
			</div>
		@endif
		<div class="card-body">
			{{-- Author Info (for Guests & Non-Owner Users) --}}
			@if (!$isPostOwner)
				<div class="container p-0 border-bottom pb-3 mb-3">
					<div class="row">
						<div class="col-md-4">
							<img src="{{ data_get($post, 'user_photo_url') }}" class="img-fluid rounded" alt="{{ data_get($post, 'contact_name') }}">
						</div>
						<div class="col-md-8 vstack gap-1">
							<small class="text-secondary">{{ t('Posted by') }}</small>
							<span class="fs-6 fw-bold">
							@if (!empty($user))
								<a href="{{ urlGen()->user($user) }}" class="{{ $linkClass }}">
									{{ data_get($post, 'contact_name') }}
								</a>
								@else
									{{ data_get($post, 'contact_name') }}
								@endif
							</span>
							
							@if (config('plugins.reviews.installed'))
								@if (view()->exists('reviews::ratings-user'))
									@include('reviews::ratings-user')
								@endif
							@endif
						</div>
					</div>
				</div>
			@endif
			
			{{-- Author Additional Info (for Guests & Non-Owner Users) --}}
			@php
				$evActionClass = 'border-top-0';
			@endphp
			@if (!$isPostOwner)
				<div class="container p-0 mb-3 text-secondary small">
					<div class="row my-2">
						<div class="col-6 text-start">
							<i class="bi bi-geo-alt"></i> {{ t('location') }}
						</div>
						<div class="col-6 text-end">
							<a href="{!! urlGen()->city(data_get($post, 'city')) !!}" class="{{ $linkClass }}">
								{{ data_get($post, 'city.name') }}
							</a>
						</div>
					</div>
					@if (!config('settings.listing_page.hide_date'))
						@if (!empty($user) && !empty(data_get($user, 'created_at_formatted')))
							<div class="row my-2">
								<div class="col-6 text-start">
									<i class="bi bi-person-check"></i> {{ t('Joined') }}
								</div>
								<div class="col-6 text-end">
									<span>{!! data_get($user, 'created_at_formatted') !!}</span>
								</div>
							</div>
						@endif
					@endif
				</div>
				@php
					$evActionClass = 'border-top pt-3';
				@endphp
			@endif
			
			{{-- Actions Buttons --}}
			<div class="container p-0 {{ $evActionClass }} d-grid gap-2">
				{{-- Actions Buttons (for Logged-in Users) --}}
				@if (!empty($authUser))
					@if ($isPostOwner)
						{{-- Actions Buttons (for Owner Author) --}}
						<a href="{{ urlGen()->editPost($post) }}" class="btn btn-primary">
							<i class="fa-regular fa-pen-to-square"></i> {{ t('Update the details') }}
						</a>
						@if (isMultipleStepsFormEnabled())
							<a href="{{ url('posts/' . data_get($post, 'id') . '/photos') }}" class="btn btn-secondary">
								<i class="fa-solid fa-camera"></i> {{ t('Update Photos') }}
							</a>
							@if ($countPackages > 0 && $countPaymentMethods > 0)
								<a href="{{ url('posts/' . data_get($post, 'id') . '/payment') }}" class="btn btn-success">
									<i class="fa-regular fa-circle-check"></i> {{ t('Make It Premium') }}
								</a>
							@endif
						@endif
						@if (empty(data_get($post, 'archived_at')) && isVerifiedPost($post))
							<a href="{{ url(urlGen()->getAccountBasePath() . '/posts/list/' . data_get($post, 'id') . '/offline') }}"
							   class="btn btn-warning confirm-simple-action"
							>
								<i class="fa-solid fa-eye-slash"></i> {{ t('put_it_offline') }}
							</a>
						@endif
						@if (!empty(data_get($post, 'archived_at')))
							<a href="{{ url(urlGen()->getAccountBasePath() . '/posts/archived/' . data_get($post, 'id') . '/repost') }}"
							   class="btn btn-info confirm-simple-action"
							>
								<i class="fa-solid fa-recycle"></i> {{ t('re_post_it') }}
							</a>
						@endif
					@else
						{{-- Actions Buttons (for Non-Owner Users) --}}
						{!! genPhoneNumberBtn($post, true) !!}
						{!! genEmailContactBtn($post, true) !!}
					@endif
					
					{{-- Actions Buttons (for Admin Users Only) --}}
					@php
						try {
							if (doesUserHavePermission($authUser, \App\Models\Permission::getStaffPermissions())) {
								$btnUrl = urlGen()->adminUrl('blacklists/add') . '?';
								$btnQs = (!empty(data_get($post, 'email'))) ? 'email=' . data_get($post, 'email') : '';
								$btnQs = (!empty($btnQs)) ? $btnQs . '&' : $btnQs;
								$btnQs = (!empty(data_get($post, 'phone'))) ? $btnQs . 'phone=' . data_get($post, 'phone') : $btnQs;
								$btnUrl = $btnUrl . $btnQs;
								
								if (!isDemoDomain($btnUrl)) {
									$btnText = trans('admin.ban_the_user');
									$btnHint = $btnText;
									if (!empty(data_get($post, 'email')) && !empty(data_get($post, 'phone'))) {
										$btnHint = trans('admin.ban_the_user_email_and_phone', [
											'email' => data_get($post, 'email'),
											'phone' => data_get($post, 'phone'),
										]);
									} else {
										if (!empty(data_get($post, 'email'))) {
											$btnHint = trans('admin.ban_the_user_email', ['email' => data_get($post, 'email')]);
										}
										if (!empty(data_get($post, 'phone'))) {
											$btnHint = trans('admin.ban_the_user_phone', ['phone' => data_get($post, 'phone')]);
										}
									}
									$tooltip = ' data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $btnHint . '"';
									
									$btnOut = '<a href="'. $btnUrl .'" class="btn btn-outline-danger confirm-simple-action"'. $tooltip .'>';
									$btnOut .= $btnText;
									$btnOut .= '</a>';
									
									echo $btnOut;
								}
							}
						} catch (\Throwable $e) {}
					@endphp
				@else
					{{-- Actions Buttons (for Guests) --}}
					{!! genPhoneNumberBtn($post, true) !!}
					{!! genEmailContactBtn($post, true) !!}
				@endif
			</div>
		</div>
	</div>
	
	{{-- Google Maps --}}
	@if ($isMapEnabled)
		<div class="card">
			<div class="card-header fw-bold">
				{{ t('location_map') }}
			</div>
			<div class="card-body text-start p-0">
				<div class="posts-googlemaps">
					@if ($useGeocodingApi)
						<div id="googleMaps" style="width: 100%; height: {{ $mapHeight }}px;"></div>
					@else
						<iframe id="googleMaps"
						        width="100%"
						        height="{{ $mapHeight }}"
						        src="{{ $mapsEmbedApiUrl }}"
						        loading="lazy"
						        style="border:0;"
						        allowfullscreen
						></iframe>
					@endif
				</div>
			</div>
		</div>
	@endif
	
	{{-- Social Media Sharing --}}
	@if (isVerifiedPost($post))
		@include('front.layouts.partials.social.horizontal')
	@endif
	
	{{-- Safety Tips --}}
	@php
		$tips = [
			t('Meet seller at a public place'),
			t('Check the item before you buy'),
			t('Pay only after collecting the item'),
		];
	@endphp
	<div class="card">
		<div class="card-header fw-bold">
			{{ t('Safety Tips for Buyers') }}
		</div>
		<div class="card-body text-start">
			<ul class="list-unstyled">
				@foreach($tips as $tip)
					<li><i class="bi bi-check-lg"></i> {{ $tip }}</li>
				@endforeach
			</ul>
			@php
				$tipsLinkAttributes = getUrlPageByType('tips');
			@endphp
			@if (!str_contains($tipsLinkAttributes, 'href="#"') && !str_contains($tipsLinkAttributes, 'href=""'))
				<p>
					<a class="float-end {{ $linkClass }}" {!! $tipsLinkAttributes !!}>
						{{ t('Know more') }} <i class="fa-solid fa-angles-right"></i>
					</a>
				</p>
			@endif
		</div>
	</div>
</aside>

@section('after_scripts')
	@parent
	@if ($isMapEnabled)
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
	@endif
@endsection
