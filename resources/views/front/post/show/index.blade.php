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
	
	<div class="main-container">
		
		@if (!empty($topAdvertising))
			@include('front.layouts.partials.advertising.top', ['paddingTopExists' => $paddingTopExists ?? false])
			@php
				$paddingTopExists = false;
			@endphp
		@endif
		
		<div class="container {{ !empty($topAdvertising) ? 'mt-3' : 'mt-2' }}">
			<div class="row">
				<div class="col-md-12">
					
					<nav aria-label="breadcrumb" role="navigation" class="float-start">
						<ol class="breadcrumb">
							<li class="breadcrumb-item">
								<a href="{{ url('/') }}" class="{{ linkClass() }}">
									<i class="fa-solid fa-house"></i>
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
					
					<div class="float-end">
						<a href="{{ rawurldecode(url()->previous()) }}" class="{{ linkClass() }}">
							<i class="fa-solid fa-angles-left"></i> {{ t('back_to_results') }}
						</a>
					</div>
				
				</div>
			</div>
		</div>
		
		<div class="container">
			<div class="row">
				{{-- Content --}}
				<div class="col-lg-9">
					@php
						$overflowStyle = (!auth()->check() && plugin_exists('reviews')) ? 'overflow: visible;' : '';
					@endphp
					<div class="container border rounded bg-body-tertiary px-3 pt-2 pb-3 mb-md-0 mb-3 items-details-wrapper" style="{{ $overflowStyle }}">
						{{-- Title --}}
						<div class="clearfix">
							<h1 class="fs-3 fw-bold text-wrap float-start">
								<a href="{{ urlGen()->post($post) }}"
								   class="{{ linkClass() }}"
								   title="{{ data_get($post, 'title') }}"
								>
									{{ data_get($post, 'title') }}
								</a>
								
								@if (data_get($post, 'featured') == 1 && !empty(data_get($post, 'payment.package')))
									@php
										$ribbonColor = data_get($post, 'payment.package.ribbon');
										$ribbonColorClass = BootstrapColor::Text->getColorClass($ribbonColor);
										$packageShortName = data_get($post, 'payment.package.short_name');
									@endphp
									<i class="fa-solid fa-check-circle {{ $ribbonColorClass }}"
									   data-bs-placement="bottom"
									   data-bs-toggle="tooltip"
									   title="{{ $packageShortName }}"
									></i>
								@endif
							</h1>
							@if (config('settings.listing_form.show_listing_type'))
								@if (!empty(data_get($post, 'postType')))
									<span class="badge rounded-pill text-bg-dark float-end mt-2">
										{{ data_get($post, 'postType.label') }}
									</span>
								@endif
							@endif
						</div>
						
						{{-- Infos --}}
						<div class="border-top py-2 mt-0 text-secondary d-flex justify-content-between">
							<ul class="list-inline mb-0">
								@if (!config('settings.listing_page.hide_date'))
									<li class="list-inline-item"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
										<i class="fa-regular fa-clock"></i> {!! data_get($post, 'created_at_formatted') !!}
									</li>
								@endif
								<li class="list-inline-item"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
									<i class="bi bi-folder"></i> {{ data_get($post, 'category.parent.name', data_get($post, 'category.name')) }}
								</li>
								<li class="list-inline-item"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
									<i class="bi bi-geo-alt"></i> {{ data_get($post, 'city.name') }}
								</li>
								<li class="list-inline-item"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
									<i class="bi bi-eye"></i> {{ data_get($post, 'visits_formatted') }}
								</li>
							</ul>
							<div class="text-nowrap"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
								{{ t('reference') }}: {{ data_get($post, 'reference') }}
							</div>
						</div>
						
						{{-- Pictures --}}
						@include('front.post.show.partials.pictures-slider')
						
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
				<div class="col-lg-3">
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
