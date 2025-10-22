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

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				@include('front.post.partials.notification')
				
				<div class="col-xl-12">
					
					@if (session()->has('message'))
						<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 mb-sm-3">
							<div class="row">
								<div class="col-12">
									<div class="alert alert-success mb-0" role="alert">
										<h2 class="p-0 mb-3">
											<i class="fa-regular fa-circle-check"></i> {{ t('congratulations') }}
										</h2>
										<p class="mb-0">
											{{ session('message') }} <a href="{{ url('/') }}">{{ t('Homepage') }}</a>
										</p>
									</div>
								</div>
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
