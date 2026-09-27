@php
	// Empty state shown when a search returns nothing
	$keyword = request()->query('q');
	$hasFilters = !empty(request()->except(['page', 'orderBy', 'display']));
	[$createListingLinkUrl, $createListingLinkAttr] = getCreateListingLinkInfo();
@endphp
<div class="search-empty">
	<div class="search-empty-art" aria-hidden="true">
		<span class="search-empty-ring"></span>
		<i class="bi bi-search"></i>
		<span class="search-empty-badge"><i class="bi bi-x-lg"></i></span>
	</div>
	<h3 class="search-empty-title">
		@if (!empty($keyword))
			{{ str_replace(['${query}', ':query'], $keyword, t('no_results_for', ['query' => $keyword])) }}
		@else
			{{ t('no_results') }}
		@endif
	</h3>
	<p class="search-empty-text">{{ t('no_result_refine_your_search') }}</p>
	<div class="search-empty-actions">
		@if ($hasFilters)
			<a href="{{ urlGen()->searchWithoutQuery() }}" class="btn btn-outline-primary search-empty-btn" rel="nofollow">
				<i class="bi bi-arrow-counterclockwise"></i> {{ t('Clear all') }}
			</a>
		@endif
		<a href="{{ $createListingLinkUrl }}" class="btn btn-primary section-cta-btn search-empty-btn"{!! $createListingLinkAttr !!}>
			<i class="fa-regular fa-pen-to-square"></i> {{ t('create_listing') }}
		</a>
	</div>
</div>
