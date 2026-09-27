{{-- Category --}}
@if (!empty($cats))
	@php
		$countPostsPerCat ??= [];
		$showCatCount = (bool)config('settings.listings_list.count_categories_listings');
	@endphp
	<div id="catsList">
		<div class="container p-0 vstack gap-2 sidebar-section sidebar-section-categories">
			<h5 class="border-bottom pb-2 d-flex justify-content-between">
				<span class="fw-bold">{{ t('all_categories') }}</span> {!! $clearFilterBtn ?? '' !!}
			</h5>
			<ul class="mb-0 list-unstyled filter-list long-list">
				@foreach ($cats as $iCat)
					@php
						$isActiveCat = (isset($cat) && data_get($iCat, 'id') == data_get($cat, 'id'));
						$iCatIcon = data_get($iCat, 'icon_class') ?: 'bi bi-folder-fill';
						$iCatCount = $countPostsPerCat[data_get($iCat, 'id')]['total'] ?? 0;
					@endphp
					<li class="{{ $isActiveCat ? 'active' : '' }}">
						<a href="{{ urlGen()->category($iCat, null, $city ?? null) }}" class="filter-item" title="{{ data_get($iCat, 'name') }}">
							<span class="filter-item-icon"><i class="{{ $iCatIcon }}"></i></span>
							<span class="filter-item-name">{{ data_get($iCat, 'name') }}</span>
							@if ($showCatCount)
								<span class="filter-item-count">{{ $iCatCount }}</span>
							@endif
							<i class="bi bi-chevron-right filter-item-arrow"></i>
						</a>
					</li>
				@endforeach
			</ul>
		</div>
	</div>
@endif
