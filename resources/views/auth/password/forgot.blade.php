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
		<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
			<div class="alert alert-success alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('status') }}</p>
			</div>
		</div>
	@endif
	
	@if (session()->has('email'))
		<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('email') }}</p>
			</div>
		</div>
	@endif
	
	@if (session()->has('phone'))
		<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('phone') }}</p>
			</div>
		</div>
	@endif
	
	@if (session()->has('login'))
		<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				<p class="mb-0">{{ session('login') }}</p>
			</div>
		</div>
	@endif
@endsection

@section('content')
	@if (!(isset($paddingTopExists) and $paddingTopExists))
		<div class="p-0 mt-lg-4 mt-md-3 mt-3"></div>
	@endif
	<div class="col-11 col-sm-11 col-md-10 col-lg-9 col-xl-8 mx-auto">
		<h3 class="fw-600 mb-5">{{ trans('auth.forgotten_password') }}</h3>
		
		<p class="text-muted mb-4">{{ getPasswordForgotDescription() }}</p>
		
		<form id="pwdForm" action="{{ urlGen()->passwordForgot() }}" method="post" role="form">
			@csrf
			@honeypot
			
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
					'placeholder'       => trans('auth.email_or_username'),
					'value'             => null,
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
				@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
				
				{{-- button --}}
				<div class="d-grid my-4">
					<button type="submit" id="pwdBtn" class="btn btn-primary btn-lg btn-block">
						{{ trans('auth.continue') }}
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
