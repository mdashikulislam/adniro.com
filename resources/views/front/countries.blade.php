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
	$countries ??= collect();
	$countryFlagShape = config('settings.localization.country_flag_shape');
@endphp
@section('header')
	@include('front.layouts.partials.lite.header', ['showIconOnly' => true])
@endsection

@section('search')
	@parent
@endsection

@section('body_class', 'page-countries')
@section('content')
	@include('front.common.spacer')
	@php
		$currentCountryCode = strtolower((string)config('country.code'));
	@endphp
	<div class="main-container countries-page pb-0">
		<div class="container">
			
			{{-- Page title --}}
			<div class="countries-hero">
				<span class="countries-hero-icon"><i class="bi bi-globe-americas"></i></span>
				<div>
					<h1 class="countries-hero-title">
						{{ t('countries') }}
						@if ($countries->isNotEmpty())
							<small class="country-modal-count">{{ $countries->count() }}</small>
						@endif
					</h1>
					<p class="countries-hero-text">{{ t('countries_page_intro') }}</p>
				</div>
			</div>
			
			<div class="countries-card">
				@if ($countries->isNotEmpty())
					<div class="country-grid countries-grid" id="countryList">
						@foreach ($countries as $code => $country)
							@php
								$countryUrl = dmUrl($country, '/', true, !config('plugins.domainmapping.installed'));
								$countryName = $country->get('name');
								$countryNameLimited = str($countryName)->limit(26)->toString();
								$isCurrent = (strtolower((string)$code) == $currentCountryCode);
								$flag32Url = $country->get('flag32_url') ?: $country->get('flag16_url');
							@endphp
							<a href="{{ $countryUrl }}"
							   class="country-tile{{ $isCurrent ? ' is-current' : '' }}"
							   title="{{ $countryName }}"
							>
								<span class="country-tile-flag">
									@if ($countryFlagShape == 'rectangle')
										<img src="{{ url('images/blank.gif') . getPictureVersion() }}"
										     class="flag flag-{{ $country->get('icode') }}"
										     alt="{{ $countryNameLimited }}"
										>
									@else
										<img src="{{ $flag32Url }}" alt="{{ $countryNameLimited }}">
									@endif
								</span>
								<span class="country-tile-name">{{ $countryNameLimited }}</span>
								@if ($isCurrent)
									<span class="country-tile-check"><i class="bi bi-check-lg"></i></span>
								@else
									<span class="country-tile-arrow"><i class="bi bi-arrow-right"></i></span>
								@endif
							</a>
						@endforeach
					</div>
				@else
					<div class="countries-empty">
						<i class="bi bi-globe"></i>
						<span>{{ t('countries_not_found') }}</span>
					</div>
				@endif
			</div>
			
			@if (isSocialSharesEnabled())
				<div class="countries-share">
					@include('front.layouts.partials.social.horizontal')
				</div>
			@endif
		</div>
	</div>
@endsection

@section('footer')
	@include('front.layouts.partials.lite.footer')
@endsection

@section('after_scripts')
@endsection
