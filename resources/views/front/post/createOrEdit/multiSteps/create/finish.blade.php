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

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				@include('front.post.partials.notification')
				
				<div class="col-xl-12">
					
					@if (session()->has('message'))
						<div class="listing-finish">
							<span class="listing-finish-icon"><i class="bi bi-check-lg"></i></span>
							<h2 class="listing-finish-title">{{ t('congratulations') }}</h2>
							<p class="listing-finish-text">{{ session('message') }}</p>
							<div class="listing-finish-actions">
								<a href="{{ url('/') }}" class="btn btn-primary listing-finish-btn"><i class="bi bi-house-door"></i> {{ t('Homepage') }}</a>
								@if (auth()->check())
									<a href="{{ url(urlGen()->getAccountBasePath() . '/posts/list') }}" class="btn btn-outline-primary listing-finish-btn"><i class="bi bi-collection"></i> {{ t('my_listings') }}</a>
								@endif
							</div>
						</div>
					@endif
					
				</div>
			</div>
		</div>
	</div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection
@php
	if (!session()->has('resendEmailVerificationData') && !session()->has('resendPhoneVerificationData')) {
		if (session()->has('message')) {
			session()->forget('message');
		}
	}
@endphp
