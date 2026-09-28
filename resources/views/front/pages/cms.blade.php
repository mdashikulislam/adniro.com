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
@extends('front.layouts.master')

@php
	$page ??= [];
	$hasHeroImage = !empty(data_get($page, 'image_path'));
	
	$pageSlug = (string)data_get($page, 'slug');
	$pageType = (string)data_get($page, 'type');
	$pageIcon = match (true) {
		$pageType == 'terms'                      => 'bi bi-file-earmark-text-fill',
		$pageType == 'privacy'                    => 'bi bi-shield-lock-fill',
		str_contains($pageSlug, 'faq')            => 'bi bi-question-circle-fill',
		str_contains($pageSlug, 'scam')           => 'bi bi-shield-exclamation',
		str_contains($pageSlug, 'about')          => 'bi bi-info-circle-fill',
		default                                   => 'bi bi-file-earmark-richtext-fill',
	};
	
	$updatedAt = data_get($page, 'updated_at');
	try {
		$updatedAtFormatted = !empty($updatedAt) ? \Carbon\Carbon::parse($updatedAt)->translatedFormat('F j, Y') : null;
	} catch (\Throwable $e) {
		$updatedAtFormatted = null;
	}
	
	// Other CMS pages (quick links)
	try {
		$otherPages = cache()->remember('cms.pages.nav.' . config('app.locale'), 3600, function () {
			return \App\Models\Page::query()->where('active', 1)->orderBy('lft')->get();
		});
	} catch (\Throwable $e) {
		$otherPages = collect();
	}
@endphp

@section('body_class', 'page-cms')
@section('search')
	@parent
    @include('front.pages.cms.intro')
@endsection

@section('content')
	@include('front.common.spacer')
	<div class="container cms-page">
		
		@if (!$hasHeroImage)
			<div class="cms-hero">
				<span class="cms-hero-icon"><i class="{{ $pageIcon }}"></i></span>
				<div class="cms-hero-body">
					<h1 class="cms-hero-title" style="{{ !empty(data_get($page, 'name_color')) ? 'color: ' . data_get($page, 'name_color') : '' }}">
						{{ data_get($page, 'name') }}
					</h1>
					@if (data_get($page, 'name') != data_get($page, 'title'))
						<p class="cms-hero-subtitle" style="{{ !empty(data_get($page, 'title_color')) ? 'color: ' . data_get($page, 'title_color') : '' }}">
							{{ data_get($page, 'title') }}
						</p>
					@endif
					@if (!empty($updatedAtFormatted))
						<span class="cms-hero-date">
							<i class="bi bi-clock-history"></i> {{ t('last_updated') }}: <strong>{{ $updatedAtFormatted }}</strong>
						</span>
					@endif
				</div>
			</div>
		@endif
		
		<div class="row g-4">
			<div class="{{ $otherPages->count() > 1 ? 'col-lg-9' : 'col-12' }}">
				<article class="cms-card">
					<div class="cms-content from-wysiwyg">
						{!! data_get($page, 'content') !!}
					</div>
				</article>
				
				@if (isSocialSharesEnabled())
					<div class="cms-share">
						@include('front.layouts.partials.social.horizontal')
					</div>
				@endif
			</div>
			
			@if ($otherPages->count() > 1)
				<div class="col-lg-3">
					<aside class="cms-aside">
						<div class="cms-aside-card">
							<h2 class="cms-aside-title">
								<span class="cms-aside-icon"><i class="bi bi-collection-fill"></i></span>
								{{ t('other_pages') }}
							</h2>
							<ul class="cms-aside-list list-unstyled">
								@foreach ($otherPages as $iPage)
									@php
										$iSlug = (string)data_get($iPage, 'slug');
										$iType = (string)data_get($iPage, 'type');
										$iIcon = match (true) {
											$iType == 'terms'              => 'bi bi-file-earmark-text',
											$iType == 'privacy'            => 'bi bi-shield-lock',
											str_contains($iSlug, 'faq')    => 'bi bi-question-circle',
											str_contains($iSlug, 'scam')   => 'bi bi-shield-exclamation',
											str_contains($iSlug, 'about')  => 'bi bi-info-circle',
											default                        => 'bi bi-file-earmark-richtext',
										};
										$isCurrentPage = ($iSlug == $pageSlug);
									@endphp
									<li class="{{ $isCurrentPage ? 'is-current' : '' }}">
										<a href="{{ urlGen()->page($iPage) }}">
											<i class="{{ $iIcon }}"></i>
											<span>{{ data_get($iPage, 'name') }}</span>
											<i class="bi bi-chevron-right cms-aside-arrow"></i>
										</a>
									</li>
								@endforeach
							</ul>
						</div>
						
						<div class="cms-aside-help">
							<span class="cms-aside-help-icon"><i class="bi bi-chat-dots-fill"></i></span>
							<p>{{ t('cms_page_intro') }}</p>
							<a href="{{ urlGen()->contact() }}" class="btn btn-primary cms-aside-help-btn">
								<i class="bi bi-envelope"></i> {{ t('Contact Us') }}
							</a>
						</div>
					</aside>
				</div>
			@endif
		</div>
	</div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection
