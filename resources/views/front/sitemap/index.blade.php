{{--
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
--}}
{{--
 * adniro: the page redesigned (texts: lang/*/sitemap.php, styles: public/assets/css/sitemap.css).
 * A teal head with the page's figures and jump links; every category as a card with its icon and all of its
 * sub-categories (nothing folded behind "View more": it is a sitemap); the cities from A to Z under their
 * letter; then the blog's topics and latest articles when there are any.
--}}
@extends('front.layouts.master')

@php
	$cats = collect($cats ?? []);
	$cities = collect($cities ?? []);
	$blogCategories = collect($blogCategories ?? []);
	$blogPosts = collect($blogPosts ?? []);
	$countryName = (string)config('country.name');

	$subCatCount = $cats->sum(fn ($cat) => $cat->children?->count() ?? 0);

	// The cities from A to Z, under their first letter (they arrive biggest first)
	$cityGroups = $cities
		->sortBy(fn ($city) => mb_strtolower((string)$city->name))
		->groupBy(fn ($city) => mb_strtoupper(mb_substr((string)$city->name, 0, 1)));

	// The parts of the page that have something to show: their figure in the head and their jump link
	$parts = collect([
		['id' => 'sitemap-categories', 'icon' => 'bi-grid', 'count' => $cats->count(), 'label' => trans('sitemap.stat_categories')],
		['id' => 'sitemap-categories', 'icon' => 'bi-diagram-3', 'count' => $subCatCount, 'label' => trans('sitemap.stat_subcategories'), 'jump' => false],
		['id' => 'sitemap-cities', 'icon' => 'bi-geo-alt', 'count' => $cities->count(), 'label' => trans('sitemap.stat_cities')],
		['id' => 'sitemap-blog', 'icon' => 'bi-journal-text', 'count' => $blogPosts->count(), 'label' => trans('sitemap.stat_articles')],
	])->filter(fn ($part) => $part['count'] > 0)->values();
@endphp

@push('after_styles_stack')
	<link href="{{ url('assets/css/sitemap.css') }}?v={{ @filemtime(public_path('assets/css/sitemap.css')) }}" rel="stylesheet">
@endpush

@section('search')
	@parent
@endsection

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container sm-page">

			{{-- Head: what the page is, its figures, and links to its parts --}}
			<header class="sm-hero">
				<div class="sm-hero-main">
					<span class="sm-hero-badge" aria-hidden="true"><i class="bi bi-signpost-split-fill"></i></span>
					<div class="sm-hero-text">
						<h1 class="sm-hero-title">{{ t('sitemap') }}</h1>
						<p class="sm-hero-intro">{{ trans('sitemap.intro', ['app' => config('settings.app.name'), 'country' => $countryName]) }}</p>
					</div>
				</div>

				<div class="sm-stats">
					@foreach ($parts as $part)
						<div class="sm-stat">
							<span class="sm-stat-icon" aria-hidden="true"><i class="bi {{ $part['icon'] }}"></i></span>
							<span class="sm-stat-value">{{ $part['count'] }}</span>
							<span class="sm-stat-label">{{ $part['label'] }}</span>
						</div>
					@endforeach
				</div>

				<nav class="sm-jump" aria-label="{{ trans('sitemap.jump') }}">
					<span class="sm-jump-label">{{ trans('sitemap.jump') }}</span>
					@foreach ($parts->where('jump', '!==', false) as $part)
						<a href="#{{ $part['id'] }}" class="sm-jump-link">
							<i class="bi {{ $part['icon'] }}" aria-hidden="true"></i> {{ $part['label'] }}
						</a>
					@endforeach
				</nav>
			</header>

			{{-- Categories: a card each, with every sub-category --}}
			@if ($cats->isNotEmpty())
				<section class="sm-section" id="sitemap-categories" aria-labelledby="sitemapCategoriesTitle">
					<header class="sm-section-head">
						<span class="sm-section-icon" aria-hidden="true"><i class="bi bi-grid-fill"></i></span>
						<div class="sm-section-text">
							<h2 class="sm-section-title" id="sitemapCategoriesTitle">{{ trans('sitemap.categories_title') }}</h2>
							<p class="sm-section-sub">{{ trans('sitemap.categories_sub') }}</p>
						</div>
						<span class="sm-section-count">{{ $cats->count() }}</span>
					</header>

					<div class="sm-cats">
						@foreach ($cats as $iCat)
							@php
								$catUrl = urlGen()->category($iCat);
								$catIconClass = !empty($iCat->icon_class) ? $iCat->icon_class : 'bi bi-tag';
								$children = collect($iCat->children ?? []);
							@endphp
							<article class="sm-cat" id="sitemap-cat-{{ $iCat->id }}">
								<header class="sm-cat-head">
									<span class="sm-cat-icon" aria-hidden="true"><i class="{{ $catIconClass }}"></i></span>
									<h3 class="sm-cat-title"><a href="{{ $catUrl }}">{{ $iCat->name }}</a></h3>
									@if ($children->isNotEmpty())
										<span class="sm-cat-count">{{ $children->count() }}</span>
										<span class="visually-hidden">{{ trans('sitemap.stat_subcategories') }}</span>
									@endif
								</header>
								@if ($children->isNotEmpty())
									<ul class="sm-cat-list" role="list">
										@foreach ($children as $iSubCat)
											<li class="sm-cat-item">
												<a href="{{ urlGen()->category($iSubCat) }}" class="sm-cat-link">
													<i class="bi bi-chevron-right" aria-hidden="true"></i>
													<span>{{ $iSubCat->name }}</span>
												</a>
											</li>
										@endforeach
									</ul>
								@endif
								<a href="{{ $catUrl }}" class="sm-cat-all">
									{{ trans('sitemap.all_in', ['category' => $iCat->name]) }}
									<i class="bi bi-arrow-right" aria-hidden="true"></i>
								</a>
							</article>
						@endforeach
					</div>
				</section>
			@endif

			{{-- Cities: from A to Z, under their letter --}}
			@if ($cities->isNotEmpty())
				<section class="sm-section" id="sitemap-cities" aria-labelledby="sitemapCitiesTitle">
					<header class="sm-section-head">
						<span class="sm-section-icon" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>
						<div class="sm-section-text">
							<h2 class="sm-section-title" id="sitemapCitiesTitle">{{ trans('sitemap.cities_title', ['country' => $countryName]) }}</h2>
							<p class="sm-section-sub">{{ trans('sitemap.cities_sub') }}</p>
						</div>
						<span class="sm-section-count">{{ $cities->count() }}</span>
					</header>

					<div class="sm-panel">
						<div class="sm-letters">
							@foreach ($cityGroups as $letter => $letterCities)
								<div class="sm-letter-group">
									<h3 class="sm-letter">{{ $letter }}</h3>
									<ul class="sm-city-list" role="list">
										@foreach ($letterCities as $city)
											<li>
												<a href="{{ urlGen()->city($city) }}" class="sm-city" title="{{ t('Free Listings') . ' ' . $city->name }}">
													<i class="bi bi-geo-alt" aria-hidden="true"></i>
													<span class="sm-city-name">{{ $city->name }}</span>
												</a>
											</li>
										@endforeach
									</ul>
								</div>
							@endforeach
						</div>
					</div>
				</section>
			@endif

			{{-- Blog: its topics, then the latest articles --}}
			@if ($blogPosts->isNotEmpty())
				<section class="sm-section" id="sitemap-blog" aria-labelledby="sitemapBlogTitle">
					<header class="sm-section-head">
						<span class="sm-section-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
						<div class="sm-section-text">
							<h2 class="sm-section-title" id="sitemapBlogTitle">{{ trans('sitemap.blog_title') }}</h2>
							<p class="sm-section-sub">{{ trans('sitemap.blog_sub') }}</p>
						</div>
						<span class="sm-section-count">{{ $blogPosts->count() }}</span>
					</header>

					<div class="sm-panel">
						@if ($blogCategories->isNotEmpty())
							<div class="sm-topics">
								@foreach ($blogCategories as $blogCategory)
									<a href="{{ $blogCategory->url }}" class="sm-topic">
										<i class="bi bi-folder2-open" aria-hidden="true"></i> {{ $blogCategory->name }}
									</a>
								@endforeach
							</div>
						@endif

						<ul class="sm-posts" role="list">
							@foreach ($blogPosts as $blogPost)
								<li>
									<a href="{{ $blogPost->url }}" class="sm-post">
										<i class="bi bi-file-earmark-text" aria-hidden="true"></i>
										<span>{{ str($blogPost->title)->limit(60) }}</span>
									</a>
								</li>
							@endforeach
						</ul>
					</div>
				</section>
			@endif

			@include('front.layouts.partials.social.horizontal')
		</div>
	</div>
@endsection
