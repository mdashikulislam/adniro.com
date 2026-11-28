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
 * adniro: the pricing page, redesigned. A dark teal head (the sitemap page's), figures drawn from the
 * active plans, one card per package (pricing/package-card.blade.php), how a boost works, and help.
 * Texts: lang/[locale]/pricing.php. Styles: public/assets/css/pricing.css.
--}}
@extends('front.layouts.master')

@php
	$promoPackages ??= [];
	$promoPackagesErrorMessage ??= '';

	$subsPackages ??= [];
	$subsPackagesErrorMessage ??= '';

	$isAllTypesOfPackageExist = (!empty($promoPackages) && !empty($subsPackages));
	$isAllTypesOfPackageNotExist = (empty($promoPackages) && empty($subsPackages));

	// Get the active tab
	$defaultPackageType = config('settings.listing_form.default_package_type');
	$packageType = request()->query('type', $defaultPackageType);

	// Get the active tab (by checking if its packages exist)
	$packageType = ($packageType == 'promotion' && !empty($promoPackages)) ? 'promotion' : 'subscription';
	$packageType = ($packageType == 'subscription' && !empty($subsPackages)) ? 'subscription' : 'promotion';

	$appName = config('settings.app.name', config('app.name'));
	$title = fn (string $text) => preg_replace('/\*(.+?)\*/u', '<strong>$1</strong>', e($text));

	// The figures under the head, from the plans on the page
	$allPackages = collect(array_merge((array)$promoPackages, (array)$subsPackages));
	$cheapest = $allPackages->sortBy(fn ($p) => (float)data_get($p, 'price', 0))->first();
	$longestPromotion = (int)$allPackages->max(fn ($p) => (int)data_get($p, 'promotion_time', 0));
	$mostPhotos = max(
		(int)$allPackages->max(fn ($p) => (int)data_get($p, 'pictures_limit', 0)),
		(int)config('settings.listing_form.pictures_limit', 0)
	);
	$highlights = array_values(array_filter([
		$cheapest ? ['icon' => 'bi-tag', 'html' => str_replace('__PRICE__', '<strong>' . data_get($cheapest, 'price_formatted') . '</strong>', e(trans('pricing.from', ['price' => '__PRICE__'])))] : null,
		$longestPromotion > 0 ? ['icon' => 'bi-lightning-charge', 'html' => e(trans_choice('pricing.promo_days', $longestPromotion, ['count' => $longestPromotion]))] : null,
		$mostPhotos > 0 ? ['icon' => 'bi-images', 'html' => e(trans('pricing.photos', ['count' => $mostPhotos]))] : null,
	]));

	$steps = [
		['icon' => 'bi-pencil-square', 'title' => trans('pricing.step_post_title'), 'text' => trans('pricing.step_post_text')],
		['icon' => 'bi-hand-index-thumb', 'title' => trans('pricing.step_pick_title'), 'text' => trans('pricing.step_pick_text')],
		['icon' => 'bi-graph-up-arrow', 'title' => trans('pricing.step_reach_title'), 'text' => trans('pricing.step_reach_text')],
	];
@endphp

@push('after_styles_stack')
	<link href="{{ url('assets/css/pricing.css') }}?v={{ @filemtime(public_path('assets/css/pricing.css')) }}" rel="stylesheet">
@endpush

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container pr-page" id="pricing">

			{{-- Head --}}
			<header class="pr-hero">
				<div class="pr-hero-decor" aria-hidden="true">
					<i class="bi bi-stars pr-hero-spark pr-hero-spark--1"></i>
					<i class="bi bi-gem pr-hero-spark pr-hero-spark--2"></i>
					<i class="bi bi-lightning-charge-fill pr-hero-spark pr-hero-spark--3"></i>
				</div>
				<div class="pr-hero-inner">
					<p class="pr-hero-eyebrow">
						<i class="bi bi-rocket-takeoff" aria-hidden="true"></i>
						<span>{{ t('Pricing') }}</span>
					</p>
					<h1 class="pr-hero-title">{!! $title(trans('pricing.title')) !!}</h1>
					<p class="pr-hero-intro">{{ trans('pricing.intro', ['app' => $appName]) }}</p>

					@if (!empty($highlights))
						<ul class="pr-highlights" aria-label="{{ trans('pricing.highlights_label') }}">
							@foreach ($highlights as $highlight)
								<li class="pr-highlight">
									<i class="bi {{ $highlight['icon'] }}" aria-hidden="true"></i>
									<span>{!! $highlight['html'] !!}</span>
								</li>
							@endforeach
						</ul>
					@endif
				</div>
			</header>

			@if (!$isAllTypesOfPackageNotExist)
				@if ($isAllTypesOfPackageExist)
					<div class="pr-tabs-wrap">
						<ul class="nav pr-tabs" id="pills-tab" role="tablist">
							<li class="nav-item" role="presentation">
								<button @class(['nav-link', 'active' => $packageType == 'promotion'])
								        id="pills-promotion-tab"
								        data-bs-toggle="pill"
								        data-bs-target="#pills-promotion"
								        type="button"
								        role="tab"
								        aria-controls="pills-promotion"
								        aria-selected="{{ $packageType == 'promotion' ? 'true' : 'false' }}"
								>{{ t('promo_packages_tab') }}</button>
							</li>
							<li class="nav-item" role="presentation">
								<button @class(['nav-link', 'active' => $packageType == 'subscription'])
								        id="pills-subscription-tab"
								        data-bs-toggle="pill"
								        data-bs-target="#pills-subscription"
								        type="button"
								        role="tab"
								        aria-controls="pills-subscription"
								        aria-selected="{{ $packageType == 'subscription' ? 'true' : 'false' }}"
								>{{ t('subs_packages_tab') }}</button>
							</li>
						</ul>
					</div>
				@endif

				<h2 class="visually-hidden">{{ trans('pricing.plans_title') }}</h2>
				<div class="tab-content pr-plans" id="pills-tabContent">
					@if (!empty($promoPackages))
						<div @class(['tab-pane fade', 'show active' => $packageType == 'promotion'])
						     id="pills-promotion"
						     role="tabpanel"
						     aria-labelledby="pills-promotion-tab"
						>
							@include('front.pages.pricing.promo-packages', [
								'packages' => $promoPackages,
								'message'  => $promoPackagesErrorMessage
							])
						</div>
					@endif
					@if (!empty($subsPackages))
						<div @class(['tab-pane fade', 'show active' => $packageType == 'subscription'])
						     id="pills-subscription"
						     role="tabpanel"
						     aria-labelledby="pills-subscription-tab"
						>
							@include('front.pages.pricing.subs-packages', [
								'packages' => $subsPackages,
								'message'  => $subsPackagesErrorMessage
							])
						</div>
					@endif
				</div>
			@else
				<div class="pr-empty">
					<span class="pr-empty-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
					<p class="pr-empty-title">{{ trans('pricing.empty_title') }}</p>
					<p class="pr-empty-text">{{ trans('pricing.empty_text') }}</p>
					<a href="{{ urlGen()->addPost() }}" class="pr-card-cta pr-card-cta--solid pr-empty-cta">{{ trans('pricing.empty_link') }}</a>
				</div>
			@endif

			{{-- How a boost works --}}
			<section class="pr-steps-section" aria-labelledby="pricingStepsTitle">
				<h2 class="pr-section-title" id="pricingStepsTitle">{!! $title(trans('pricing.steps_title')) !!}</h2>
				<ol class="pr-steps">
					@foreach ($steps as $step)
						<li class="pr-step">
							<span class="pr-step-number" aria-hidden="true">{{ $loop->iteration }}</span>
							<span class="pr-step-icon" aria-hidden="true"><i class="bi {{ $step['icon'] }}"></i></span>
							<h3 class="pr-step-title">{{ $step['title'] }}</h3>
							<p class="pr-step-text">{{ $step['text'] }}</p>
						</li>
					@endforeach
				</ol>
			</section>

			{{-- Help --}}
			<aside class="pr-help">
				<span class="pr-help-icon" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
				<div class="pr-help-text">
					<p class="pr-help-title">{{ trans('pricing.help_title') }}</p>
					<p class="pr-help-body">{{ trans('pricing.help_text') }}</p>
				</div>
				<a href="{{ urlGen()->contact() }}" class="pr-help-link">
					<span>{{ trans('pricing.help_link') }}</span>
					<i class="bi bi-arrow-right" aria-hidden="true"></i>
				</a>
			</aside>

		</div>
	</div>

	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection
