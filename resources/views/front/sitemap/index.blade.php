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
	$cats ??= [];
	$cities ??= [];
	
	$wrapperClass = ' my-2 px-1';
	$itemClass = ' px-0';
	$itemBorderClass = ' border rounded';
	$h5BgColor = ' bg-body-secondary rounded px-3 py-2';
	$subCatIcon = '<i class="bi bi-dash"></i> ';
@endphp

@section('search')
	@parent
@endsection

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container mt-5">
			@include('helpers.titles.title-2', ['title' => t('sitemap')])
			@include('front.common.spacer')
			
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h3 class="mb-0 fs-5 fw-bold">
								{{ t('list_of_categories_and_sub_categories') }}
							</h3>
						</div>
						
						<div class="card-body">
							<div class="container">
								<div class="row row-cols-lg-3 row-cols-md-2 row-cols-sm-1 row-cols-1{{ $wrapperClass }}">
									@foreach ($cats as $key => $iCat)
										@php
											$randomId = '-' . generateRandomString(5);
											
											$domElId = $iCat->id . $randomId;
											$catIconClass = $iCat->icon_class ?? 'icon-ok';
											$catIcon = !empty($catIconClass) ? '<i class="' . $catIconClass . '"></i> ' : '';
										@endphp
										<div class="col d-flex justify-content-center align-content-stretch{{ $itemClass }}">
											<div class="w-100 p-3 m-1{{ $itemBorderClass }}">
												<h5 class="mb-0 fs-5 fw-bold d-flex justify-content-between{{ $h5BgColor }}">
													<span>
														{!! $catIcon !!}<a href="{{ urlGen()->category($iCat) }}" class="{{ linkClass() }}">
															{{ $iCat->name }} <span class="count"></span>
														</a>
													</span>
													@if (isset($iCat->children) && $iCat->children->count() > 0)
														<a class="{{ linkClass('body-emphasis') }}"
														   data-bs-toggle="collapse"
														   data-bs-target="#parentCat{{ $domElId }}"
														   href="#parentCat{{ $domElId }}"
														   role="button"
														   aria-expanded="false"
														   aria-controls="parentCat{{ $domElId }}"
														>
															<i class="bi bi-chevron-down"></i>
														</a>
													@endif
												</h5>
												@if (isset($iCat->children) && $iCat->children->count() > 0)
													<div class="collapse show mt-2 ms-3" id="parentCat{{ $domElId }}">
														<ul class="list-unstyled long-list-home">
															@foreach ($iCat->children as $iSubCat)
																<li class="py-1">
																	{!! $subCatIcon !!}<a href="{{ urlGen()->category($iSubCat) }}" class="{{ linkClass() }}">
																		{{ $iSubCat->name }}
																	</a>
																</li>
															@endforeach
														</ul>
													</div>
												@endif
											</div>
										</div>
									@endforeach
								</div>
							</div>
						</div>
					</div>
				</div>
				
				@if (isset($cities))
					@include('front.common.spacer')
					<div class="col-12">
						<div class="card">
							<div class="card-header">
								<h3 class="mb-0 fs-5 fw-bold">
									<i class="bi bi-geo-alt"></i> {{ t('list_of_cities_in') }} {{ config('country.name') }}
								</h3>
							</div>
							
							<div class="card-body">
								<div class="container">
									<div class="row row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-1">
										@foreach ($cities as $key => $city)
											<div class="col px-4 py-1">
												<a href="{{ urlGen()->city($city) }}"
												   class="{{ linkClass('body-emphasis') }}"
												   title="{{ t('Free Listings') . ' ' . $city->name }}"
												>
													{{ $city->name }}
												</a>
											</div>
										@endforeach
									</div>
								</div>
							</div>
						</div>
					</div>
				@endif

			</div>
			
			@include('front.layouts.partials.social.horizontal')
		</div>
	</div>
@endsection

@section('before_scripts')
	@parent
	<script>
		var maxSubCats = 5;
	</script>
@endsection
