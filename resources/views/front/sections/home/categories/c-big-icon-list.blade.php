@php
	$categories ??= [];
	$isCountPostsEnabled = (config('settings.listings_list.count_categories_listings') == '1');
	$isShowingCategoryIconEnabled = in_array(config('settings.listings_list.show_category_icon'), [2, 6, 7, 8]);
@endphp
@if (!empty($categories))
	<div class="row row-cols-lg-6 row-cols-md-4 row-cols-sm-3 row-cols-2 g-3 py-1 px-0 big-icon-category-list">
		@foreach($categories as $cat)
			@php
				$catId = data_get($cat, 'id', 0);
				$catIconClass = $isShowingCategoryIconEnabled ? data_get($cat, 'icon_class', 'bi bi-folder-fill') : '';
				$catIcon = !empty($catIconClass) ? '<i class="' . $catIconClass . '"></i>' : '';
				$catName = data_get($cat, 'name', '--');
				
				$catCountPosts = $isCountPostsEnabled
					? ($countPostsPerCat[$catId]['total'] ?? 0)
					: null;
			@endphp
			<div class="col d-flex justify-content-center align-content-stretch">
				<div class="cat-card w-100 text-center">
					<a href="{{ urlGen()->category($cat) }}" class="{{ linkClass() }} cat-card-link d-flex flex-column align-items-center justify-content-center h-100">
						@if (!empty($catIcon))
							<span class="cat-card-icon">{!! $catIcon !!}</span>
						@endif
						<h6 class="cat-card-title mb-0 fw-bold">{{ $catName }}</h6>
						@if (!is_null($catCountPosts))
							<span class="cat-card-count">{{ number_format((int)$catCountPosts) }} {{ trans_choice('global.count_listings', $catCountPosts) }}</span>
						@endif
					</a>
				</div>
			</div>
		@endforeach
	</div>
@endif
