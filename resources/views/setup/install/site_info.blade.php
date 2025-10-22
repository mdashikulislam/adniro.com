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
@extends('setup.install.layouts.master')
@section('title', trans('messages.site_info_title'))

@php
	$defaultCountyCode ??= null;
	$siteInfo ??= [];
	$mailDrivers ??= [];
	$mailDriversSelectorsJson ??= '[]';
	
	// Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
	$formActionUrl ??= request()->fullUrl();
	$nextStepUrl ??= url('/');
	$nextStepLabel ??= trans('messages.next');
@endphp
@section('content')
	<form name="siteInfoForm" action="{{ $formActionUrl }}" method="POST" novalidate>
		@csrf
		
		<div class="row">
			<div class="mb-4 col-md-12">
				<h5 class="mb-0 fs-5 border-bottom pb-3">
					<i class="bi bi-globe"></i> {{ trans('messages.app_info') }}
				</h5>
			</div>
			
			{{-- settings[app][name] --}}
			@include('helpers.forms.fields.text', [
				'label'       => trans('messages.settings_app_name'),
				'name'        => 'settings[app][name]',
				'required'    => true,
				'value'       => data_get($siteInfo, 'settings.app.name'),
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- settings[app][slogan] --}}
			@include('helpers.forms.fields.text', [
				'label'       => trans('messages.settings_app_slogan'),
				'name'        => 'settings[app][slogan]',
				'required'    => true,
				'value'       => data_get($siteInfo, 'settings.app.slogan'),
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- settings[localization][default_country_code] --}}
			@include('helpers.forms.fields.select2', [
				'label'       => trans('messages.settings_localization_default_country_code'),
				'name'        => 'settings[localization][default_country_code]',
				'required'    => true,
				'options'     => getCountriesFromArray(),
				'value'       => data_get($siteInfo, 'settings.localization.default_country_code', $defaultCountyCode),
				'placeholder' => 'Select a country',
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- settings[app][purchase_code] --}}
			@include('helpers.forms.fields.text', [
				'label'       => trans('messages.settings_app_purchase_code'),
				'name'        => 'settings[app][purchase_code]',
				'required'    => true,
				'value'       => data_get($siteInfo, 'settings.app.purchase_code'),
				'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
				'hint'        => trans('admin.find_my_purchase_code', ['purchaseCodeFindingUrl' => config('larapen.core.purchaseCodeFindingUrl')]),
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			<div class="mb-4 col-md-12 mt-3">
				<h5 class="mb-0 fs-5 border-bottom pb-3">
					<i class="bi bi-person-circle"></i> {{ trans('messages.admin_info') }}
				</h5>
			</div>
			
			{{-- user[name] --}}
			@include('helpers.forms.fields.text', [
				'label'       => trans('messages.user_name'),
				'name'        => 'user[name]',
				'required'    => true,
				'value'       => data_get($siteInfo, 'user.name'),
				'hint'        => 'Enter the administrator name',
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
				'newline'     => true,
			])
			
			{{-- user[email] --}}
			@include('helpers.forms.fields.text', [
				'label'       => trans('messages.user_email'),
				'name'        => 'user[email]',
				'required'    => true,
				'value'       => data_get($siteInfo, 'user.email'),
				'hint'        => 'Enter the admin panel email',
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- user[password] --}}
			@include('helpers.forms.fields.text', [
				'label'       => trans('messages.user_password'),
				'name'        => 'user[password]',
				'required'    => true,
				'value'       => data_get($siteInfo, 'user.password'),
				'hint'        => 'Enter the admin panel password',
				'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
			])
			
			{{-- settings[mail][*] --}}
			@if (view()->exists('setup.install.site_info.mail_drivers'))
				<div class="col-12">
					<div class="row">
						@include('setup.install.site_info.mail_drivers')
					</div>
				</div>
			@endif
			
			{{-- button --}}
			<div class="col-md-12 text-end border-top pt-3 mt-3">
				<button type="submit" class="btn btn-primary" data-wait="{{ trans('messages.button_processing') }}">
					{!! $nextStepLabel !!} <i class="fa-solid fa-chevron-right position-right"></i>
				</button>
			</div>
		</div>
		
	</form>
@endsection
