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

@section('wizard')
    @include('front.post.createOrEdit.multiSteps.partials.wizard')
@endsection

@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
	
	// Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
	$formActionUrl ??= request()->fullUrl();
	$nextStepUrl ??= '/';
	$nextStepLabel ??= t('submit');
@endphp
@section('content')
	@include('front.common.spacer')
    <div class="main-container">
        <div class="container">
            <div class="row">
    
                @include('front.post.partials.notification')
                
                <div class="col-md-12">
                    <div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 mb-sm-3">
						
                        <h3 class="fw-bold border-bottom pb-3 mb-4">
							@if (!empty($selectedPackage))
		                        <i class="bi bi-wallet"></i> {{ t('Payment') }}
							@else
								<i class="fa-solid fa-tags"></i> {{ t('Pricing') }}
							@endif
						</h3>
						
                        <div class="row">
                            <div class="col-12">
                                <form id="payableForm" action="{{ $formActionUrl }}" method="POST" class="form">
	                                @csrf
	                                
                                    <div class="row">
										
										@if (!empty($selectedPackage))
											@include('front.payment.packages.selected')
										@else
											@include('front.payment.packages')
                                        @endif
										
                                        <div class="col-12 mt-5">
	                                        <div class="row">
	                                            <div class="col-md-6 mb-md-0 mb-2 text-start d-grid">
													<a href="{{ $previousStepUrl }}" class="btn btn-secondary btn-lg">
														{!! $previousStepLabel !!}
													</a>
	                                            </div>
		                                        <div class="col-md-6 mb-md-0 mb-2 text-end d-grid">
	                                                <button id="payableFormSubmitButton" class="btn btn-primary btn-lg payableFormSubmitButton">
		                                                {!! $nextStepLabel !!}
	                                                </button>
	                                            </div>
	                                        </div>
                                        </div>
                                    
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection

@section('after_scripts')
	<script>
		const packageType = 'promotion';
		const formType = 'multiStep';
		const isCreationFormPage = true;
	</script>
	@include('front.common.js.payment-scripts')
	@include('front.common.js.payment-js')
@endsection
