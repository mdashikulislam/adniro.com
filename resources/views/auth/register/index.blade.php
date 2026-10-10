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

@section('auth_card_class', 'auth-card-wide')

@section('content')
	<div class="auth-head">
		<span class="auth-head-icon"><i class="fa-solid fa-user-plus"></i></span>
		<h2 class="auth-title">{{ t('register_title') }}</h2>
		<p class="auth-subtitle">{{ trans('auth.register_description') }}</p>
	</div>
	
	@include('auth.login.partials.social-buttons', ['dividerText' => t('or_register_with_email')])
	
	<form id="signupForm" action="{{ url()->current() }}" method="post" class="auth-form {{ unsavedFormGuard() }}">
		@csrf
		@honeypot
		
		<div class="row gx-3">
			<div class="col-12 auth-section-head">
				<span class="auth-section-num">1</span>
				<div>
					<h3 class="auth-section-title">{{ t('register_section_details') }}</h3>
					<p class="auth-section-text">{{ t('register_section_details_text') }}</p>
				</div>
			</div>
			
			{{-- name --}}
			@include('helpers.forms.fields.text', [
				'label'       => t('Name'),
				'name'        => 'name',
				'placeholder' => t('enter_your_name'),
				'required'    => true,
				'value'       => null,
				'prefix'      => '<i class="bi bi-person"></i>',
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
				'prefix'      => '<i class="bi bi-envelope"></i>',
				'attributes'  => ['data-valid-type' => 'email'],
				'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
			])
			
			{{-- phone --}}
			@php
				$phoneCountryValue = config('country.code');
				$phoneIsRequired = (getAuthField() == 'phone');
				$phoneLabel = trans('auth.phone_number');
				if (!$phoneIsRequired && !$usersCanChooseNotifyChannel) {
					$phoneLabel .= ' <span class="auth-optional">' . t('optional') . '</span>';
				}
			@endphp
			@include('helpers.forms.fields.intl-tel-input', [
				'label'       => $phoneLabel,
				'id'          => 'phone',
				'name'        => 'phone',
				'required'    => (getAuthField() == 'phone'),
				'placeholder' => null,
				'value'       => null,
				'countryCode' => $phoneCountryValue,
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
					'prefix'      => '<i class="bi bi-at"></i>',
				])
			@endif
			
			<div class="col-12 auth-section-head auth-section-head-next">
				<span class="auth-section-num">2</span>
				<div>
					<h3 class="auth-section-title">{{ t('register_section_security') }}</h3>
					<p class="auth-section-text">{{ t('register_section_security_text') }}</p>
				</div>
			</div>
			
			{{-- password --}}
			@include('helpers.forms.fields.password', [
				'label'          => trans('auth.password'),
				'name'           => 'password',
				'placeholder'    => trans('auth.password'),
				'required'       => true,
				'value'          => null,
				'prefix'         => '<i class="bi bi-lock"></i>',
				'togglePassword' => 'icon',
				'hint'           => '',
				'baseClass'      => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- password_confirmation --}}
			@include('helpers.forms.fields.password', [
				'label'          => trans('auth.confirm_password'),
				'name'           => 'password_confirmation',
				'placeholder'    => trans('auth.confirm_password'),
				'required'       => true,
				'value'          => null,
				'prefix'         => '<i class="bi bi-shield-lock"></i>',
				'togglePassword' => 'icon',
				'hint'           => '',
				'baseClass'      => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- password strength --}}
			@php
				$pwdMinLength = (int)config('settings.auth.password_min_length', 6);
				$pwdMaxLength = (int)config('settings.auth.password_max_length', 30);
			@endphp
			<div class="col-12">
				<div class="auth-pwd-meter" id="pwdMeter" data-min="{{ $pwdMinLength }}" data-max="{{ $pwdMaxLength }}">
					<div class="auth-pwd-meter-top">
						<div class="auth-pwd-bars" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
						<span class="auth-pwd-label"
						      data-empty="{{ t('pwd_strength') }}"
						      data-weak="{{ t('pwd_weak') }}"
						      data-fair="{{ t('pwd_fair') }}"
						      data-good="{{ t('pwd_good') }}"
						      data-strong="{{ t('pwd_strong') }}"
						>{{ t('pwd_strength') }}</span>
					</div>
					<ul class="auth-pwd-rules">
						<li data-rule="length"><i class="bi bi-check-circle-fill"></i>{{ trans('auth.password_tip_length', ['min' => $pwdMinLength, 'max' => $pwdMaxLength]) }}</li>
						<li data-rule="case"><i class="bi bi-check-circle-fill"></i>{{ t('pwd_rule_case') }}</li>
						<li data-rule="number"><i class="bi bi-check-circle-fill"></i>{{ t('pwd_rule_number') }}</li>
						<li data-rule="symbol"><i class="bi bi-check-circle-fill"></i>{{ t('pwd_rule_symbol') }}</li>
						<li data-rule="match"><i class="bi bi-check-circle-fill"></i>{{ t('pwd_rule_match') }}</li>
					</ul>
				</div>
			</div>
			
			{{-- captcha --}}
			<div class="auth-captcha col-12">
				@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
			</div>
			
			<div class="col-12 auth-consents">
				{{-- accept_terms --}}
				@include('helpers.forms.fields.checkbox', [
					'label'     => t('accept_terms_label', ['attributes' => getUrlPageByType('terms')]),
					'id'        => 'acceptTerms',
					'name'      => 'accept_terms',
					'required'  => false,
					'value'     => null,
					'baseClass' => ['wrapper' => 'mb-2'],
				])
				
				{{-- accept_marketing_offers --}}
				@include('helpers.forms.fields.checkbox', [
					'label'     => t('accept_marketing_offers_label'),
					'id'        => 'acceptMarketingOffers',
					'name'      => 'accept_marketing_offers',
					'required'  => false,
					'value'     => null,
					'baseClass' => ['wrapper' => 'mb-0'],
				])
			</div>
			
			{{-- button --}}
			<div class="col-12">
				<button type="submit" id="signupBtn" class="btn btn-primary auth-submit">
					{{ trans('auth.sign_up') }} <i class="fa-solid fa-arrow-right"></i>
				</button>
			</div>
		</div>
	</form>
	
	<div class="auth-alt">
		{{ trans('auth.already_have_account') }} <a href="{{ urlGen()->signIn() }}">{{ trans('auth.sign_in') }}</a>
	</div>
@endsection

@section('after_scripts')
	<script>
		(function () {
			const meter = document.getElementById('pwdMeter');
			const pwd = document.getElementById('password');
			const confirmPwd = document.getElementById('password_confirmation');
			if (!meter || !pwd) {
				return;
			}
			
			const min = parseInt(meter.dataset.min, 10) || 6;
			const max = parseInt(meter.dataset.max, 10) || 30;
			const label = meter.querySelector('.auth-pwd-label');
			const rules = {};
			meter.querySelectorAll('[data-rule]').forEach((el) => rules[el.dataset.rule] = el);
			const levels = ['empty', 'weak', 'fair', 'good', 'strong'];
			
			const update = () => {
				const value = pwd.value || '';
				const passed = {
					length: value.length >= min && value.length <= max,
					case: /[a-z]/.test(value) && /[A-Z]/.test(value),
					number: /\d/.test(value),
					symbol: /[^A-Za-z0-9]/.test(value),
					match: value.length > 0 && confirmPwd && confirmPwd.value === value,
				};
				Object.keys(rules).forEach((rule) => rules[rule].classList.toggle('is-met', !!passed[rule]));
				
				let score = 0;
				if (value.length > 0) {
					score = 1;
					if (passed.length) {
						score += ['case', 'number', 'symbol'].filter((rule) => passed[rule]).length;
						if (value.length >= min + 4) {
							score += 1;
						}
					}
					score = Math.min(score, 4);
				}
				meter.dataset.level = levels[score];
				label.textContent = label.dataset[levels[score]];
			};
			
			pwd.addEventListener('input', update);
			if (confirmPwd) {
				confirmPwd.addEventListener('input', update);
			}
			update();
		})();
	</script>
@endsection
