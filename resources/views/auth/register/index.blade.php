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

@section('content')
	<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto">
		@php
			// $mbAuth = socialLogin()->isEnabled() ? ' mb-4' : ' mb-4';
			$mbAuth = ' mb-4';
		@endphp
		<div class="row d-flex justify-content-center">
			<div class="col-12 col-sm-12 col-md-12 col-lg-11 col-xl-10 col-xxl-8">
				<h3 class="fw-600{{ $mbAuth }}">{{ trans('auth.sign_up') }}</h3>
			</div>
		</div>
		
		@include('auth.login.partials.social', ['page' => 'register', 'position' => 'top'])
		
		<div class="row d-flex justify-content-center">
			<div class="col-12 col-sm-12 col-md-12 col-lg-11 col-xl-10 col-xxl-8">
				<p class="text-muted mb-4">{{ trans('auth.register_description') }}</p>
				
				<form id="signupForm" action="{{ url()->current() }}" method="post" class="{{ unsavedFormGuard() }}">
					@csrf
					@honeypot
					
					<div class="row">
						{{-- name --}}
						@include('helpers.forms.fields.text', [
							'label'       => t('Name'),
							'name'        => 'name',
							'placeholder' => t('enter_your_name'),
							'required'    => true,
							'value'       => null,
						])
						
						{{-- country_code --}}
						@if (empty(config('country.code')))
							@php
								$countries ??= [];
								$countryCodeOptions = collect($countries)
									->map(function($item) {
										return [
											'value'      => $item['code'] ?? null,
											'text'       => $item['name'] ?? null,
											'attributes' => ['data-admin-type' => $item['admin_type'] ?? 0],
										];
									})->toArray();
								
								$selectedCountryCode = (!empty(config('ipCountry.code'))) ? config('ipCountry.code') : 0;
							@endphp
							@include('helpers.forms.fields.select2', [
								'label'       => t('your_country'),
								'id'          => 'countryCode',
								'name'        => 'country_code',
								'required'    => true,
								'placeholder' => t('select_a_country'),
								'options'     => $countryCodeOptions,
								'value'       => $selectedCountryCode,
								'hint'        => null,
								'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
							])
						@else
							<input id="countryCode" name="country_code" type="hidden" value="{{ config('country.code') }}">
						@endif
						
						{{-- auth_field (as notification channel) --}}
						@php
							$authFields = getAuthFields(true);
							$authFields = collect($authFields)
								->map(fn($item, $key) => ['value' => $key, 'text' => $item])
								->toArray();
							
							$usersCanChooseNotifyChannel = isUsersCanChooseNotifyChannel();
							$authFieldValue = ($usersCanChooseNotifyChannel) ? (old('auth_field', getAuthField())) : getAuthField();
						@endphp
						@if ($usersCanChooseNotifyChannel)
							@include('helpers.forms.fields.radio', [
								'label'      => trans('auth.notifications_channel'),
								'btnVariant' => 'secondary',
								'btnOutline' => true,
								'id'         => 'authField-',
								'name'       => 'auth_field',
								'inline'     => true,
								'required'   => true,
								'options'    => $authFields,
								'value'      => $authFieldValue,
								'attributes' => ['class' => 'auth-field-input'],
								'hint'       => trans('auth.notifications_channel_hint'),
							])
						@else
							<input id="authField-{{ $authFieldValue }}" name="auth_field" type="hidden" value="{{ $authFieldValue }}">
						@endif
						
						@php
							$forceToDisplay = isBothAuthFieldsCanBeDisplayed() ? ' force-to-display' : '';
						@endphp
						
						{{-- email --}}
						@include('helpers.forms.fields.email', [
							'label'       => trans('auth.email'),
							'id'          => 'email',
							'name'        => 'email',
							'required'    => (getAuthField() == 'email'),
							'placeholder' => t('enter_your_email'),
							'value'       => null,
							'attributes'  => ['data-valid-type' => 'email'],
							'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
						])
						
						{{-- phone --}}
						@php
							$phoneCountryValue = config('country.code');
						@endphp
						@include('helpers.forms.fields.intl-tel-input', [
							'label'       => trans('auth.phone_number'),
							'id'          => 'phone',
							'name'        => 'phone',
							'required'    => (getAuthField() == 'phone'),
							'placeholder' => null,
							'value'       => null,
							'countryCode' => $phoneCountryValue,
							// 'baseClass' => ['wrapper' => 'mb-3 col-md-10'],
							'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
						])
						
						{{-- username --}}
						@php
							$usernameIsEnabled = !config('larapen.core.disable.username');
						@endphp
						@if ($usernameIsEnabled)
							@include('helpers.forms.fields.text', [
								'label'       => trans('auth.username'),
								'name'        => 'username',
								'placeholder' => t('enter_your_username'),
								'value'       => null,
								'baseClass'   => ['wrapper' => 'mb-3 col-md-10'],
							])
						@endif
						
						{{-- password --}}
						@include('helpers.forms.fields.password', [
							'label'          => trans('auth.password'),
							'name'           => 'password',
							'placeholder'    => trans('auth.password'),
							'required'       => true,
							'value'          => null,
							'togglePassword' => 'link',
							// 'baseClass'   => ['wrapper' => 'mb-3 col-md-10'],
						])
						
						{{-- password_confirmation --}}
						@include('helpers.forms.fields.password', [
							'label'          => trans('auth.confirm_password'),
							'name'           => 'password_confirmation',
							'placeholder'    => trans('auth.confirm_password'),
							'required'       => true,
							'value'          => null,
							'togglePassword' => 'link',
							'hint'           => '',
							// 'baseClass'   => ['wrapper' => 'mb-3 col-md-10'],
						])
						
						{{-- captcha --}}
						@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
						
						{{-- accept_terms --}}
						@include('helpers.forms.fields.checkbox', [
							'label'     => t('accept_terms_label', ['attributes' => getUrlPageByType('terms')]),
							'id'        => 'acceptTerms',
							'name'      => 'accept_terms',
							'required'  => false,
							'value'     => null,
							'baseClass' => ['wrapper' => 'mb-1 col-md-12'],
						])
						
						{{-- accept_marketing_offers --}}
						@include('helpers.forms.fields.checkbox', [
							'label'    => t('accept_marketing_offers_label'),
							'id'       => 'acceptMarketingOffers',
							'name'     => 'accept_marketing_offers',
							'required' => false,
							'value'    => null,
						])
						
						{{-- button --}}
						<div class="d-grid my-4">
							<button type="submit" id="signupBtn" class="btn btn-primary btn-lg">
								{{ trans('auth.sign_up') }}
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		
		<p class="text-center text-muted mb-0">
			{{ trans('auth.already_have_account') }} <a href="{{ urlGen()->signIn() }}">{{ trans('auth.sign_in') }}</a>
		</p>
	</div>
@endsection

@section('after_scripts')
@endsection
