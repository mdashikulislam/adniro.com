@php
	$countPostsPerCat ??= [];
	
	// Clear Filter Button
	$clearFilterBtn = urlGen()->getCategoryFilterClearLink($cat ?? null, $city ?? null);
	
	$showCatCount = (bool)config('settings.listings_list.count_categories_listings');
@endphp
@php
	// Use the expandable tree (root list) for root and second-level categories;
	// deeper levels keep the sibling list with a back link.
	$useCategoryTree = empty($cat) || empty(data_get($cat, 'parent.parent'));
@endphp
@if (!empty($cat) && !$useCategoryTree)
	@php
		$categoryParent = data_get($cat, 'parent') ?? null;
		$categoryChildren = data_get($cat, 'children') ?? [];
		$categoryParentOfParent = data_get($cat, 'parent.parent') ?? null;
		$categoryParentChildren = data_get($cat, 'parent.children') ?? [];
		
		$catParentUrl = urlGen()->parentCategory($categoryParent ?? null, $city ?? null);
		$catIcon = data_get($cat, 'icon_class') ?: 'bi bi-folder-fill';
		$catCount = $countPostsPerCat[data_get($cat, 'id')]['total'] ?? 0;
	@endphp
	
	{{-- SubCategory --}}
	<div id="subCatsList">
		@if (!empty($categoryChildren))
			
			<div class="container p-0 vstack gap-2 sidebar-section sidebar-section-categories">
				<h5 class="border-bottom pb-2 d-flex justify-content-between mb-0">
					<span class="fw-bold">
						@if (!empty($categoryParent))
							<a href="{{ urlGen()->category($categoryParent, null, $city ?? null) }}" class="filter-back-link">
								<i class="bi bi-arrow-left"></i> {{ data_get($cat, 'parent.name') }}
							</a>
						@else
							<a href="{{ $catParentUrl }}" class="filter-back-link">
								<i class="bi bi-arrow-left"></i> {{ t('all_categories') }}
							</a>
						@endif
					</span> {!! $clearFilterBtn !!}
				</h5>
				<ul class="mb-0 list-unstyled filter-list">
					{{-- Current category --}}
					<li class="active">
						<span class="filter-item">
							<span class="filter-item-icon"><i class="{{ $catIcon }}"></i></span>
							<span class="filter-item-name">{{ data_get($cat, 'name') }}</span>
							@if ($showCatCount)
								<span class="filter-item-count">{{ $catCount }}</span>
							@endif
						</span>
					</li>
				</ul>
				<ul class="mb-0 list-unstyled filter-list filter-list-children long-list">
					@foreach ($categoryChildren as $iSubCat)
						@php
							$iSubCatIcon = data_get($iSubCat, 'icon_class') ?: 'bi bi-folder';
							$iSubCatCount = $countPostsPerCat[data_get($iSubCat, 'id')]['total'] ?? 0;
						@endphp
						<li>
							<a href="{{ urlGen()->category($iSubCat, null, $city ?? null) }}" class="filter-item" title="{{ data_get($iSubCat, 'name') }}">
								<span class="filter-item-icon"><i class="{{ $iSubCatIcon }}"></i></span>
								<span class="filter-item-name">{{ str(data_get($iSubCat, 'name'))->limit(100) }}</span>
								@if ($showCatCount)
									<span class="filter-item-count">{{ $iSubCatCount }}</span>
								@endif
								<i class="bi bi-chevron-right filter-item-arrow"></i>
							</a>
						</li>
					@endforeach
				</ul>
			</div>
			
		@else
			
			@if (!empty($categoryParentChildren))
				<div class="container p-0 vstack gap-2 sidebar-section sidebar-section-categories">
					<h5 class="border-bottom pb-2 d-flex justify-content-between">
						<span class="fw-bold">
							@if (!empty($categoryParentOfParent))
								<a href="{{ urlGen()->category($categoryParentOfParent, null, $city ?? null) }}" class="filter-back-link">
									<i class="bi bi-arrow-left"></i> {{ data_get($cat, 'parent.parent.name') }}
								</a>
							@elseif (!empty($categoryParent))
								<a href="{{ urlGen()->category($categoryParent, null, $city ?? null) }}" class="filter-back-link">
									<i class="bi bi-arrow-left"></i> {{ data_get($cat, 'parent.name') }}
								</a>
							@else
								<a href="{{ $catParentUrl }}" class="filter-back-link">
									<i class="bi bi-arrow-left"></i> {{ t('all_categories') }}
								</a>
							@endif
						</span> {!! $clearFilterBtn !!}
					</h5>
					<ul class="mb-0 list-unstyled filter-list long-list">
						@foreach ($categoryParentChildren as $iSubCat)
							@php
								$isActiveSub = (data_get($iSubCat, 'id') == data_get($cat, 'id'));
								$iSubCatIcon = data_get($iSubCat, 'icon_class') ?: 'bi bi-folder';
								$iSubCatCount = $countPostsPerCat[data_get($iSubCat, 'id')]['total'] ?? 0;
							@endphp
							<li class="{{ $isActiveSub ? 'active' : '' }}">
								@if ($isActiveSub)
									<span class="filter-item">
										<span class="filter-item-icon"><i class="{{ $iSubCatIcon }}"></i></span>
										<span class="filter-item-name">{{ str(data_get($iSubCat, 'name'))->limit(100) }}</span>
										@if ($showCatCount)
											<span class="filter-item-count">{{ $iSubCatCount }}</span>
										@endif
									</span>
								@else
									<a href="{{ urlGen()->category($iSubCat, null, $city ?? null) }}" class="filter-item" title="{{ data_get($iSubCat, 'name') }}">
										<span class="filter-item-icon"><i class="{{ $iSubCatIcon }}"></i></span>
										<span class="filter-item-name">{{ str(data_get($iSubCat, 'name'))->limit(100) }}</span>
										@if ($showCatCount)
											<span class="filter-item-count">{{ $iSubCatCount }}</span>
										@endif
										<i class="bi bi-chevron-right filter-item-arrow"></i>
									</a>
								@endif
							</li>
						@endforeach
					</ul>
				</div>
			@else
				
				@include('front.search.partials.sidebar.categories.root', ['countPostsPerCat' => $countPostsPerCat])
			
			@endif
			
		@endif
	</div>
	
@else
	
	@include('front.search.partials.sidebar.categories.root', ['countPostsPerCat' => $countPostsPerCat])
	
@endif
