{{-- Category --}}
@if (!empty($cats))
	@php
		$countPostsPerCat ??= [];
		$showCatCount = (bool)config('settings.listings_list.count_categories_listings');
		$currentCatId = isset($cat) ? data_get($cat, 'id') : null;
		$currentParentId = isset($cat) ? data_get($cat, 'parent_id') : null;
	@endphp
	<div id="catsList">
		<div class="container p-0 vstack gap-2 sidebar-section sidebar-section-categories">
			<h5 class="border-bottom pb-2 d-flex justify-content-between">
				<span class="fw-bold">{{ t('all_categories') }}</span> {!! $clearFilterBtn ?? '' !!}
			</h5>
			<ul class="mb-0 list-unstyled filter-list{{ empty($currentCatId) ? ' long-list' : '' }}">
				@foreach ($cats as $iCat)
					@php
						$iCatId = data_get($iCat, 'id');
						$iCatChildren = data_get($iCat, 'children_closure') ?? data_get($iCat, 'childrenClosure') ?? data_get($iCat, 'children') ?? [];
						$iCatChildren = is_iterable($iCatChildren) ? collect($iCatChildren) : collect();
						$hasChildren = $iCatChildren->isNotEmpty();
						$isActiveCat = ($currentCatId == $iCatId);
						$isOpen = $hasChildren && ($isActiveCat || $currentParentId == $iCatId);
						$iCatIcon = data_get($iCat, 'icon_class') ?: 'bi bi-folder-fill';
						$iCatCount = $countPostsPerCat[$iCatId]['total'] ?? 0;
					@endphp
					<li class="{{ $isActiveCat ? 'active' : '' }}{{ $hasChildren ? ' has-children' : '' }}{{ $isOpen ? ' is-open' : '' }}">
						<div class="filter-row">
							<a href="{{ urlGen()->category($iCat, null, $city ?? null) }}" class="filter-item" title="{{ data_get($iCat, 'name') }}">
								<span class="filter-item-icon"><i class="{{ $iCatIcon }}"></i></span>
								<span class="filter-item-name">{{ data_get($iCat, 'name') }}</span>
								@if ($showCatCount)
									<span class="filter-item-count">{{ $iCatCount }}</span>
								@endif
								@if (!$hasChildren)
									<i class="bi bi-chevron-right filter-item-arrow"></i>
								@endif
							</a>
							@if ($hasChildren)
								<button type="button"
								        class="filter-toggle"
								        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
								        aria-label="{{ data_get($iCat, 'name') }}"
								>
									<i class="bi bi-chevron-down"></i>
								</button>
							@endif
						</div>
						@if ($hasChildren)
							<ul class="list-unstyled filter-sublist">
								@foreach ($iCatChildren as $iSubCat)
									@php
										$iSubCatId = data_get($iSubCat, 'id');
										$iSubCatIcon = data_get($iSubCat, 'icon_class') ?: 'bi bi-folder';
										$iSubCatCount = $countPostsPerCat[$iSubCatId]['total'] ?? 0;
									@endphp
									@php
										$isActiveSub = ($currentCatId == $iSubCatId);
										$subChildren = ($isActiveSub && isset($cat)) ? collect(data_get($cat, 'children') ?? []) : collect();
									@endphp
									<li class="{{ $isActiveSub ? 'active' : '' }}{{ $subChildren->isNotEmpty() ? ' has-children is-open' : '' }}">
										<a href="{{ urlGen()->category($iSubCat, null, $city ?? null) }}" class="filter-item" title="{{ data_get($iSubCat, 'name') }}">
											<span class="filter-item-icon"><i class="{{ $iSubCatIcon }}"></i></span>
											<span class="filter-item-name">{{ data_get($iSubCat, 'name') }}</span>
											@if ($showCatCount)
												<span class="filter-item-count">{{ $iSubCatCount }}</span>
											@endif
											<i class="bi bi-chevron-right filter-item-arrow"></i>
										</a>
										@if ($subChildren->isNotEmpty())
											<ul class="list-unstyled filter-sublist">
												@foreach ($subChildren as $iSubSubCat)
													<li>
														<a href="{{ urlGen()->category($iSubSubCat, null, $city ?? null) }}" class="filter-item" title="{{ data_get($iSubSubCat, 'name') }}">
															<span class="filter-item-icon"><i class="{{ data_get($iSubSubCat, 'icon_class') ?: 'bi bi-folder' }}"></i></span>
															<span class="filter-item-name">{{ data_get($iSubSubCat, 'name') }}</span>
															@if ($showCatCount)
																<span class="filter-item-count">{{ $countPostsPerCat[data_get($iSubSubCat, 'id')]['total'] ?? 0 }}</span>
															@endif
															<i class="bi bi-chevron-right filter-item-arrow"></i>
														</a>
													</li>
												@endforeach
											</ul>
										@endif
									</li>
								@endforeach
							</ul>
						@endif
					</li>
				@endforeach
			</ul>
		</div>
	</div>
@endif
