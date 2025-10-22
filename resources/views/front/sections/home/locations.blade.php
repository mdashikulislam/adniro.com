@php
	$sectionOptions = $locationsOptions ?? [];
	
	// Get Admin Map's values
	$locCanBeShown = (data_get($sectionOptions, 'show_cities') == '1');
	$locColumns = (int)(data_get($sectionOptions, 'items_cols') ?? 3);
	$locCountListingsPerCity = (config('settings.listings_list.count_cities_listings'));
	$mapCanBeShown = (
		file_exists(config('larapen.core.maps.path') . config('country.icode') . '.svg')
		&& data_get($sectionOptions, 'enable_map') == '1'
	);
	
	$showListingBtn = (data_get($sectionOptions, 'show_listing_btn') == '1');
	
	$fullHeight = $sectionOptions['full_height'] ?? '0';
	$isFullHeightEnabled = ($fullHeight == '1');
	$style = $isFullHeightEnabled ? 'height: 100vh; min-height: 100dvh;' : '';
	
	$htmlAttr = $sectionOptions['html_attributes'] ?? '';
	$htmlAttr = !empty($htmlAttr) ? " {$htmlAttr}" : '';
	
	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';
	
	$sectionData ??= [];
	$cities = (array)($sectionData['cities'] ?? []);
@endphp
@if ($locCanBeShown || $mapCanBeShown)
	<div class="container{{ $cssClasses }} location-card" style="{!! $style !!}">
		<div class="card bg-body-tertiary"{!! $htmlAttr !!}>
			<div class="card-body rounded p-4 p-lg-3 pb-lg-4 p-md-2">
				
				<div class="row">
					@if (!$mapCanBeShown)
						<div class="row">
							<div class="col-xl-12 col-sm-12">
								<h4 class="pb-3 px-0 fw-bold text-nowrap">
									<i class="bi bi-geo-alt"></i>&nbsp;{{ t('Choose a city') }}
								</h4>
							</div>
						</div>
					@endif
					
					@php
						$leftClassCol = '';
						$rightClassCol = '';
						$rowCol = 'row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-1'; // Cities Columns
						
						if ($locCanBeShown && $mapCanBeShown) {
							// Display the Cities & the Map
							$leftClassCol = 'col-lg-8 col-md-12';
							$rightClassCol = 'col-lg-3 col-md-12 mt-3 mt-xl-0 mt-lg-0';
							$rowCol = 'row-cols-lg-3 row-cols-md-2 row-cols-sm-1 row-cols-1';
							
							if ($locColumns == 2) {
								$leftClassCol = 'col-md-6 col-sm-12';
								$rightClassCol = 'col-md-5 col-sm-12';
								$rowCol = 'row-cols-lg-2 row-cols-md-2 row-cols-sm-1 row-cols-1';
							}
							if ($locColumns == 1) {
								$leftClassCol = 'col-md-3 col-sm-12';
								$rightClassCol = 'col-md-8 col-sm-12';
								$rowCol = 'row-cols-lg-1 row-cols-md-1 row-cols-sm-1 row-cols-1';
							}
						} else {
							if ($locCanBeShown && !$mapCanBeShown) {
								// Display the Cities & Hide the Map
								$leftClassCol = 'col-xl-12';
							}
							if (!$locCanBeShown && $mapCanBeShown) {
								// Display the Map & Hide the Cities
								$rightClassCol = 'col-xl-12';
							}
						}
					@endphp
					@if ($locCanBeShown)
						<div class="{{ $leftClassCol }} m-0 p-0">
							@if (!empty($cities))
								@if ($mapCanBeShown)
									<h4 class="pt-1 pb-3 px-3 fw-bold text-nowrap">
										<i class="bi bi-geo-alt"></i>&nbsp;{{ t('Choose a city or region') }}
									</h4>
								@endif
								<div class="row px-4">
									<div class="col-xl-12">
										<div id="cityList" class="row {{ $rowCol }}">
											@foreach ($cities as $key => $city)
												<div class="col mb-2">
													@if (data_get($city, 'id') == 0)
														<a href="#browseLocations"
														   class="{{ linkClass('body-emphasis') }}"
														   data-bs-toggle="modal"
														   data-admin-code="0"
														   data-city-id="0"
														>
															{!! data_get($city, 'name') !!}
														</a>
													@else
														<a href="{{ urlGen()->city($city) }}" class="{{ linkClass('body-emphasis') }}">
															{{ data_get($city, 'name') }}
														</a>
														@if ($locCountListingsPerCity)
															&nbsp;({{ data_get($city, 'posts_count') ?? 0 }})
														@endif
													@endif
												</div>
											@endforeach
										</div>
									</div>
									
									@if ($showListingBtn)
										@php
											[$createListingLinkUrl, $createListingLinkAttr] = getCreateListingLinkInfo();
										@endphp
										<div class="col-xl-12 text-center pt-5">
											<a class="btn btn-outline-primary px-3"
											   href="{{ $createListingLinkUrl }}"{!! $createListingLinkAttr !!}
											>
												<i class="fa-regular fa-pen-to-square"></i> {{ t('create_listing') }}
											</a>
										</div>
									@endif
			
								</div>
							@endif
						</div>
					@endif
					
					@include('front.sections.home.locations.svgmap')
				</div>
				
			</div>
		</div>
	</div>
@endif

@section('modal_location')
	@parent
	@if ($locCanBeShown || $mapCanBeShown)
		@include('front.layouts.partials.modal.location')
	@endif
@endsection

@section('after_scripts')
	@parent
@endsection
