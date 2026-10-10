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
	@if (session()->has('status'))
		<div>
			<div class="alert alert-success alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('status') }}</p>
			</div>
		</div>
	@endif
	
	@if (session()->has('email'))
		<div>
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('email') }}</p>
			</div>
		</div>
	@endif
	
	@if (session()->has('phone'))
		<div>
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('phone') }}</p>
			</div>
		</div>
	@endif
	
	@if (session()->has('login'))
		<div>
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('login') }}</p>
			</div>
		</div>
	@endif
@endsection

@section('content')
	<div class="auth-head">
		<span class="auth-head-icon"><i class="fa-solid fa-key"></i></span>
		<h2 class="auth-title">{{ trans('auth.forgotten_password') }}</h2>
		<p class="auth-subtitle">{{ getPasswordForgotDescription() }}</p>
	</div>
	
	<form id="pwdForm" class="auth-form" action="{{ urlGen()->passwordForgot() }}" method="post" role="form">
		@csrf
		@honeypot
		
		{{-- email --}}
		@php
			$labelRight = '';
			if (isPhoneAsAuthFieldEnabled()) {
				$labelRight .= '<a href="" class="auth-field" data-auth-field="phone">';
				$labelRight .= trans('auth.use_phone');
				$labelRight .= '</a>';
			}
		@endphp
		@include('helpers.forms.fields.email', [
			'label'             => trans('auth.email'),
			'labelRightContent' => $labelRight,
			'id'                => 'email',
			'name'              => 'email',
			'required'          => (getAuthField() == 'email'),
			'placeholder'       => trans('auth.email_or_username'),
			'value'             => null,
			'prefix'            => '<i class="bi bi-envelope"></i>',
			'hint'              => trans('auth.forgot_password_hint_email'),
			'wrapper'           => ['class' => 'auth-field-item'],
		])
		
		{{-- phone --}}
		@if (isPhoneAsAuthFieldEnabled())
			@php
				$labelRight = '<a href="" class="auth-field" data-auth-field="email">';
				$labelRight .= trans('auth.use_email');
				$labelRight .= '</a>';
				
				$phoneCountryValue = config('country.code');
			@endphp
			@include('helpers.forms.fields.intl-tel-input', [
				'label'             => trans('auth.phone_number'),
				'labelRightContent' => $labelRight,
				'id'                => 'phone',
				'name'              => 'phone',
				'required'          => (getAuthField() == 'phone'),
				'value'             => null,
				'countryCode'       => $phoneCountryValue,
				'hint'              => trans('auth.forgot_password_hint_phone'),
				'wrapper'           => ['class' => 'auth-field-item'],
			])
		@endif
		
		{{-- auth_field --}}
		<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">
		
		{{-- captcha --}}
		<div class="auth-captcha">
			@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
		</div>
		
		{{-- button --}}
		<button type="submit" id="pwdBtn" class="btn btn-primary auth-submit">
			{{ trans('auth.continue') }} <i class="fa-solid fa-arrow-right"></i>
		</button>
	</form>
	
	<a class="auth-back" href="{{ urlGen()->signIn() }}">
		<i class="fa-solid fa-arrow-left"></i> {{ trans('auth.back_to_login') }}
	</a>
	
	<div class="auth-alt">
		{{ trans('auth.dont_have_account') }} <a href="{{ urlGen()->signUp() }}">{{ trans('auth.create_account') }}</a>
	</div>
@endsection
