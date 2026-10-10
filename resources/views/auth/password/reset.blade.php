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
	$passwordReset ??= [];
	$passwordTips = getPasswordTips(withCommon: true);
	
	$authField = request()->query('field');
@endphp
@section('content')
	<div class="auth-head">
		<span class="auth-head-icon"><i class="fa-solid fa-unlock-keyhole"></i></span>
		<h2 class="auth-title">{{ trans('auth.reset_password') }}</h2>
		<p class="auth-subtitle">{{ getResetPasswordDescription() }}</p>
	</div>
	
	<form class="auth-form" action="{{ urlGen()->passwordReset() }}" method="post">
		@csrf
		@honeypot
		
		<input type="hidden" name="token" value="{{ $token }}">
		
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
			'placeholder'       => trans('auth.email_address'),
			'value'             => data_get($passwordReset, 'email'),
			'prefix'            => '<i class="bi bi-envelope"></i>',
			'wrapper'           => ['class' => 'auth-field-item'],
		])
		
		{{-- phone --}}
		@if (isPhoneAsAuthFieldEnabled())
			@php
				$labelRight = '<a href="" class="auth-field" data-auth-field="email">';
				$labelRight .= trans('auth.use_email');
				$labelRight .= '</a>';
				
				$phoneValue = data_get($passwordReset, 'phone');
				$phoneCountryValue = data_get($passwordReset, 'phone_country', config('country.code'));
			@endphp
			@include('helpers.forms.fields.intl-tel-input', [
				'label'             => trans('auth.phone_number'),
				'labelRightContent' => $labelRight,
				'id'                => 'phone',
				'name'              => 'phone',
				'required'          => (getAuthField() == 'phone'),
				'value'             => $phoneValue,
				'countryCode'       => $phoneCountryValue,
				'hint'              => trans('auth.forgot_password_hint_phone'),
				'wrapper'           => ['class' => 'auth-field-item'],
			])
		@endif
		
		{{-- auth_field --}}
		<input name="auth_field" type="hidden" value="{{ old('auth_field', getAuthField()) }}">
		
		{{-- password --}}
		@include('helpers.forms.fields.password', [
			'label'          => trans('auth.new_password'),
			'name'           => 'password',
			'placeholder'    => trans('auth.new_password'),
			'required'       => true,
			'value'          => null,
			'prefix'         => '<i class="bi bi-lock"></i>',
			'togglePassword' => 'icon',
		])
		
		{{-- password_confirmation --}}
		@include('helpers.forms.fields.password', [
			'label'          => trans('auth.confirm_new_password'),
			'name'           => 'password_confirmation',
			'placeholder'    => trans('auth.confirm_new_password'),
			'required'       => true,
			'value'          => null,
			'prefix'         => '<i class="bi bi-shield-lock"></i>',
			'togglePassword' => 'icon',
			'hint'           => '',
		])
		
		{{-- captcha --}}
		<div class="auth-captcha">
			@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
		</div>
		
		{{-- button --}}
		<button type="submit" class="btn btn-primary auth-submit">
			{{ trans('auth.reset_password') }} <i class="fa-solid fa-arrow-right"></i>
		</button>
	</form>
	
	<a class="auth-back" href="{{ urlGen()->signIn() }}">
		<i class="fa-solid fa-arrow-left"></i> {{ trans('auth.back_to_login') }}
	</a>
@endsection

@section('after_scripts')
	<script>
		if (typeof defaultAuthField === 'undefined') {
			var defaultAuthField;
		}
		defaultAuthField = '{{ old('auth_field', $authField) }}';
		{{--
		if (typeof phoneCountry === 'undefined') {
			var phoneCountry;
		}
		phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
		--}}
	</script>
@endsection
