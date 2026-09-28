@extends('front.layouts.master')
@php
	$apiResult ??= [];
	$apiExtra ??= [];
	$count = (array)data_get($apiExtra, 'count');
	$posts = (array)data_get($apiResult, 'data');
	$totalPosts = (int)data_get($apiResult, 'meta.total', 0);
	$tags = (array)data_get($apiExtra, 'tags');

	$postTypes ??= [];
	$orderByOptions ??= [];
	$displayModes ??= [];

	$isLeftSidebarEnabled = (config('settings.listings_list.show_left_sidebar') == '1');
	$breakpointKey = config('settings.listings_list.left_sidebar_offcanvas', 'sm');
	$pageBreakpoint = getSerpOffcanvasBreakpoint($breakpointKey, $isLeftSidebarEnabled);

	$selectedDisplayMode = config('settings.listings_list.display_mode', 'grid-view');
	$hideOnXsOrLower = 'd-none d-sm-block';
	$hideInlineOnMdOrLower = 'd-none d-lg-inline-block';
@endphp
@section('search')
	@parent
	@include('front.search.partials.form')
@endsection
@section('body_class', 'page-results')
@section('content')
	<div class="main-container search-results-page">
		@include('front.search.partials.breadcrumbs')

		{{-- SEO H1 Title --}}
		@php
			$cat ??= null;
			$city ??= null;
			$admin ??= null;
			$h1Title = '';

			// Build unique H1 title based on page type
			if (!empty($city)) {
				// City page
				if (!empty($cat)) {
					// Category + City
					$h1Title = data_get($cat, 'name') . ' ' . t('in') . ' ' . data_get($city, 'name');
					if (!empty(data_get($cat, 'parent'))) {
						$h1Title = data_get($cat, 'name') . ' ' . t('in') . ' ' . data_get($city, 'name') . ' - ' . data_get($cat, 'parent.name');
					}
				} else {
					// City only
					$h1Title = t('all_listings') . ' ' . t('in') . ' ' . data_get($city, 'name') . ', ' . config('country.name');
				}
			} elseif (!empty($admin)) {
				// State/Region page
				if (!empty($cat)) {
					// Category + State
					$h1Title = data_get($cat, 'name') . ' ' . t('in') . ' ' . data_get($admin, 'name');
				} else {
					// State only
					$h1Title = t('all_listings') . ' ' . t('in') . ' ' . data_get($admin, 'name') . ', ' . config('country.name');
				}
			} elseif (!empty($cat)) {
				// Category page (no location)
				if (!empty(data_get($cat, 'parent'))) {
					$h1Title = data_get($cat, 'name') . ' - ' . data_get($cat, 'parent.name') . ' ' . t('in') . ' ' . config('country.name');
				} else {
					$h1Title = data_get($cat, 'name') . ' ' . t('in') . ' ' . config('country.name');
				}
			} elseif (request()->filled('q')) {
				// Search query page
				$h1Title = t('blog_search_results_for', ['keyword' => request()->query('q')]);
			} else {
				// Default general search page
				$h1Title = t('all_listings') . ' ' . t('in') . ' ' . config('country.name');
			}
		@endphp

		<div class="container mb-3">
			<div class="row">
				<div class="col-12">
					<div class="page-title-wrapper results-title">
						<span class="results-title-icon"><i class="bi bi-search"></i></span>
						<h1 class="results-title-text m-0 p-0">{{ $h1Title }}</h1>
						@if (!empty($posts) && $totalPosts > 0 && isset($count) && data_get($count, '0') !== null)
							<span class="results-title-count ms-md-auto">
								<i class="bi bi-grid-3x3-gap-fill"></i>
								<strong>{{ number_format((int)data_get($count, '0')) }}</strong>
								{{ trans_choice('global.count_listings', (int)data_get($count, '0')) }}
							</span>
						@endif
						@if (config('settings.listing_form.show_listing_type') && !empty($postTypes))
						<div class="results-title-tabs">
						<ul class="nav nav-tabs results-tabs" id="postType">
				@php
					$linkActiveClass = '';
					$linkActiveAttr = '';
					$badgeClass = 'text-bg-secondary';
					if (config('settings.listing_form.show_listing_type')) {
						if (!request()->filled('type') || request()->query('type') == '') {
							$linkActiveClass = ' active fw-bold';
							$linkActiveAttr = ' aria-current="page"';
							$badgeClass = 'text-bg-danger';
						}
					} else {
						$linkActiveClass = ' active fw-bold';
						$linkActiveAttr = ' aria-current="page"';
						$badgeClass = 'text-bg-danger';
					}
				@endphp
				<li class="nav-item">
					<a href="{!! request()->fullUrlWithoutQuery(['page', 'type']) !!}"
					   class="nav-link fs-6{{ $linkActiveClass }}"{!! $linkActiveAttr !!}
					>
						{{ t('all_listings') }} <span class="badge {{ $badgeClass }}">{{ data_get($count, '0') }}</span>
					</a>
				</li>
				@if (config('settings.listing_form.show_listing_type'))
					@if (!empty($postTypes))
						@foreach ($postTypes as $postType)
							@php
								$postTypeId = data_get($postType, 'id');
								$postTypeUrl = request()->fullUrlWithQuery(['type' => $postTypeId, 'page' => null]);
								$postTypeCount = data_get($count, $postTypeId) ?? 0;
								$isSelectedPostType = (request()->filled('type') && request()->query('type') == $postTypeId);
							@endphp
							@if ($isSelectedPostType)
								<li class="nav-item">
									<a href="{!! $postTypeUrl !!}" class="nav-link fs-6 active fw-bold">
										{{ data_get($postType, 'label') }}
										<span class="badge text-bg-danger {{ $hideInlineOnMdOrLower }}">
											{{ $postTypeCount }}
										</span>
									</a>
								</li>
							@else
								<li class="nav-item">
									<a href="{!! $postTypeUrl !!}" class="nav-link fs-6">
										{{ data_get($postType, 'label') }}
										<span class="badge text-bg-secondary {{ $hideInlineOnMdOrLower }}">
											{{ $postTypeCount }}
										</span>
									</a>
								</li>
							@endif
						@endforeach
					@endif
				@endif
			</ul>
						</div>
						@endif
					</div>
				</div>
			</div>
		</div>

		@if (config('settings.listings_list.show_cats_in_top'))
			@if (!empty($cats))
				<div class="container mb-2 {{ $hideOnXsOrLower }}">
					<div class="row p-0 m-0">
						<div class="col-12 p-0 m-0 border-top"></div>
					</div>
				</div>
			@endif
			@include('front.search.partials.categories')
		@endif

		@if (!empty($topAdvertising))
			@include('front.layouts.partials.advertising.top', ['paddingTopExists' => true])
			@php
				$paddingTopExists = false;
			@endphp
		@else
			@php
				if (isset($paddingTopExists) && $paddingTopExists) {
					$paddingTopExists = false;
				}
			@endphp
		@endif

		<div class="container">
			<div class="row">
				{{-- Sidebar --}}
                @if ($isLeftSidebarEnabled)
                    @include('front.search.partials.sidebar', ['pageBreakpoint' => $pageBreakpoint])
                @endif

				{{-- Content --}}
				@php
					$rightColSize = data_get($pageBreakpoint, 'rightColSize') ?? ($isLeftSidebarEnabled ? 'col-md-9' : 'col-md-12');
					$showInlineOnSmallScreen = data_get($pageBreakpoint, 'showInlineOnSmallScreen') ?? ' d-inline-block d-md-none';
				@endphp
				<div class="{{ $rightColSize }} mb-4">
					<div class="{{ $selectedDisplayMode }}{{ str_ends_with($rightColSize, '-12') ? ' noSideBar' : '' }}">

						@if (!empty($posts) && $totalPosts > 0)
						{{-- Filters, OrderBy & Display Mode --}}
						<div class="container results-toolbar">
							<ul class="list-inline m-0 p-0 text-end d-flex align-items-center justify-content-end gap-2">
								{{-- Filter (Show/Hide Sidebar) | d-inline-block d-sm-inline-block d-md-none --}}
								@if ($isLeftSidebarEnabled)
									<li class="list-inline-item px-2{{ $showInlineOnSmallScreen }}">
										<a href="#"
										   class="text-uppercase {{ linkClass() }} navbar-toggler results-filter-btn"
										   data-bs-toggle="offcanvas"
										   data-bs-target="#smallScreenSidebar"
										   aria-controls="smallScreenSidebar"
										   aria-label="Toggle navigation"
										>
											<i class="fa-solid fa-bars"></i> {{ t('Filters') }}
										</a>
									</li>
								@endif

								{{-- OrderBy --}}
								<li class="list-inline-item px-2">
									<div class="dropdown">
										<a href="#" class="dropdown-toggle text-uppercase {{ linkClass() }} results-sort-btn" data-bs-toggle="dropdown" aria-expanded="false">
											<i class="bi bi-arrow-down-up"></i> {{ t('Sort by') }}
										</a>
										<ul class="dropdown-menu">
											@if (!empty($orderByOptions))
												@foreach($orderByOptions as $option)
													@if (data_get($option, 'condition'))
														@php
															$currentUrl = request()->fullUrl();
															$currentUrlWithoutOrderBy = urlBuilder($currentUrl)->removeParameter('orderBy')->toString();

															$optionQuery = (array)data_get($option, 'query');
															$optionUrl = urlBuilder($currentUrl)->setParameters($optionQuery)->toString();

															$optionParams = urlBuilder($optionUrl)->getAllParameters();
															$optionParams = collect($optionParams)->sortKeys()->toArray();
															$currentParams = urlBuilder(request()->fullUrl())->getAllParameters();
															$currentParams = collect($currentParams)->sortKeys()->toArray();

															$optionUrl = ($optionUrl == $currentUrlWithoutOrderBy) ? '#' : $optionUrl;
															$activeClass = ($optionParams == $currentParams) ? ' active' : '';
														@endphp
														<li>
															<a href="{!! $optionUrl !!}" class="dropdown-item{{ $activeClass }}" rel="nofollow">
																{{ data_get($option, 'label') }}
															</a>
														</li>
													@endif
												@endforeach
											@endif
										</ul>
									</div>
								</li>

								{{-- Display Modes --}}
								@if (!empty($posts) && $totalPosts > 0)
									<li class="list-inline-item px-2">
										@if (!empty($displayModes))
											<div class="btn-group results-view-switch">
												@foreach($displayModes as $displayMode => $value)
													@php
														$displayModeUrl = request()->fullUrlWithQuery((array)data_get($value, 'query'));
														$activeClass = ($selectedDisplayMode == $displayMode) ? ' active' : '';
														$activeAttr = ($selectedDisplayMode == $displayMode) ? ' aria-current="page"' : '';
													@endphp
													<a href="{!! $displayModeUrl !!}"
													   class="btn btn-outline-primary btn-sm{{ $activeClass }}"
													   rel="nofollow"{!! $activeAttr !!}
													>
														<i class="{{ data_get($value, 'icon') }}"></i>
													</a>
												@endforeach
											</div>
										@endif
									</li>
								@endif
							</ul>
						</div>
						@endif

						{{-- Listing List --}}
						<div class="tab-content bg-body" id="myTabContent">
							<div class="tab-pane fade show active" id="contentAll" role="tabpanel" aria-labelledby="tabAll">
								<div class="container results-list px-0">
									@if ($selectedDisplayMode == 'list-view')
										@include('front.search.partials.posts.template.list')
									@elseif ($selectedDisplayMode == 'compact-view')
										@include('front.search.partials.posts.template.compact')
									@else
										@include('front.search.partials.posts.template.grid',['customCols' => 3])
									@endif
								</div>
							</div>
						</div>

						{{-- Save Search Link --}}
						@php
							$keyword = request()->query('q');
							$searchCanBeSaved = (!empty($keyword) && data_get($count, '0') > 0);
						@endphp
						@if ($searchCanBeSaved)
							<div class="container py-2 mt-3 results-save-search text-center">
								<a id="saveSearch"
								   href=""
								   data-search-url="{!! request()->fullUrlWithoutQuery(['_token', 'location']) !!}"
								   data-results-count="{{ data_get($count, '0') }}"
								   class="{{ linkClass() }}"
								>
									<i class="bi bi-bell"></i> {{ t('Save Search') }}
								</a>
							</div>
						@endif
					</div>

					{{-- Pagination --}}
					@include('vendor.pagination.api.bootstrap-5')

				</div>
			</div>
		</div>

		{{-- Advertising --}}
		@include('front.layouts.partials.advertising.bottom')

		{{-- Promo Listing Button --}}
		@include('front.search.partials.call-to-action')

		{{-- Category Description --}}
		@include('front.search.partials.category-description')
{{--		@if(!request()->is('*/search'))--}}
{{--			@include('front.search.partials.content',['category'=> $cat, 'city' => $city])--}}
{{--		@else--}}

{{--		@endif--}}
		{{-- Show Posts Tags --}}
		@include('front.search.partials.tags')

	</div>
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection

@section('modal_location')
	@include('front.layouts.partials.modal.location')
@endsection

@section('after_scripts')
	<script>
		onDocumentReady((event) => {
			{{-- postType --}}
			const postTypeEl = document.querySelectorAll('#postType a');
			if (postTypeEl.length > 0) {
				postTypeEl.forEach((element) => {
					element.addEventListener('click', (event) => {
						event.preventDefault();

						let goToUrl = event.target.getAttribute('href');
						redirect(goToUrl);
					});
				});
			}
		});
	</script>
@endsection
