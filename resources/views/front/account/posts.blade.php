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
	$apiResult ??= [];
	$posts = (array)data_get($apiResult, 'data');
	$totalPosts = (int)data_get($apiResult, 'meta.total', 0);
	$pagePath ??= null;
	
	$countPromotionPackages ??= 0;
	$countPaymentMethods ??= 0;
	
	$pageData = [
		'list' => [
			'icon'     => 'fa-solid fa-bullhorn',
			'title'    => t('my_listings'),
			'basePath' => urlGen()->getAccountBasePath() . '/posts/list',
		],
		'archived' => [
			'icon'     => 'bi bi-calendar-x',
			'title'    => t('archived_listings'),
			'basePath' => urlGen()->getAccountBasePath() . '/posts/archived',
		],
		'pending-approval' => [
			'icon'     => 'bi bi-hourglass-split',
			'title'    => t('pending_approval'),
			'basePath' => urlGen()->getAccountBasePath() . '/posts/pending-approval',
		],
		'saved-posts' => [
			'icon'     => 'bi bi-bookmarks',
			'title'    => t('favourite_listings'),
			'basePath' => urlGen()->getAccountBasePath() . '/saved-posts',
		],
	];
	
	$pageIcon = $pageData[$pagePath]['icon'] ?? 'fa-solid fa-bullhorn';
	$pageTitle = $pageData[$pagePath]['title'] ?? t('posts');
	$basePath = $pageData[$pagePath]['basePath'] ?? urlGen()->getAccountBasePath() . '/posts/undefined';
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
						<h3 class="fw-bold border-bottom pb-3 mb-4">
							<i class="{{ $pageIcon }}"></i> {{ $pageTitle }}
						</h3>
						
						<div class="table-responsive">
							<form name="listForm" action="{{ url($basePath . '/delete') }}" method="POST">
								@csrf
								
								<div class="d-flex justify-content-between bg-body rounded p-3 mb-3 table-action">
									<div class="text-nowrap d-flex align-items-center">
										<div class="btn-group" role="group">
											<button type="button" class="btn btn-sm btn btn-outline-primary pb-0">
												<input type="checkbox" id="checkAll" class="from-check-all">
											</button>
											<button type="button" class="btn btn-sm btn btn-primary from-check-all">
												{{ t('Select') }}: {{ t('All') }}
											</button>
										</div>
										
										<button type="submit" class="btn btn-sm btn btn-danger ms-1 confirm-simple-action">
											<i class="fa-regular fa-trash-can"></i> {{ t('Delete') }}
										</button>
									</div>
									
									<div class="w-100 table-search">
										<div class="row">
											<label class="col-5 my-0 form-label text-end">{{ t('search') }} <br>
												<a title="clear filter" class="clear-filter {{ linkClass() }}" href="#clear">
													[{{ t('clear') }}]
												</a>
											</label>
											<div class="col-7 my-0">
												<input type="text" class="form-control" id="filter">
											</div>
										</div>
									</div>
								</div>
								
								<table id="addManageTable"
									   class="table mb-0 table-striped"
									   data-filter="#filter"
									   data-filter-text-only="true"
								>
									<thead>
									<tr>
										<th scope="col" data-type="numeric" data-sort-initial="true"></th>
										<th scope="col">{{ t('Photo') }}</th>
										<th scope="col" data-sort-ignore="true">{{ t('listing_details') }}</th>
										<th scope="col" data-type="numeric" class="d-md-table-cell d-sm-none d-none">--</th>
										<th scope="col">{{ t('action') }}</th>
									</tr>
									</thead>
									<tbody>
									
									@if (!empty($posts) && $totalPosts > 0)
										@foreach($posts as $post)
											@php
												$postUrl = urlGen()->post($post);
												$deletingUrl = url($basePath . '/' . data_get($post, 'id') . '/delete');
												
												$isForOwnerEdition = (
													in_array($pagePath, ['list', 'pending-approval'])
													&& isset($authUser, $authUser->id)
													&& $authUser->id == data_get($post, 'user_id')
												);
												
												$isEditingAllowed = (
													$isForOwnerEdition
													&& empty(data_get($post, 'archived_at'))
												);
												$isPhotoEditingAllowed = (
													$isForOwnerEdition
													&& isMultipleStepsFormEnabled()
												);
												$isPlanPaymentAllowed = (
													$isForOwnerEdition
													&& isMultipleStepsFormEnabled()
													&& $countPromotionPackages > 0 && $countPaymentMethods > 0
												);
												$isArchivingAllowed = (
													$pagePath == 'list'
													&& isVerifiedPost($post)
													&& empty(data_get($post, 'archived_at'))
												);
												$isRepostingAllowed = (
													$pagePath == 'archived'
													&& isset($authUser, $authUser->id)
													&& $authUser->id == data_get($post, 'user_id')
													&& !empty(data_get($post, 'archived_at'))
												);
												
												$editingUrl = urlGen()->editPost($post);
												$photoEditingUrl = url('posts/' . data_get($post, 'id') . '/photos');
												$planPaymentUrl = url('posts/' . data_get($post, 'id') . '/payment');
												$archivingUrl = url($basePath . '/' . data_get($post, 'id') . '/offline');
												$repostingUrl = url($basePath . '/' . data_get($post, 'id') . '/repost');
											@endphp
											<tr>
												<td style="width:2%" class="add-img-selector">
													<div class="checkbox">
														<label><input type="checkbox" name="entries[]" value="{{ data_get($post, 'id') }}"></label>
													</div>
												</td>
												<td style="width:20%" class="add-img-td">
													<a href="{{ $postUrl }}">
														<img class="img-thumbnail img-fluid" src="{{ data_get($post, 'picture.url.medium') }}" alt="img">
													</a>
												</td>
												<td style="width:52%" class="items-details-td">
													<div>
														<p>
															<a href="{{ $postUrl }}"
															   class="{{ linkClass() }} fw-bold"
															   title="{{ data_get($post, 'title') }}"
															>
																{{ str(data_get($post, 'title'))->limit(40) }}
															</a>
															@if (in_array($pagePath, ['list', 'archived', 'pending-approval']))
																@if (
																	!empty(data_get($post, 'payment'))
																	&& !empty(data_get($post, 'payment.package'))
																)
																	@php
																		$ribbonColor = data_get($post, 'payment.package.ribbon');
																		$ribbonColorClass = BootstrapColor::Badge->getColorClass($ribbonColor);
																		$packageShortName = data_get($post, 'payment.package.short_name');
																		$packageInfo = '';
																		if (data_get($post, 'featured') != 1) {
																			$ribbonColorClass = 'text-bg-secondary';
																			$packageInfo = ' (' . t('expired') . ')';
																		}
																	@endphp
																	<span class="badge rounded-pill {{ $ribbonColorClass }}"
																	      data-bs-toggle="tooltip"
																	      data-bs-placement="bottom"
																	      title="{{ $packageShortName . $packageInfo }}"
																	>
																		{{ $packageShortName }}
																	</span>
																@endif
															@endif
														</p>
														@php
															$listingDates = getListingDates($post, $pagePath);
														@endphp
														@if (!empty($listingDates))
															@foreach($listingDates as $label => $labeledDate)
																<p class="mb-1">
																	<i class="fa-regular fa-clock"
																	   data-bs-toggle="tooltip"
																	   data-bs-placement="bottom"
																	   title="{{ $label }}"
																	></i>&nbsp;{!! $labeledDate !!}
																</p>
															@endforeach
														@endif
														<p class="mb-1">
															<i class="fa-regular fa-eye"
															   data-bs-toggle="tooltip"
															   data-bs-placement="bottom"
															   title="{{ t('Visitors') }}"
															></i> {{ data_get($post, 'visits_formatted') ?? 0 }}
															
															<i class="bi bi-geo-alt"
															   data-bs-toggle="tooltip"
															   data-bs-placement="bottom"
															   title="{{ t('Located In') }}"
															></i> {{ data_get($post, 'city.name') ?? '-' }}
															
															<img src="{{ data_get($post, 'country_flag_url') }}" alt=""
															     data-bs-toggle="tooltip"
															     title="{{ data_get($post, 'country.name') }}"
															>
														</p>
													</div>
												</td>
												<td style="width:16%" class="price-td d-md-table-cell d-sm-none d-none">
													<div class="fw-bold">
														{!! data_get($post, 'price_formatted') !!}
													</div>
												</td>
												<td style="width:10%" class="action-td">
													<div>
														<div class="btn-group">
															<button type="button"
															        class="btn btn btn-outline-primary dropdown-toggle"
															        data-bs-toggle="dropdown"
															        aria-expanded="false"
															>
																{{ t('action') }}
															</button>
															<ul class="dropdown-menu">
																@if ($isEditingAllowed)
																	<li>
																		<a class="dropdown-item" href="{{ $editingUrl }}">
																			<i class="fa-regular fa-pen-to-square"></i> {{ t('Edit') }}
																		</a>
																	</li>
																@endif
																@if ($isPhotoEditingAllowed)
																	<li>
																		<a class="dropdown-item" href="{{ $photoEditingUrl }}">
																			<i class="bi bi-camera"></i> {{ t('Update Photos') }}
																		</a>
																	</li>
																@endif
																@if ($isPlanPaymentAllowed)
																	<li>
																		<a class="dropdown-item" href="{{ $planPaymentUrl }}">
																			<i class="fa-regular fa-circle-check"></i> {{ t('Make It Premium') }}
																		</a>
																	</li>
																@endif
																@if ($isArchivingAllowed)
																	<li>
																		<a class="dropdown-item confirm-simple-action" href="{{ $archivingUrl }}">
																			<i class="fa-solid fa-eye-slash"></i> {{ t('put_it_offline') }}
																		</a>
																	</li>
																@endif
																@if ($isRepostingAllowed)
																	<li>
																		<a class="dropdown-item confirm-simple-action" href="{{ $repostingUrl }}">
																			<i class="fa-solid fa-recycle"></i> {{ t('re_post_it') }}
																		</a>
																	</li>
																@endif
																<li>
																	<a class="dropdown-item confirm-simple-action text-danger"
																	   href="{{ $deletingUrl }}"
																	>
																		<i class="fa-regular fa-trash-can"></i> {{ t('Delete') }}
																	</a>
																</li>
															</ul>
														</div>
													</div>
												</td>
											</tr>
										@endforeach
									@else
										<tr>
											<td colspan="5">
												<div class="text-center my-5">
													{{ $apiMessage ?? t('no_posts_found') }}
												</div>
											</td>
										</tr>
									@endif
									</tbody>
								</table>
							</form>
						</div>
						
						@include('vendor.pagination.api.bootstrap-5')
						
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('after_scripts')
	<script src="{{ url('assets/plugins/footable-jquery/2.0.1.4/footable.js?v=2-0-1') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/footable-jquery/2.0.1.4/footable.filter.js?v=2-0-1') }}" type="text/javascript"></script>
	<script type="text/javascript">
		onDocumentReady((event) => {
			$('#addManageTable').footable().bind('footable_filtering', function (e) {
				let selected = $('.filter-status').find(':selected').text();
				if (selected && selected.length > 0) {
					e.filter += (e.filter && e.filter.length > 0) ? ' ' + selected : selected;
					e.clear = !e.filter;
				}
			});
			
			/* Clear Filter OnClick */
			const clearFilterEl = document.querySelector(".clear-filter");
			if (clearFilterEl) {
				clearFilterEl.addEventListener("click", (event) => {
					event.preventDefault();
					
					const filterStatusEl = document.querySelector(".filter-status");
					if (filterStatusEl) {
						filterStatusEl.value = '';
					}
					
					$('table.demo').trigger('footable_clear_filter');
				});
			}
			
			/* Check All OnClick */
			const checkAllEls = document.querySelectorAll('.from-check-all');
			if (checkAllEls.length > 0) {
				checkAllEls.forEach(checkEl => {
					checkEl.addEventListener('click', (event) => checkAllBoxes(event.target));
				});
			}
		});
	</script>
@endsection
