@php
	$categories ??= [];
	$isCountPostsEnabled = (config('settings.listings_list.count_categories_listings') == '1');
	$isShowingCategoryIconEnabled = in_array(config('settings.listings_list.show_category_icon'), [2, 6, 7, 8]);
	
	$catDisplayType ??= 'c_normal_list';
	
	$listTypes = ['c_border_list' => 'border-bottom pb-2'];
	$borderBottom = $listTypes[$catDisplayType] ?? '';
	$borderBottom = !empty($borderBottom) ? ' ' . $borderBottom : '';
@endphp
@if (!empty($categories))
	<ul class="row row-cols-lg-3 row-cols-md-2 row-cols-sm-1 row-cols-1 my-4 list-unstyled category-list">
		@foreach ($categories as $key => $cat)
			@php
				$catId = data_get($cat, 'id', 0);
				$catIconClass = $isShowingCategoryIconEnabled ? data_get($cat, 'icon_class', 'fa-solid fa-check') : '';
				$catIcon = !empty($catIconClass) ? '<i class="' . $catIconClass . '"></i> ' : '';
				$catName = data_get($cat, 'name', '--');
				
				$catCountPosts = $isCountPostsEnabled
					? ' (' . ($countPostsPerCat[$catId]['total'] ?? 0) . ')'
					: '';
			@endphp
			<li class="col my-2 px-4 d-flex justify-content-center align-content-stretch">
				<div class="w-100{{ $borderBottom }}">
					{!! $catIcon !!}
					<a href="{{ urlGen()->category($cat) }}" class="{{ linkClass('body-emphasis') }}">
						{{ $catName }}
					</a>{{ $catCountPosts }}
				</div>
			</li>
		@endforeach
	</ul>
@endif
