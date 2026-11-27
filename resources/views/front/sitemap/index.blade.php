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
 * adniro: the page redesigned in the homepage's own language (spec: replica/design/components.md).
 * Texts: lang/[locale]/sitemap.php. Styles: public/assets/css/sitemap.css. Search: public/assets/js/app/sitemap.js.
 *
 * - Head: the homepage hero in small (night gradient, white uppercase title, white pill search bar), with the
 *   page's figures on a bar that overlaps its lower edge. The search filters the page as one types.
 * - Categories: cards on a grid, each as wide as its list is long (App\Helpers\Common\SitemapBento), with the
 *   category's icon and every sub-category: nothing is folded behind "View more", it is a sitemap.
 * - Cities: the homepage's "Choose a city" tiles (.city-chip, styled in custom.css), from A to Z (they arrive
 *   biggest first). As many as Admin > Settings > Pagination allows ("cities_limit").
 * - Blog: its topics and latest articles, when there are any.
--}}
@extends('front.layouts.master')

@php
	use App\Helpers\Common\SitemapBento;

	$cats = collect($cats ?? [])->values();
	$cities = collect($cities ?? []);
	$blogCategories = collect($blogCategories ?? []);
	$blogPosts = collect($blogPosts ?? []);
	$appName = (string)config('settings.app.name');
	$countryName = (string)config('country.name');

	// A title's words between two * are set in bold, like the homepage's section titles
	$title = fn (string $text) => preg_replace('/\*(.+?)\*/u', '<span class="fw-bold">$1</span>', e($text));

	// How wide each category card is on a grid of 4, 3 and 2 columns
	$childCounts = $cats->map(fn ($cat) => collect($cat->children ?? [])->count());
	$subCatCount = $childCounts->sum();
	$spansByColumns = collect([4, 3, 2])->mapWithKeys(fn ($columns) => [$columns => SitemapBento::spans($childCounts->all(), $columns)]);

	// The cities from A to Z
	$citiesByName = $cities->sortBy(fn ($city) => mb_strtolower((string)$city->name))->values();

	// The figures on the head's bar; the ones with a part of the page to go to are links
	$stats = collect([
		['icon' => 'bi-grid', 'count' => $cats->count(), 'label' => trans('sitemap.stat_categories'), 'target' => 'sitemap-categories'],
		['icon' => 'bi-diagram-3', 'count' => $subCatCount, 'label' => trans('sitemap.stat_subcategories'), 'target' => null],
		['icon' => 'bi-geo-alt', 'count' => $cities->count(), 'label' => trans('sitemap.stat_cities'), 'target' => 'sitemap-cities'],
		['icon' => 'bi-journal-text', 'count' => $blogPosts->count(), 'label' => trans('sitemap.stat_articles'), 'target' => 'sitemap-blog'],
	])->filter(fn ($stat) => $stat['count'] > 0)->values();
@endphp

@push('after_styles_stack')
	<link href="{{ url('assets/css/sitemap.css') }}?v={{ @filemtime(public_path('assets/css/sitemap.css')) }}" rel="stylesheet">
@endpush

@push('after_scripts_stack')
	<script src="{{ url('assets/js/app/sitemap.js') }}?v={{ @filemtime(public_path('assets/js/app/sitemap.js')) }}" defer></script>
@endpush

@section('search')
	@parent
@endsection

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container sm-page" id="sitemapPage">

			{{-- Head --}}
			<header class="sm-hero">
				<div class="sm-hero-decor" aria-hidden="true">
					<i class="bi bi-geo-alt-fill sm-hero-pin sm-hero-pin--1"></i>
					<i class="bi bi-geo-alt-fill sm-hero-pin sm-hero-pin--2"></i>
					<i class="bi bi-geo-alt-fill sm-hero-pin sm-hero-pin--3"></i>
				</div>

				<div class="sm-hero-inner">
					<p class="sm-hero-eyebrow">
						<i class="bi bi-compass" aria-hidden="true"></i>
						<span>{{ $appName }}</span>
						<span class="sm-hero-eyebrow-dot" aria-hidden="true"></span>
						<span>{{ $countryName }}</span>
					</p>
					<h1 class="sm-hero-title">{{ t('sitemap') }}</h1>
					<p class="sm-hero-intro">{{ trans('sitemap.intro', ['app' => $appName, 'country' => $countryName]) }}</p>

					{{-- Shown by sitemap.js, which filters the page as one types --}}
					<form class="sm-search" role="search" hidden
					      data-sm-search
					      data-found="{{ trans('sitemap.found') }}"
					      data-none="{{ trans('sitemap.no_results_title') }}">
						<label class="visually-hidden" for="sitemapSearch">{{ trans('sitemap.search_label') }}</label>
						<i class="bi bi-search sm-search-icon" aria-hidden="true"></i>
						<input type="search" id="sitemapSearch" class="sm-search-input" autocomplete="off" spellcheck="false"
						       placeholder="{{ trans('sitemap.search_placeholder') }}" data-sm-search-input>
						<button type="button" class="sm-search-clear" hidden data-sm-search-clear aria-label="{{ trans('sitemap.search_clear') }}">
							<i class="bi bi-x-lg" aria-hidden="true"></i>
						</button>
						<button type="submit" class="sm-search-button">
							<i class="bi bi-search" aria-hidden="true"></i>
							<span>{{ trans('sitemap.search_button') }}</span>
						</button>
					</form>
					<p class="sm-search-status" role="status" aria-live="polite" data-sm-search-status></p>
				</div>
			</header>

			{{-- The page's figures, over the head's lower edge --}}
			<nav class="sm-stats" aria-label="{{ trans('sitemap.jump') }}">
				@foreach ($stats as $stat)
					@php
						$statTag = $stat['target'] ? 'a' : 'div';
					@endphp
					<{{ $statTag }} @class(['sm-stat', 'sm-stat--plain' => !$stat['target']]) @if ($stat['target']) href="#{{ $stat['target'] }}" @endif>
						<span class="sm-stat-icon" aria-hidden="true"><i class="bi {{ $stat['icon'] }}"></i></span>
						<span class="sm-stat-text">
							<span class="sm-stat-value">{{ $stat['count'] }}</span>
							<span class="sm-stat-label">{{ $stat['label'] }}</span>
						</span>
						@if ($stat['target'])
							<i class="bi bi-arrow-down-short sm-stat-arrow" aria-hidden="true"></i>
						@endif
					</{{ $statTag }}>
				@endforeach
			</nav>

			<div id="sitemapResults">

				{{-- Categories --}}
				@if ($cats->isNotEmpty())
					<section class="sm-section" id="sitemap-categories" aria-labelledby="sitemapCategoriesTitle" data-sm-section>
						<header class="sm-section-head">
							<h2 class="h4 mb-0 fw-lighter section-title" id="sitemapCategoriesTitle">{!! $title(trans('sitemap.categories_title')) !!}</h2>
							<span class="sm-section-meta">
								<i class="bi bi-grid" aria-hidden="true"></i> {{ $cats->count() }} {{ trans('sitemap.stat_categories') }}
							</span>
						</header>

						<div class="sm-cats">
							@foreach ($cats as $index => $iCat)
								@php
									$catUrl = urlGen()->category($iCat);
									$catIconClass = !empty($iCat->icon_class) ? $iCat->icon_class : 'bi bi-tag';
									$children = collect($iCat->children ?? []);
									$spanStyle = $spansByColumns->map(fn ($spans, $columns) => "--sm-span-{$columns}: " . ($spans[$index] ?? 1))->implode('; ');
								@endphp
								<article class="sm-cat" id="sitemap-cat-{{ $iCat->id }}" style="{{ $spanStyle }}" data-sm-group>
									<header class="sm-cat-head">
										<span class="sm-cat-icon" aria-hidden="true"><i class="{{ $catIconClass }}"></i></span>
										<div class="sm-cat-heading">
											<h3 class="sm-cat-title">
												<a href="{{ $catUrl }}" data-sm-item data-sm-heading>{{ $iCat->name }}</a>
											</h3>
											@if ($children->isNotEmpty())
												<p class="sm-cat-meta">{{ trans_choice('sitemap.sub_count', $children->count(), ['count' => $children->count()]) }}</p>
											@endif
										</div>
										<a href="{{ $catUrl }}" class="sm-cat-go" aria-label="{{ trans('sitemap.all_in', ['category' => $iCat->name]) }}"
										   title="{{ trans('sitemap.all_in', ['category' => $iCat->name]) }}">
											<i class="bi bi-arrow-up-right" aria-hidden="true"></i>
										</a>
									</header>
									@if ($children->isNotEmpty())
										<ul class="sm-cat-list" role="list">
											@foreach ($children as $iSubCat)
												<li class="sm-cat-item">
													<a href="{{ urlGen()->category($iSubCat) }}" class="sm-link" data-sm-item>
														<span class="sm-link-text">{{ $iSubCat->name }}</span>
														<i class="bi bi-arrow-right-short sm-link-arrow" aria-hidden="true"></i>
													</a>
												</li>
											@endforeach
										</ul>
									@endif
								</article>
							@endforeach
						</div>
					</section>
				@endif

				{{-- Cities: the homepage's tiles --}}
				@if ($cities->isNotEmpty())
					<section class="sm-section" id="sitemap-cities" aria-labelledby="sitemapCitiesTitle" data-sm-section>
						<header class="sm-section-head">
							<h2 class="h4 mb-0 fw-lighter section-title location-title" id="sitemapCitiesTitle">
								<span class="location-title-icon" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>
								<span class="fw-bold">{{ str_replace('*', '', trans('sitemap.cities_title', ['country' => $countryName])) }}</span>
							</h2>
							<span class="sm-section-meta">
								<i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $cities->count() }} {{ trans('sitemap.stat_cities') }}
							</span>
						</header>
						
						<div class="row row-cols-lg-4 row-cols-md-3 row-cols-2 g-2 city-list" data-sm-group>
							@foreach ($citiesByName as $city)
								<div class="col" data-sm-holder>
									<a href="{{ urlGen()->city($city) }}" class="{{ linkClass('body-emphasis') }} city-chip"
									   title="{{ t('Free Listings') . ' ' . $city->name }}" data-sm-item>
										<span class="city-chip-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
										<span class="city-chip-name">{{ $city->name }}</span>
									</a>
								</div>
							@endforeach
						</div>
					</section>
				@endif
				
				{{-- Blog --}}
				@if ($blogPosts->isNotEmpty())
					<section class="sm-section" id="sitemap-blog" aria-labelledby="sitemapBlogTitle" data-sm-section>
						<header class="sm-section-head">
							<h2 class="h4 mb-0 fw-lighter section-title" id="sitemapBlogTitle">{!! $title(trans('sitemap.blog_title')) !!}</h2>
							<span class="sm-section-meta">
								<i class="bi bi-journal-text" aria-hidden="true"></i> {{ $blogPosts->count() }} {{ trans('sitemap.stat_articles') }}
							</span>
						</header>

						<div class="sm-panel" data-sm-group>
							@if ($blogCategories->isNotEmpty())
								<ul class="sm-chips sm-topics" role="list">
									@foreach ($blogCategories as $blogCategory)
										<li>
											<a href="{{ $blogCategory->url }}" class="sm-topic" data-sm-item>
												<i class="bi bi-folder2-open" aria-hidden="true"></i>
												<span class="sm-topic-name">{{ $blogCategory->name }}</span>
											</a>
										</li>
									@endforeach
								</ul>
							@endif

							<ul class="sm-posts" role="list">
								@foreach ($blogPosts as $blogPost)
									<li>
										<a href="{{ $blogPost->url }}" class="sm-link" data-sm-item>
											<i class="bi bi-file-earmark-text sm-link-lead" aria-hidden="true"></i>
											<span class="sm-link-text">{{ str($blogPost->title)->limit(60) }}</span>
											<i class="bi bi-arrow-right-short sm-link-arrow" aria-hidden="true"></i>
										</a>
									</li>
								@endforeach
							</ul>
						</div>
					</section>
				@endif

				{{-- Shown by sitemap.js when a search finds nothing --}}
				<div class="sm-empty" hidden data-sm-empty>
					<span class="sm-empty-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
					<p class="sm-empty-title" data-sm-empty-title></p>
					<p class="sm-empty-hint">{{ trans('sitemap.no_results_hint') }}</p>
					<button type="button" class="sm-empty-button" data-sm-search-clear>
						<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> {{ trans('sitemap.search_clear') }}
					</button>
				</div>
			</div>

			@include('front.layouts.partials.social.horizontal')
		</div>
	</div>
@endsection
