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
	$paymentType ??= 'promotion';
	$isPromoting ??= true;
	$isSubscripting ??= false;
	
	$apiResult ??= [];
	$transactions = (array)data_get($apiResult, 'data');
	$totalTransactions = (int)data_get($apiResult, 'meta.total', 0);
	
	$stats ??= [];
	$countPromoTransactions = data_get($stats, 'transactions.promotion');
	$countSubsTransactions = data_get($stats, 'transactions.subscription');
	$isAllTypesOfTransactionExist = ($countPromoTransactions > 0 && $countSubsTransactions > 0);
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
							<i class="fa-solid fa-coins"></i> {{ t('Transactions') }}
						</h3>
						
						@if ($isAllTypesOfTransactionExist)
							<div class="col-12 mb-4">
								<div class="row d-flex justify-content-center">
									<div class="col-sm-6 col-6 text-right pe-1">
										<a class="btn {{ $isPromoting ? 'btn-primary' : 'btn-secondary' }}"
										   href="{{ url(urlGen()->getAccountBasePath() . '/transactions/promotion') }}"
										>{{ t('promo_packages_tab') }}</a>
									</div>
									<div class="col-sm-6 col-6 text-left ps-1">
										<a class="btn {{ $isSubscripting ? 'btn-primary' : 'btn-secondary' }}"
										        href="{{ url(urlGen()->getAccountBasePath() . '/transactions/subscription') }}"
										>{{ t('subs_packages_tab') }}</a>
									</div>
								</div>
							</div>
						@endif
						
						<div class="table-responsive">
							<table class="table mb-0 table-striped">
								<thead>
								<tr>
									<th scope="col" class="align-middle">
										<span data-bs-toggle="tooltip" title="{{ t('reference') }}">{{ t('ref') }}</span>
									</th>
									<th scope="col" class="align-middle">{{ t('Description') }}</th>
									<th scope="col" class="align-middle">{{ t('Payment Method') }}</th>
									<th scope="col" class="align-middle">{{ t('amount') }}</th>
									<th scope="col" class="align-middle">{{ t('Date') }}</th>
									<th scope="col" class="align-middle">{{ t('Status') }}</th>
								</tr>
								</thead>
								<tbody>
								@if (!empty($transactions) && $totalTransactions > 0)
									@foreach($transactions as $key => $transaction)
										<tr>
											<td class="align-middle">{{ data_get($transaction, 'id') }}</td>
											<td class="align-middle">
												@if ($isPromoting)
													@php
														$postUrl = urlGen()->post(data_get($transaction, 'payable'));
													@endphp
													<strong>{{ t('Listing') }}</strong>
													<a href="{{ $postUrl }}" class="{{ linkClass() }}">
														{{ data_get($transaction, 'payable.title') }}
													</a>
												@else
													<strong>{{ t('account') }}</strong> {{ data_get($transaction, 'payable.name') }}
												@endif
												<br><strong>{{ t('Package') }}</strong> {{ data_get($transaction, 'package.short_name') }}
												@if ($isSubscripting)
													@if (data_get($transaction, 'expired') != 1)
														<br><strong>{{ t('remaining_listings') }}</strong>
															{{ data_get($transaction, 'remaining_posts') }}
													@endif
												@endif
												<br>{{ data_get($transaction, 'starting_info') }}
												<br>{{ data_get($transaction, 'expiry_info') }}
											</td>
											<td class="align-middle">
												{{ data_get($transaction, 'paymentMethod.display_name', '--') }}
											</td>
											<td class="align-middle">
												{!! data_get($transaction, 'package.currency.symbol') . data_get($transaction, 'package.price') !!}
											</td>
											<td class="align-middle">
												{!! data_get($transaction, 'created_at_formatted') !!}
											</td>
											<td class="align-middle">
												@php
													$expiryInfo = data_get($transaction, 'expiry_info');
													$tooltip = ' data-bs-toggle="tooltip" title="' . $expiryInfo . '"';
													$cssClass = 'bg-' . data_get($transaction, 'css_class_variant');
												@endphp
												<span class="badge {{ $cssClass }}"{!! $tooltip !!}>
													{{ data_get($transaction, 'status_info') }}
												</span>
											</td>
										</tr>
									@endforeach
								@else
									<tr>
										<td colspan="6">
											<div class="text-center my-5">
												{{ $apiMessage ?? t('no_payments_found') }}
											</div>
										</td>
									</tr>
								@endif
								</tbody>
							</table>
						</div>
						
						@include('vendor.pagination.api.bootstrap-5')
					
					</div>
				</div>
				
			</div>
		</div>
	</div>
@endsection

@section('after_scripts')
@endsection
