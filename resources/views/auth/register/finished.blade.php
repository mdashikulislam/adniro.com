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
	@if (session()->has('message'))
		<div class="auth-head auth-head-success">
			<span class="auth-head-icon"><i class="fa-solid fa-circle-check"></i></span>
			<h2 class="auth-title">{{ trans('auth.congratulations') }}</h2>
			<p class="auth-subtitle">{{ session('message') }}</p>
		</div>
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
	
	<a href="{{ url('/') }}" class="btn btn-primary auth-submit">
		<i class="fa-solid fa-house"></i> {{ trans('auth.back_to_home') }}
	</a>
@endsection

@php
	if (!session()->has('resendEmailVerificationData') && !session()->has('resendPhoneVerificationData')) {
		if (session()->has('message')) {
			session()->forget('message');
		}
	}
@endphp
