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
	$apiResult ??= [];
	$savedSearches = (array)data_get($apiResult, 'data');
	$totalSavedSearches = (int)data_get($apiResult, 'meta.total');
@endphp

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				<div class="col-md-3">
					@include('front.account.partials.sidebar')
				</div>
				
				<div class="col-md-9">
					<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2">
						<h2 class="fw-bold border-bottom pb-3 mb-4">
							<i class="bi bi-bell"></i> {{ t('saved_searches') }}
						</h2>
						<div class="row">
							<div class="col-md-12">
								@if (!empty($savedSearches) && $totalSavedSearches > 0)
									<div class="row row-cols-lg-2 row-cols-1 g-1 g-lg-1 mb-3">
										@foreach ($savedSearches as $search)
											@php
												$isSelected = (request()->query('q') == data_get($search, 'keyword'));
												$activeClass = $isSelected ? ' active' : '';
												
												$searchId = data_get($search, 'id');
												$detailUrl = url(urlGen()->getAccountBasePath() . '/saved-searches/' . $searchId);
												$deleteUrl = url(urlGen()->getAccountBasePath() . '/saved-searches/' . $searchId . '/delete');
											@endphp
											<div class="col p-1">
												<div class="w-100 border border rounded bg-body p-2 clearfix{{ $activeClass }}">
													<div class="float-start d-flex align-items-center h-100 px-1 fs-6">
														<a href="{{ $detailUrl }}" class="{{ linkClass() }}">
															<span>
																{{ str(data_get($search, 'keyword'))->headline()->limit(20) }}
															</span>
															<span class="badge rounded-pill text-bg-warning" id="{{ $searchId }}">
																{{ data_get($search, 'count') }}~
															</span>
														</a>
													</div>
													<div class="float-end">
														<a href="{{ $deleteUrl }}"
														   class="confirm-simple-action {{ linkClass() }}"
														   data-bs-toggle="tooltip"
														   title="{{ t('Delete') }}"
														>
															<i class="bi bi-trash text-danger"></i>
														</a>
													</div>
												</div>
											</div>
										@endforeach
									</div>
								@else
									<div class="text-center mt-3 mb-5">
										{{ $apiMessage ?? t('You have no saved search') }}
									</div>
								@endif
								
								@include('vendor.pagination.api.bootstrap-5')
							</div>
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</div>
@endsection
