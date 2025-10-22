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
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
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
		            @if (isset($errors) && $errors->any())
			            <div class="alert alert-danger alert-dismissible">
				            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				            <h5><strong>{{ t('validation_errors_title') }}</strong></h5>
				            <ul>
					            @foreach ($errors->all() as $error)
						            <li>{{ $error }}</li>
					            @endforeach
				            </ul>
			            </div>
		            @endif
		            
                    <div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2">
						
                        <h3 class="fw-bold border-bottom pb-3 mb-4">
							@if (!empty($selectedPackage))
								<i class="fa-solid fa-wallet"></i> {{ t('Payment') }}
							@else
								<i class="fa-solid fa-tags"></i> {{ t('subscription') }}
							@endif
						</h3>
						
                        <div class="row">
                            <div class="col-sm-12">
                                <form id="payableForm" action="{{ url()->current() }}" method="POST" class="form">
	                                @csrf
                                    <input type="hidden" name="payable_id" value="{{ $authUser->id }}">
                                    <fieldset>
										
										@if (!empty($selectedPackage))
											@include('front.payment.packages.selected')
										@else
											@include('front.payment.packages')
                                        @endif
										
                                        <div class="row">
                                            <div class="col-md-12 text-center mt-4">
												<a id="skipBtn" href="{{ urlGen()->accountOverview() }}" class="btn btn-secondary btn-lg">
													{{ t('Skip') }}
												</a>
                                                <button id="payableFormSubmitButton" class="btn btn-success btn-lg payableFormSubmitButton">
	                                                {{ t('Pay') }}
                                                </button>
                                            </div>
                                        </div>
                                        
                                    </fieldset>
                                </form>
                            </div>
                        </div>
	                    
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
	<script>
		const packageType = 'subscription';
		const formType = 'multiStep';
		const isCreationFormPage = false;
	</script>
	@include('front.common.js.payment-scripts')
	@include('front.common.js.payment-js')
@endsection
