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
	
	$showCityCount = (bool)config('settings.listings_list.count_cities_listings');

	// Each city link opens a city page (a category×city page on a category page), whose
	// live listings count is the city's "posts_count" here (see SidebarTrait). Only the pages
	// with enough live listings deserve to be crawled; the others stay usable but are
	// marked "nofollow" so crawlers don't waste budget on them.
	$minListingsToIndex = \App\Helpers\Services\ListingCounts::minListingsToIndex();
@endphp
{{-- City --}}
<div class="container p-0 vstack gap-2 sidebar-section sidebar-section-cities">
	<h5 class="border-bottom pb-2 d-flex justify-content-between">
		<span class="fw-bold">{{ t('locations') }}</span> {!! $clearFilterBtn !!}
	</h5>
	<div>
		<ul class="mb-0 list-unstyled filter-list long-list">
			@if (!empty($cities))
				@foreach ($cities as $iCity)
					@php
						$isActiveCity = (
							(isset($city) && data_get($city, 'id') == data_get($iCity, 'id'))
							|| request()->input('l') == data_get($iCity, 'id')
						);
						$iCityCount = data_get($iCity, 'posts_count') ?? 0;
						// The city's page (or category×city page) is "noindex" below the threshold
						$isThinCombo = ((int)$iCityCount < $minListingsToIndex);
					@endphp
					<li class="{{ $isActiveCity ? 'active' : '' }}">
						<a href="{!! urlGen()->city($iCity, null, $cat ?? null) !!}" class="filter-item" title="{{ data_get($iCity, 'name') }}"{!! $isThinCombo ? ' rel="nofollow"' : '' !!}>
							<span class="filter-item-icon"><i class="bi bi-geo-alt{{ $isActiveCity ? '-fill' : '' }}"></i></span>
							<span class="filter-item-name">{{ data_get($iCity, 'name') }}</span>
							@if ($showCityCount)
								<span class="filter-item-count">{{ $iCityCount }}</span>
							@endif
							<i class="bi bi-chevron-right filter-item-arrow"></i>
						</a>
					</li>
				@endforeach
			@endif
		</ul>
	</div>
</div>
