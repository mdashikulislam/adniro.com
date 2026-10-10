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
	$fieldHiddenValue = session('twoFactorMethodValue');
	$otpLength = defaultOtpLength();
	
	$pageDescription = !empty($fieldHiddenValue)
		? trans('auth.two_factor_description', ['fieldHiddenValue' => $fieldHiddenValue])
		: trans('auth.two_factor_description_std');
@endphp

@section('notifications')
	@if (session()->has('code'))
		<div class="alert alert-danger">
			<p class="mb-0">{{ session('code') }}</p>
		</div>
	@endif
@endsection

@section('content')
	<div class="auth-head">
		<span class="auth-head-icon"><i class="fa-solid fa-shield-halved"></i></span>
		<h2 class="auth-title">{{ trans('auth.two_factor_title') }}</h2>
		<p class="auth-subtitle">{!! $pageDescription !!}</p>
	</div>
	
	<form id="twoFactorOtpForm" class="auth-form" role="form" method="post" action="{{ urlGen()->twoFactorChallenge() }}" data-submitting="false">
		@csrf
		@honeypot
		
		{{-- code --}}
		@php
			$codeError = (isset($errors) && $errors->has('code')) ? ' is-invalid' : '';
		@endphp
		<div class="auth-otp" id="otpInputs">
			@for($i = 0; $i < $otpLength; $i++)
				<input type="text" class="form-control{{ $codeError }} auth-otp-input" maxlength="1" inputmode="numeric" autocomplete="off" aria-label="{{ trans('auth.code') }} {{ $i + 1 }}">
			@endfor
		</div>
		<input type="hidden" name="code" id="otpCode">
		
		<button type="submit" id="otpButton" class="btn btn-primary auth-submit">
			{{ trans('auth.verify') }} <i class="fa-solid fa-arrow-right"></i>
		</button>
	</form>
	
	<div class="auth-resend">
		{{ trans('auth.not_received_code') }} <a href="{{ urlGen()->twoFactorResend() }}">{{ trans('auth.resend_code') }}</a>
	</div>
	
	<a class="auth-back" href="{{ urlGen()->signIn() }}">
		<i class="fa-solid fa-arrow-left"></i> {{ trans('auth.back_to_login') }}
	</a>
@endsection
