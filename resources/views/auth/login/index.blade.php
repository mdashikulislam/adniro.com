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
		<div class="alert alert-info text-center">
			{!! $resendVerificationLink !!}
		</div>
	@endif
@endsection

@section('content')
	<div class="auth-head">
		<span class="auth-head-icon"><i class="fa-solid fa-right-to-bracket"></i></span>
		<h2 class="auth-title">{{ t('login_page_title') }}</h2>
		<p class="auth-subtitle">{{ getLoginDescription() }}</p>
	</div>
	
	@include('auth.login.partials.social-buttons')
	
	<form id="loginForm" class="auth-form" action="{{ url()->current() }}" method="post" role="form">
		@csrf
		@honeypot
		
		<input type="hidden" name="country" value="{{ config('country.code') }}">
		
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
			'prefix'            => '<i class="bi bi-envelope"></i>',
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
			'prefix'         => '<i class="bi bi-lock"></i>',
			'togglePassword' => 'icon',
			'hint'           => false,
		])
		
		{{-- remember --}}
		@php
			$labelRight = '<a href="' . urlGen()->passwordForgot() . '" class="auth-link-strong">';
			$labelRight .= trans('auth.forgot_password');
			$labelRight .= '</a>';
		@endphp
		@include('helpers.forms.fields.checkbox', [
			'label'             => trans('auth.remember_me'),
			'labelRightContent' => $labelRight,
			'id'                => 'rememberMe',
			'name'              => 'remember',
			'value'             => null,
			'wrapper'           => ['class' => 'auth-remember'],
		])
		
		{{-- captcha --}}
		<div class="auth-captcha">
			@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
		</div>
		
		{{-- button --}}
		<button type="submit" id="loginBtn" class="btn btn-primary auth-submit">
			{{ trans('auth.log_in') }} <i class="fa-solid fa-arrow-right"></i>
		</button>
	</form>
	
	<div class="auth-alt">
		{{ trans('auth.dont_have_account') }} <a href="{{ urlGen()->signUp() }}">{{ trans('auth.create_account') }}</a>
	</div>
@endsection
