@php
	$categories ??= [];
	$subCategories ??= [];
	$isCountPostsEnabled = (config('settings.listings_list.count_categories_listings') == '1');
	$isShowingCategoryIconEnabled = in_array(config('settings.listings_list.show_category_icon'), [2, 6, 7, 8]);
	
	$catDisplayType ??= 'cc_normal_list';
	
	$wrapperClass = ' my-4 px-3';
	$itemClass = '';
	$itemBorderClass = '';
	$h5BgColor = '';
	$subCatIcon = '<i class="bi bi-dash"></i> ';
	if ($catDisplayType == 'cc_normal_list_s') {
		$wrapperClass = ' my-2 px-1';
		$itemClass = ' px-0';
		$itemBorderClass = ' border rounded';
		$h5BgColor = ' bg-body-secondary rounded px-3 py-2';
		$subCatIcon = '<i class="bi bi-dash"></i> ';
	}
@endphp

@if (!empty($categories))
	<div class="row row-cols-lg-3 row-cols-md-2 row-cols-sm-1 row-cols-1{{ $wrapperClass }} nested-category-list">
		@foreach ($categories as $key => $iCat)
			@php
				$randomId = '-' . generateRandomString(5);
				
				$catId = data_get($iCat, 'id', 0);
				$domElId = $catId . $randomId;
				$catIconClass = $isShowingCategoryIconEnabled ? data_get($iCat, 'icon_class', 'fa-solid fa-check') : '';
				$catIcon = !empty($catIconClass) ? '<i class="' . $catIconClass . '"></i> ' : '';
				$catName = data_get($iCat, 'name', '--');
				
				$catCountPosts = $isCountPostsEnabled
					? ' (' . ($countPostsPerCat[$catId]['total'] ?? 0) . ')'
					: '';
				$catDisplayName = !empty($catCountPosts) ? $catName . ' ' . $catCountPosts : $catName;
				
				// Subcategories of the current category
				$catSubCats = $subCategories[$catId] ?? [];
			@endphp
			<div class="col d-flex justify-content-center align-content-stretch{{ $itemClass }}">
				<div class="w-100 p-3 m-1{{ $itemBorderClass }}">
					<h5 class="fs-5 fw-bold d-flex justify-content-between{{ $h5BgColor }}">
						<span>
							{!! $catIcon !!}<a href="{{ urlGen()->category($iCat) }}" class="{{ linkClass() }}">
								{{ $catDisplayName }}
							</a>
						</span>
						@if ($catSubCats)
							<a class="{{ linkClass('body-emphasis') }}"
							   data-bs-toggle="collapse"
							   data-bs-target="#parentCat{{ $domElId }}"
							   href="#parentCat{{ $domElId }}"
							   role="button"
							   aria-expanded="false"
							   aria-controls="parentCat{{ $domElId }}"
							>
								<i class="bi bi-chevron-down"></i>
							</a>
						@else
							<i class="bi bi-chevron-down"></i>
						@endif
					</h5>
					@if ($catSubCats)
						<div class="collapse show ms-3" id="parentCat{{ $domElId }}">
							<ul class="list-unstyled long-list-home">
								@foreach ($catSubCats as $iSubCat)
									@php
										$subCatId = data_get($iSubCat, 'id', 0);
										$subCatName = data_get($iSubCat, 'name', '--');
										$subCatCountPosts = $isCountPostsEnabled
											? ' (' . ($countPostsPerCat[$subCatId]['total'] ?? 0) . ')'
											: '';
									@endphp
									<li class="py-1">
										{!! $subCatIcon !!}<a href="{{ urlGen()->category($iSubCat) }}" class="{{ linkClass() }}">
											{{ $subCatName }}
										</a>{{ $subCatCountPosts }}
									</li>
								@endforeach
							</ul>
						</div>
					@endif
				</div>
			</div>
		@endforeach
	</div>
@endif
