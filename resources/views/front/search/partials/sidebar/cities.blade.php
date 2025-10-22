@php
	// Clear Filter Button
	$clearFilterBtn = urlGen()->getCityFilterClearLink($cat ?? null, $city ?? null);
	
	/*
	 * Check if the City Model exists in the Cities eloquent collection
	 * If it doesn't exist in the collection,
	 * Then, add it into the Cities eloquent collection
	 */
	if (isset($cities, $city) && !collect($cities)->contains($city)) {
		collect($cities)->push($city)->toArray();
	}
	
	// Links CSS Class
	$linkClass = linkClass('body-emphasis');
@endphp
{{-- City --}}
<div class="container p-0 vstack gap-2">
	<h5 class="border-bottom pb-2 d-flex justify-content-between">
		<span class="fw-bold">{{ t('locations') }}</span> {!! $clearFilterBtn !!}
	</h5>
	<div>
		<ul class="mb-0 list-unstyled long-list">
			@if (!empty($cities))
				@foreach ($cities as $iCity)
					<li class="py-1">
						@if (
							(
								isset($city)
								&& data_get($city, 'id') == data_get($iCity, 'id')
							)
							|| request()->input('l') == data_get($iCity, 'id')
							)
							<span class="fw-bold">
								{{ data_get($iCity, 'name') }}
								@if (config('settings.listings_list.count_cities_listings'))
									&nbsp;<span class="fw-normal">({{ data_get($iCity, 'posts_count') ?? 0 }})</span>
								@endif
							</span>
						@else
							<a href="{!! urlGen()->city($iCity, null, $cat ?? null) !!}"
							   class="{{ $linkClass }}"
							   title="{{ data_get($iCity, 'name') }}"
							>
								{{ data_get($iCity, 'name') }}
								@if (config('settings.listings_list.count_cities_listings'))
									&nbsp;<span class="fw-normal">({{ data_get($iCity, 'posts_count') ?? 0 }})</span>
								@endif
							</a>
						@endif
					</li>
				@endforeach
			@endif
		</ul>
	</div>
</div>
