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
@php use App\Enums\BootstrapColor; @endphp
@extends('front.layouts.master')

@php
	$post ??= [];
	$catBreadcrumb ??= [];
	$topAdvertising ??= [];
	$bottomAdvertising ??= [];
@endphp

@section('body_class', 'page-listing')
@section('content')
	@include('front.common.spacer')
	@php
		$paddingTopExists = true;
	@endphp
	
	@php
		$withMessage = !session()->has('flash_messages');
		$resendVerificationLink = getResendVerificationLink(withMessage: $withMessage);
	@endphp
	@if (!empty($resendVerificationLink))
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="alert alert-info text-center">
						{!! $resendVerificationLink !!}
					</div>
				</div>
			</div>
		</div>
	@endif
	
	{{-- Archived listings message --}}
	@if (!empty(data_get($post, 'archived_at')))
		@include('front.common.spacer')
		@php
			$paddingTopExists = true;
		@endphp
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="alert alert-warning" role="alert">
						{!! t('This listing has been archived') !!}
					</div>
				</div>
			</div>
		</div>
	@endif
	
	<div class="main-container listing-page">
		
		@if (!empty($topAdvertising))
			@include('front.layouts.partials.advertising.top', ['paddingTopExists' => $paddingTopExists ?? false])
			@php
				$paddingTopExists = false;
			@endphp
		@endif
		
		{{-- Breadcrumb + back link --}}
		<div class="container {{ !empty($topAdvertising) ? 'mt-3' : 'mt-2' }}">
			<div class="row">
				<div class="col-12 listing-topbar">
					<nav aria-label="breadcrumb" role="navigation" class="listing-breadcrumb">
						<ol class="breadcrumb mb-0">
							<li class="breadcrumb-item">
								<a href="{{ url('/') }}" class="{{ linkClass() }}">
									<i class="bi bi-house-door-fill"></i>
								</a>
							</li>
							<li class="breadcrumb-item">
								<a href="{{ url('/') }}" class="{{ linkClass() }}">
									{{ config('country.name') }}
								</a>
							</li>
							@if (is_array($catBreadcrumb) && count($catBreadcrumb) > 0)
								@foreach($catBreadcrumb as $key => $value)
									<li class="breadcrumb-item">
										<a href="{{ $value->get('url') }}" class="{{ linkClass() }}">
											{!! $value->get('name') !!}
										</a>
									</li>
								@endforeach
							@endif
							<li class="breadcrumb-item active" aria-current="page">
								{{ str(data_get($post, 'title'))->limit(70) }}
							</li>
						</ol>
					</nav>
					<a href="{{ rawurldecode(url()->previous()) }}" class="listing-back-link">
						<i class="bi bi-arrow-left"></i> {{ t('back_to_results') }}
					</a>
				</div>
			</div>
		</div>
		
		<div class="container">
			<div class="row g-4">
				{{-- Content --}}
				<div class="col-lg-8">
					@php
						$overflowStyle = (!auth()->check() && plugin_exists('reviews')) ? 'overflow: visible;' : '';
						$catName = data_get($post, 'category.name');
						$parentCatName = data_get($post, 'category.parent.name');
						$catUrl = !empty(data_get($post, 'category')) ? urlGen()->category(data_get($post, 'category')) : null;
					@endphp
					<div class="listing-main items-details-wrapper" style="{{ $overflowStyle }}">
						
						{{-- Header: category, title, meta, price --}}
						<header class="listing-header">
							<div class="listing-header-top">
								<div class="listing-chips">
									@if (!empty($catName))
										<a href="{{ $catUrl }}" class="listing-chip listing-chip-cat">
											<i class="{{ data_get($post, 'category.icon_class') ?: 'bi bi-folder-fill' }}"></i>
											{{ $catName }}
										</a>
									@endif
									@if (config('settings.listing_form.show_listing_type') && !empty(data_get($post, 'postType')))
										<span class="listing-chip listing-chip-type">{{ data_get($post, 'postType.label') }}</span>
									@endif
									@if (data_get($post, 'featured') == 1 && !empty(data_get($post, 'payment.package')))
										<span class="listing-chip listing-chip-featured"
										      data-bs-placement="bottom"
										      data-bs-toggle="tooltip"
										      title="{{ data_get($post, 'payment.package.short_name') }}"
										>
											<i class="bi bi-patch-check-fill"></i> {{ t('Featured') }}
										</span>
									@endif
								</div>
								<div class="listing-ref">{{ t('reference') }}: <span>{{ data_get($post, 'reference') }}</span></div>
							</div>
							
							<h1 class="listing-title">
								<a href="{{ urlGen()->post($post) }}" title="{{ data_get($post, 'title') }}">
									{{ data_get($post, 'title') }}
								</a>
							</h1>
							
							<div class="listing-header-bottom">
								<ul class="listing-meta list-unstyled">
									<li class="listing-meta-city">
										<i class="bi bi-geo-alt-fill"></i>
										<a href="{!! urlGen()->city(data_get($post, 'city')) !!}">{{ data_get($post, 'city.name') }}</a>
									</li>
									@if (!config('settings.listing_page.hide_date'))
										<li><i class="bi bi-clock"></i> {!! data_get($post, 'created_at_formatted') !!}</li>
									@endif
									@if (!empty($parentCatName) && $parentCatName != $catName)
										<li><i class="bi bi-folder2-open"></i> {{ $parentCatName }}</li>
									@endif
									<li><i class="bi bi-eye"></i> {{ data_get($post, 'visits_formatted') }}</li>
								</ul>
								
								@if (!empty(data_get($post, 'price_formatted')))
									@php
										$isTextPrice = !preg_match('/\d/', strip_tags((string)data_get($post, 'price_formatted')));
									@endphp
									<div class="listing-price-card{{ $isTextPrice ? ' listing-price-card-text' : '' }}">
										<span class="listing-price-icon">
											<i class="bi {{ $isTextPrice ? 'bi-chat-left-dots-fill' : 'bi-tag-fill' }}"></i>
										</span>
										<span class="listing-price-body">
											<span class="listing-price-label">{{ data_get($post, 'price_label') }}</span>
											<span class="listing-price-value">
												{!! data_get($post, 'price_formatted') !!}
												@if (data_get($post, 'negotiable') == 1)
													<span class="listing-price-negotiable">{{ t('negotiable') }}</span>
												@endif
											</span>
										</span>
									</div>
								@endif
							</div>
						</header>
						
						{{-- Pictures --}}
						<div class="listing-gallery">
							@include('front.post.show.partials.pictures-slider')
						</div>
						
						{{-- Reviews Stars --}}
						@if (config('plugins.reviews.installed'))
							@if (view()->exists('reviews::ratings-single'))
								@include('reviews::ratings-single')
							@endif
						@endif
						
						{{-- Details --}}
						@include('front.post.show.partials.details')
					</div>
				</div>
				
				{{-- Sidebar --}}
				<div class="col-lg-4">
					@include('front.post.show.partials.sidebar')
				</div>
			</div>

		</div>
		
		@if (config('settings.listing_page.similar_listings') == '1' || config('settings.listing_page.similar_listings') == '2')
			@php
				$widgetType = (config('settings.listing_page.similar_listings_in_carousel') ? 'carousel' : 'normal');
			@endphp
			@include('front.search.partials.posts.widget.' . $widgetType, [
				'widget'       => ($widgetSimilarPosts ?? null),
				'firstSection' => false
			])
		@endif
		
		@include('front.layouts.partials.advertising.bottom', ['firstSection' => false])
		
		@if (isVerifiedPost($post))
			@include('front.layouts.partials.tools.facebook-comments', ['firstSection' => false])
		@endif
		
	</div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection
@php
	if (!session()->has('emailVerificationSent') && !session()->has('phoneVerificationSent')) {
		if (session()->has('message')) {
			session()->forget('message');
		}
	}
@endphp

@section('modal_message')
	@if (config('settings.listing_page.show_security_tips') == '1')
		@include('front.post.show.partials.security-tips')
	@endif
	@if (auth()->check() || config('settings.listing_page.guest_can_contact_authors') == '1')
		@include('front.account.messenger.modal.create')
	@endif
@endsection

@section('before_scripts')
	<script>
		var showSecurityTips = '{{ config('settings.listing_page.show_security_tips', '0') }}';
	</script>
@endsection

@section('after_scripts')
	<script>
		{{-- Favorites Translation --}}
        var lang = {
            labelSavePostSave: "{!! t('Save listing') !!}",
            labelSavePostRemove: "{!! t('Remove favorite') !!}",
            loginToSavePost: "{!! t('Please log in to save the Listings') !!}",
            loginToSaveSearch: "{!! t('Please log in to save your search') !!}"
        };
		
		onDocumentReady((event) => {
			{{-- Tooltip --}}
			const tooltipEls = document.querySelectorAll('[rel="tooltip"]');
			if (tooltipEls) {
				let tooltipTriggerList = [].slice.call(tooltipEls);
				let tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
					return new bootstrap.Tooltip(tooltipTriggerEl)
				});
			}
			
			{{-- Keep the current tab active with Twitter Bootstrap after a page reload --}}
			const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
			if (tabEls.length > 0) {
				tabEls.forEach((tabButton) => {
					tabButton.addEventListener('shown.bs.tab', function (e) {
						/* Save the latest tab; use cookies if you like 'em better: */
						/* localStorage.setItem('lastTab', tabButton.getAttribute('href')); */
						localStorage.setItem('lastTab', tabButton.getAttribute('data-bs-target'));
					});
				});
			}
			
			{{-- Go to the latest tab, if it exists: --}}
            let lastTab = localStorage.getItem('lastTab');
            if (lastTab) {
				{{-- let triggerEl = document.querySelector('a[href="' + lastTab + '"]'); --}}
				let triggerEl = document.querySelector('button[data-bs-target="' + lastTab + '"]');
				if (typeof triggerEl !== 'undefined' && triggerEl !== null) {
					let tabObj = new bootstrap.Tab(triggerEl);
					if (tabObj !== null) {
						tabObj.show();
					}
				}
            }
		});
	</script>
@endsection
