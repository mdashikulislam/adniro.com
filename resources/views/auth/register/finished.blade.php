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
@extends('auth.layouts.master')

@php
	$mbAuth ??= '';
@endphp

@section('notifications')
@endsection

@section('content')
	<div class="col-11 col-sm-11 col-md-10 col-lg-9 col-xl-8 mx-auto">
		
		@if (session()->has('message'))
			<h3 class="fw-600"><i class="fa-regular fa-circle-check"></i> {{ trans('auth.congratulations') }}</h3>
			
			<p class="text-muted">
				{{ session('message') }} <a href="{{ url('/') }}">{{ trans('auth.back_to_home') }}</a>
			</p>
		@endif
		
		@php
			$withMessage = !session()->has('flash_messages');
			$resendVerificationLink = getResendVerificationLink(withMessage: $withMessage);
		@endphp
		@if (!empty($resendVerificationLink))
			<div class="alert alert-info text-center">
				{!! $resendVerificationLink !!}
			</div>
		@endif
	
	</div>
@endsection

@php
	if (!session()->has('resendEmailVerificationData') && !session()->has('resendPhoneVerificationData')) {
		if (session()->has('message')) {
			session()->forget('message');
		}
	}
@endphp
