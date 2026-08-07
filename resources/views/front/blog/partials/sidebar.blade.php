@php
	$sidebarCategories ??= collect();
	$sidebarPosts ??= collect();
	$keyword ??= null;
	$category ??= null;
	$currentCategoryId = data_get($category, 'id');
@endphp
<aside class="blog-sidebar">
	{{-- Search --}}
	<div class="card border shadow-sm mb-4">
		<div class="card-body">
			<form action="{{ urlGen()->blog() }}" method="GET" role="search">
				<div class="input-group">
					<input type="text"
					       name="q"
					       class="form-control"
					       value="{{ $keyword }}"
					       placeholder="{{ t('blog_search_placeholder') }}"
					       aria-label="{{ t('blog_search_placeholder') }}"
					>
					<button class="btn btn-primary" type="submit">
						<i class="fa-solid fa-magnifying-glass"></i>
					</button>
				</div>
			</form>
		</div>
	</div>

	{{-- Categories --}}
	@if ($sidebarCategories->count() > 0)
		<div class="card border shadow-sm mb-4">
			<div class="card-header bg-body-tertiary fw-bold">
				{{ t('blog_categories') }}
			</div>
			<ul class="list-group list-group-flush">
				<li class="list-group-item {{ empty($currentCategoryId) ? 'active' : '' }}">
					<a href="{{ urlGen()->blog() }}"
					   class="{{ empty($currentCategoryId) ? 'text-white text-decoration-none' : linkClass('body-emphasis') }}"
					>
						{{ t('blog_all_posts') }}
					</a>
				</li>
				@foreach($sidebarCategories as $sidebarCategory)
					@php $isActive = ($currentCategoryId == $sidebarCategory->id); @endphp
					<li class="list-group-item d-flex justify-content-between align-items-center {{ $isActive ? 'active' : '' }}">
						<a href="{{ $sidebarCategory->url }}"
						   class="{{ $isActive ? 'text-white text-decoration-none' : linkClass('body-emphasis') }}"
						>
							{{ $sidebarCategory->name }}
						</a>
						<span class="badge bg-secondary rounded-pill">{{ (int)$sidebarCategory->posts_count }}</span>
					</li>
				@endforeach
			</ul>
		</div>
	@endif

	{{-- Recent posts --}}
	@if ($sidebarPosts->count() > 0)
		<div class="card border shadow-sm mb-4">
			<div class="card-header bg-body-tertiary fw-bold">
				{{ t('blog_recent_posts') }}
			</div>
			<ul class="list-group list-group-flush">
				@foreach($sidebarPosts as $sidebarPost)
					<li class="list-group-item">
						<div class="d-flex gap-2">
							@if (!empty($sidebarPost->thumbnail_url))
								<a href="{{ $sidebarPost->url }}" class="flex-shrink-0">
									<img src="{{ $sidebarPost->thumbnail_url }}"
									     alt="{{ str($sidebarPost->title)->slug() }}"
									     loading="lazy"
									     style="width: 70px; height: 55px; object-fit: cover; border-radius: 4px;"
									>
								</a>
							@endif
							<div>
								<a href="{{ $sidebarPost->url }}" class="{{ linkClass('body-emphasis') }} d-block small fw-semibold">
									{{ str($sidebarPost->title)->limit(60) }}
								</a>
								<span class="text-body-secondary" style="font-size: 0.75rem;">
									{{ $sidebarPost->published_at?->translatedFormat('M d, Y') }}
								</span>
							</div>
						</div>
					</li>
				@endforeach
			</ul>
		</div>
	@endif
</aside>
