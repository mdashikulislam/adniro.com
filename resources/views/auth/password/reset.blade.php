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
	@if (!(isset($paddingTopExists) && $paddingTopExists))
		<div class="p-0 mt-lg-4 mt-md-3 mt-3"></div>
	@endif
	<div class="col-11 col-sm-11 col-md-10 col-lg-9 col-xl-8 mx-auto">
		<h3 class="fw-600 mb-5">{{ trans('auth.reset_password') }}</h3>
		
		<p class="text-muted mb-4">{{ getResetPasswordDescription() }}</p>
		
		<form action="{{ urlGen()->passwordReset() }}" method="post">
			@csrf
			@honeypot
			
			<input type="hidden" name="token" value="{{ $token }}">
			
			<div class="row">
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
					'wrapper'           => [
						'class' => 'mb-3 col-md-12 auth-field-item',
					],
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
						'wrapper'           => [
							'class' => 'mb-3 col-md-12 auth-field-item',
						],
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
					'togglePassword' => 'link',
					'baseClass'      => ['wrapper' => 'mb-3 col-md-10'],
				])
				
				{{-- password_confirmation --}}
				@include('helpers.forms.fields.password', [
					'label'          => trans('auth.confirm_new_password'),
					'name'           => 'password_confirmation',
					'placeholder'    => trans('auth.confirm_new_password'),
					'required'       => true,
					'value'          => null,
					'togglePassword' => 'link',
					'baseClass'      => ['wrapper' => 'mb-3 col-md-10'],
				])
				
				{{-- captcha --}}
				@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
				
				{{-- button --}}
				<div class="d-grid my-4">
					<button type="submit" class="btn btn-primary btn-lg btn-block">
						{{ trans('auth.reset_password') }}
					</button>
				</div>
			</div>
		</form>
		
		<p class="text-center text-muted">
			<a href="{{ urlGen()->signIn() }}">{{ trans('auth.back_to_login') }}</a>
		</p>
		<p class="text-center text-muted">
			{{ trans('auth.dont_have_account') }} <a href="{{ urlGen()->signUp() }}">{{ trans('auth.create_account') }}</a>
		</p>
	</div>
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
