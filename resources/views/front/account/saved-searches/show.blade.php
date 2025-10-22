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
	$savedSearch ??= [];
	
	$apiMessage = $apiMessagePosts ?? null;
	$apiResult = $apiResultPosts ?? [];
	$posts = (array)data_get($apiResult, 'data');
	$totalPosts = (int)data_get($apiResult, 'meta.total');
	
	$apiExtraPosts ??= [];
	$query = (array)data_get($apiExtraPosts, 'preSearch.query');
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
					<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 pb-0 pb-lg-0 pb-md-0 mb-3">
						<h2 class="fw-bold border-bottom pb-3 mb-4">
							<i class="bi bi-bell"></i> {{ t('Saved search') }} #{{ data_get($savedSearch, 'id') }}
						</h2>
						
						<div class="row">
							<div class="col-12 mb-3 text-end">
								<i class="bi bi-arrow-90deg-left"></i> <a href="{{ url(urlGen()->getAccountBasePath() . '/saved-searches') }}" class="{{ linkClass() }}">
									{{ t('saved_searches') }}
								</a>
							</div>
							<div class="col-12 mb-3">
								@php
									$searchLink = urlBuilder(urlGen()->search($query))
										->removeParameters(['page'])
										->toString();
								@endphp
								<span class="fs-6">
									<strong>{{ t('search') }}:</strong> <a href="{{ $searchLink }}"
									                                       class="{{ linkClass() }}"
									                                       target="_blank"
									>{{ $searchLink }}</a>
								</span>
							</div>
						</div>
						
						<div class="row">
							<div class="col-md-12">
								@if (!empty($posts) && $totalPosts > 0)
									<div class="container px-0 pt-3 list-view">
										@foreach($posts as $key => $post)
											@php
												$borderBottom = !$loop->last ? ' border-bottom pb-3' : '';
											@endphp
											<div class="row{{ $borderBottom }} mb-3 d-flex align-items-stretch item-list">
												<div class="col-sm-2 col-12 d-flex justify-content-center p-0 main-image">
													<div class="container position-relative">
														<div class="position-absolute top-0 end-0 mt-2 me-3 bg-body-secondary opacity-75 rounded px-1">
															<i class="fa-solid fa-camera"></i> {{ data_get($post, 'count_pictures') }}
														</div>
														<a href="{{ urlGen()->post($post) }}">
															<img src="{{ data_get($post, 'picture.url.medium') }}"
															     alt="img"
															     class="lazyload img-fluid w-100 h-auto rounded"
															>
														</a>
													</div>
												</div>
												
												<div class="col-sm-7 col-12 d-flex flex-column justify-content-between">
													<div>
														<h5 class="fs-5 fw-bold px-0">
															<a href="{{ urlGen()->post($post) }}" class="{{ linkClass() }}">
																{{ data_get($post, 'title') }}
															</a>
														</h5>
														
														<ul class="list-inline mb-0 text-secondary">
															@if (!empty(data_get($post, 'postType')))
																<li class="list-inline-item">
																	<span class="badge rounded-pill text-bg-secondary fw-normal"
																	      data-bs-toggle="tooltip"
																	      data-bs-placement="right"
																	      title="{{ data_get($post, 'postType.label') }}"
																	>
																		{{ strtoupper(mb_substr(data_get($post, 'postType.label'), 0, 1)) }}
																	</span>
																</li>
															@endif
																<li class="list-inline-item">
																	<i class="fa-regular fa-clock"></i> {!! data_get($post, 'created_at_formatted') !!}
																</li>
																@if (!empty(data_get($post, 'category')))
																<li class="list-inline-item">
																	<i class="bi bi-folder"></i> {{ data_get($post, 'category.name') }}
																</li>
															@endif
															@if (!empty(data_get($post, 'city')))
																<li class="list-inline-item">
																	<i class="bi bi-geo-alt"></i> {{ data_get($post, 'city.name') }}
																</li>
															@endif
														</ul>
													</div>
												</div>
												
												<div class="col-sm-3 col-12 text-end text-nowrap d-flex flex-column justify-content-between">
													<h5 class="fs-4 fw-bold">
														{!! data_get($post, 'price_formatted') !!}
													</h5>
												</div>
											</div>
										@endforeach
									</div>
								@else
									<div class="py-5 text-center w-100">
										{{ $apiMessagePosts ?? t('Please select a saved search to show the result') }}
									</div>
								@endif
							</div>
						</div>
					</div>
					
					@include('vendor.pagination.api.bootstrap-5')
				</div>
			</div>
		</div>
	</div>
@endsection
