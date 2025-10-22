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

@section('content')
	@include('front.common.spacer')
	<div class="main-container pb-0">
		<div class="container">
			@include('helpers.titles.title-2', ['title' => t('countries')])
			
			@php
				$mb = !isSocialSharesEnabled() ? ' mb-4' : '';
			@endphp
			<div class="row{{ $mb }}">
				<div class="col-md-12">
					<div class="container bg-body-tertiary rounded py-3">
						
						<h3 class="fs-3 mb-4">
							<i class="bi bi-geo-alt"></i> {{ t('select_a_country') }}
						</h3>
						
						@if ($countries->isNotEmpty())
							<div class="row row-cols-xl-4 row-cols-lg-3 row-cols-2 m-0" id="countryList">
								@foreach ($countries as $code => $country)
									@php
										$countryUrl = dmUrl($country, '/', true, !config('plugins.domainmapping.installed'));
										$countryName = $country->get('name');
										$countryNameLimited = str($countryName)->limit(26)->toString();
									@endphp
									<div class="col mb-2">
										@if ($countryFlagShape == 'rectangle')
											<img src="{{ url('images/blank.gif') . getPictureVersion() }}"
												 class="flag flag-{{ $country->get('icode') }}"
												 style="margin-bottom: 4px; margin-right: 5px;"
											     alt="{{ $countryNameLimited }}"
											>
										@else
											<img src="{{ $country->get('flag16_url') }}"
											     class=""
											     style="margin-bottom: 4px; margin-right: 5px;"
											     alt="{{ $countryNameLimited }}"
											>
										@endif
										<a href="{{ $countryUrl }}"
										   class="{{ linkClass('body-emphasis') }}"
										   data-bs-toggle="tooltip"
										   title="{!! $countryName !!}"
										>
											{{ $countryNameLimited }}
										</a>
									</div>
								@endforeach
							</div>
						@else
							<div class="row m-0">
								<div class="col-12 text-center mb-3 text-danger fw-bold">
									{{ t('countries_not_found') }}
								</div>
							</div>
						@endif
						
					</div>
				</div>
				
			</div>
			
			@include('front.layouts.partials.social.horizontal')
		</div>
	</div>
@endsection

@section('footer')
	@include('front.layouts.partials.lite.footer')
@endsection

@section('after_scripts')
@endsection
