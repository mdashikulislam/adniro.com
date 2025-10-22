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
		<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
			<div class="alert alert-danger">
				<p>{{ session('code') }}</p>
			</div>
		</div>
	@endif
@endsection

@section('content')
	<div class="col-11 col-sm-11 col-md-10 col-lg-9 col-xl-8 mx-auto">
		<h3 class="fw-600 mb-4">{{ trans('auth.two_factor_title') }}</h3>
		
		<p class="text-muted mb-4">{!! $pageDescription !!}</p>
		
		<form id="twoFactorOtpForm" role="form" method="post" action="{{ urlGen()->twoFactorChallenge() }}" data-submitting="false">
			@csrf
			@honeypot
			
			{{-- code --}}
			@php
				$codeError = (isset($errors) && $errors->has('code')) ? ' is-invalid' : '';
			@endphp
			<div class="row g-3" id="otpInputs">
				@for($i = 0; $i < $otpLength; $i++)
					<div class="col">
						<input type="text" class="form-control{{ $codeError }} text-center text-6 py-2" maxlength="1" autocomplete="off">
					</div>
				@endfor
			</div>
			<input type="hidden" name="code" id="otpCode">
			{{--
			<div class="mb-3">
				<label for="code" class="col-form-label">{{ trans('auth.code') }}:</label>
				<input id="code" name="code"
				       type="text"
				       placeholder="{{ trans('auth.enter_verification_code') }}"
				       class="form-control{{ $codeError }}"
				       value="{{ old('code') }}"
				       autocomplete="one-time-code"
				>
			</div>
			--}}
			<div class="d-grid my-4">
				<button type="submit" id="otpButton" class="btn btn-primary btn-lg btn-block">
					{{ trans('auth.verify') }}
				</button>
			</div>
		</form>
		
		<p class="text-center text-muted">
			{{ trans('auth.not_received_code') }} <a href="{{ urlGen()->twoFactorResend() }}">{{ trans('auth.resend_code') }}</a>
		</p>
		
		<p class="text-center text-muted">
			<a href="{{ urlGen()->signIn() }}">{{ trans('auth.back_to_login') }}</a>
		</p>
	</div>
@endsection
