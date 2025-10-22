@php
	$categories ??= [];
	$isCountPostsEnabled = (config('settings.listings_list.count_categories_listings') == '1');
@endphp
@if (!empty($categories))
	<div class="row row-cols-lg-6 row-cols-md-4 row-cols-sm-3 row-cols-2 py-1 px-0 picture-category-list">
		@foreach($categories as $cat)
			@php
				$catId = data_get($cat, 'id', 0);
				$catImgUrl = data_get($cat, 'image_url', '');
				$catName = data_get($cat, 'name', '--');
				
				$catCountPosts = $isCountPostsEnabled
					? '(' . ($countPostsPerCat[$catId]['total'] ?? 0) . ')'
					: '';
				$catDisplayName = !empty($catCountPosts) ? $catName . ' ' . $catCountPosts : $catName;
			@endphp
			<div class="col px-0 d-flex justify-content-center align-content-stretch">
				<div class="text-center w-100 border rounded px-3 py-4 m-1">
					<a href="{{ urlGen()->category($cat) }}" class="{{ linkClass() }}">
						<img src="{{ $catImgUrl }}" class="lazyload img-fluid" alt="{{ $catName }}">
						<h6 class="mt-2 fw-bold">{{ $catDisplayName }}</h6>
					</a>
				</div>
			</div>
		@endforeach
	</div>
@endif
