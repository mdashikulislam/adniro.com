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

@section('notifications')
	@php
		$withMessage = !session()->has('flash_messages');
		$resendVerificationLink = getResendVerificationLink(withMessage: $withMessage);
	@endphp
	@if (!empty($resendVerificationLink))
		<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
			<div class="alert alert-info text-center">
				{!! $resendVerificationLink !!}
			</div>
		</div>
	@endif
@endsection

@section('content')
	<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
		@php
			// $mbAuth = socialLogin()->isEnabled() ? ' mb-4' : ' mb-4';
			$mbAuth = ' mb-4';
		@endphp
		<div class="row d-flex justify-content-center">
			<div class="col-12 col-sm-12 col-md-12 col-lg-11 col-xl-10 col-xxl-8">
				<h3 class="fw-600{{ $mbAuth }}">{{ trans('auth.sign_in') }}</h3>
			</div>
		</div>
		
		@include('auth.login.partials.social', ['page' => 'login', 'position' => 'top'])
		
		<div class="row d-flex justify-content-center">
			<div class="col-12 col-sm-12 col-md-12 col-lg-11 col-xl-10 col-xxl-8">
				<p class="text-muted mb-4">{{ getLoginDescription() }}</p>
				
				<form id="loginForm" action="{{ url()->current() }}" method="post" role="form">
					@csrf
					@honeypot
					
					<input type="hidden" name="country" value="{{ config('country.code') }}">
					
					<div class="row">
						{{-- email --}}
						@php
							$labelRight = '';
							if (isPhoneAsAuthFieldEnabled()) {
								$labelRight .= '<a href="" class="auth-field" data-auth-field="phone">';
								$labelRight .= trans('auth.login_with_phone');
								$labelRight .= '</a>';
							}
							$emailValue = session()->has('email') ? session('email') : null;
						@endphp
						@include('helpers.forms.fields.text', [
							'label'             => trans('auth.email'),
							'labelRightContent' => $labelRight,
							'id'                => 'email',
							'name'              => 'email',
							'required'          => (getAuthField() == 'email'),
							'placeholder'       => trans('auth.email_or_username'),
							'value'             => $emailValue,
							'wrapper'           => ['class' => 'auth-field-item'],
						])
						
						{{-- phone --}}
						@if (isPhoneAsAuthFieldEnabled())
							@php
								$labelRight = '<a href="" class="auth-field" data-auth-field="email">';
								$labelRight .= trans('auth.login_with_email');
								$labelRight .= '</a>';
								
								$phoneValue = session()->has('phone') ? session('phone') : null;
								$phoneCountryValue = config('country.code');
							@endphp
							@include('helpers.forms.fields.intl-tel-input', [
								'label'             => trans('auth.phone_number'),
								'labelRightContent' => $labelRight,
								'id'                => 'phone',
								'name'              => 'phone',
								'required'          => (getAuthField() == 'phone'),
								'placeholder'       => null,
								'value'             => $phoneValue,
								'countryCode'       => $phoneCountryValue,
								'wrapper'           => ['class' => 'auth-field-item'],
							])
						@endif
						
						{{-- auth_field --}}
						<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">
						
						{{-- password --}}
						@include('helpers.forms.fields.password', [
							'label'          => trans('auth.password'),
							'name'           => 'password',
							'placeholder'    => trans('auth.password'),
							'required'       => true,
							'value'          => null,
							'togglePassword' => 'link',
							'hint'           => false,
						])
						
						{{-- remember --}}
						@php
							$labelRight = '<a href="' . urlGen()->passwordForgot() . '">';
							$labelRight .= trans('auth.forgot_password');
							$labelRight .= '</a>';
						@endphp
						@include('helpers.forms.fields.checkbox', [
							'label'             => trans('auth.remember_me'),
							'labelRightContent' => $labelRight,
							'id'                => 'rememberMe',
							'name'              => 'remember',
							'value'             => null,
							'wrapper'           => ['class' => 'mt-4'],
						])
						
						{{-- captcha --}}
						@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
						
						{{-- button --}}
						<div class="d-grid my-4">
							<button type="submit" id="loginBtn" class="btn btn-primary btn-block">{{ trans('auth.log_in') }}</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		
		<p class="text-center text-muted mb-0">
			{{ trans('auth.dont_have_account') }} <a href="{{ urlGen()->signUp() }}">{{ trans('auth.create_account') }}</a>
		</p>
	
	</div>
@endsection
