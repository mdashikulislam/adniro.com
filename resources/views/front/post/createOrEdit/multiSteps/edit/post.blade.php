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
@extends('front.layouts.master')

@section('wizard')
	@include('front.post.createOrEdit.multiSteps.partials.wizard')
@endsection

@php
	$post ??= [];
	
	$postTypes ??= [];
	$countries ??= [];
	
	$postCatParentId = data_get($post, 'category.parent_id');
	$postCatParentId = (empty($postCatParentId)) ? data_get($post, 'category.id', 0) : $postCatParentId;
	
	// Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
	$formActionUrl ??= request()->fullUrl();
	$nextStepUrl ??= '/';
	$nextStepLabel ??= t('submit') . '  <i class="bi bi-chevron-right"></i>';
@endphp

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				@include('front.post.partials.notification')
				
				<div class="col-md-9">
					<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 mb-sm-3">
						<h3 class="fw-bold border-bottom pb-3 mb-4">
							<i class="fa-solid fa-pen-to-square"></i> {{ t('update_my_listing') }}
							-&nbsp;<a href="{{ urlGen()->post($post) }}"
							          class="{{ linkClass() }}"
							          data-bs-placement="top"
							          data-bs-toggle="tooltip"
							          title="{!! data_get($post, 'title') !!}"
							>{!! str(data_get($post, 'title'))->limit(45) !!}</a>
						</h3>
						
						<div class="row d-flex justify-content-center">
							<div class="col-md-10 col-sm-12 col-xs-12">
								
								<form id="payableForm"
								      action="{{ $formActionUrl }}"
								      method="POST"
								      enctype="multipart/form-data"
								      class="{{ unsavedFormGuard() }}"
								>
									@csrf
									@method('PUT')
									<input type="hidden" name="post_id" value="{{ data_get($post, 'id') }}">
									
									<div class="row">
										
										{{-- category_id --}}
										@php
											$categoryIdError = (isset($errors) && $errors->has('category_id')) ? ' is-invalid' : '';
											$catSelectionUrl = url('browsing/categories/select');
											
											$categoryId = old('category_id', data_get($post, 'category.id'));
											$categoryType = old('category_type', data_get($post, 'category.type'));
											
											$aModal = 'data-bs-toggle="modal"';
											$aHref = 'href="#browseCategories"';
											$aDataUrl = 'data-selection-url="' . $catSelectionUrl . '"';
											$aClass = 'class="modal-cat-link open-selection-url ' . linkClass() . '"';
											
											$customHtml = '<div id="catsContainer" class="form-control' . $categoryIdError . '">';
											$customHtml .= "<a {$aHref} {$aModal} {$aDataUrl} {$aClass}>";
											$customHtml .= t('select_a_category');
											$customHtml .= '</a>';
											$customHtml .= '</div>';
											$customHtml .= '<input type="hidden" name="category_id" id="categoryId" value="' . $categoryId . '">';
											$customHtml .= '<input type="hidden" name="category_type" id="categoryType" value="' . $categoryType . '">';
										@endphp
										@include('helpers.forms.fields.html', [
											'label'    => t('category'),
											'name'     => 'category_id', // <label for="name">
											'required' => true,
											'value'    => $customHtml,
										])
										
										{{-- post_type_id --}}
										@if (config('settings.listing_form.show_listing_type'))
											@include('helpers.forms.fields.radio', [
												'label'           => t('type'),
												'id'              => 'postTypeId-',
												'name'            => 'post_type_id',
												'inline'          => true,
												'required'        => true,
												'options'         => $postTypes,
												'optionValueName' => 'id',
												'optionTextName'  => 'label',
												'value'           => data_get($post, 'post_type_id'),
												'hint'            => t('post_type_hint'),
												'wrapper'         => ['id' => 'postTypeBloc'],
											])
										@endif
										
										{{-- title --}}
										@include('helpers.forms.fields.text', [
											'label'       => t('title'),
											'name'        => 'title',
											'placeholder' => t('enter_your_title'),
											'required'    => true,
											'value'       => data_get($post, 'title'),
											'hint'        => t('a_great_title_needs_at_least_60_characters'),
										])
										
										{{-- description --}}
										@include('helpers.forms.fields.wysiwyg', [
											'label'       => t('Description'),
											'name'        => 'description',
											'placeholder' => t('enter_your_message'),
											'required'    => true,
											'value'       => data_get($post, 'description'),
											'height'      => 350,
											'attributes'  => ['rows' => 15],
											'hint'        => t('describe_what_makes_your_listing_unique'),
										])
										
										{{-- cfContainer --}}
										<div id="cfContainer"></div>
										
										{{-- price --}}
										@php
											$currencySymbol = config('currency.symbol', 'X');
											$price = old('price', data_get($post, 'price'));
											$price = \App\Helpers\Common\Num::format($price, 2, '.', '');
											$isPriceMandatory = (config('settings.listing_form.price_mandatory') == '1');
											$priceHint = !$isPriceMandatory ? t('price_hint') : null;
											
											// negotiable
											$negotiable = old('negotiable', data_get($post, 'negotiable'));
											$negotiableChecked = ($negotiable == '1') ? ' checked' : '';
											
											$suffix = '<input id="negotiable" name="negotiable" type="checkbox" value="1"' . $negotiableChecked . '>';
											$suffix .= '&nbsp;<small>' . t('negotiable') . '</small>';
										@endphp
										@include('helpers.forms.fields.number', [
											'label'       => t('price'),
											'name'        => 'price',
											'required'    => $isPriceMandatory,
											'placeholder' => t('enter_your_price'),
											'value'       => $price,
											'step'        => getInputNumberStep((int)config('currency.decimal_places', 2)),
											'prefix'      => $currencySymbol,
											'suffix'      => $suffix,
											'hint'        => $priceHint,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
											'wrapper'     => ['id' => 'priceBloc'],
										])
										
										{{-- country_code --}}
										<input id="countryCode"
										       name="country_code"
										       type="hidden"
										       value="{{ data_get($post, 'country_code') ?? config('country.code') }}"
										>
										
										@php
											$adminType = config('country.admin_type', 0);
										@endphp
										@if (config('settings.listing_form.city_selection') == 'select')
											@if (in_array($adminType, ['1', '2']))
												{{-- admin_code --}}
												@include('helpers.forms.fields.select2', [
													'label'        => t('location'),
													'id'           => 'adminCode',
													'name'         => 'admin_code',
													'required'     => true,
													'placeholder'  => t('select_your_location'),
													'options'      => [],
													'largeOptions' => true,
													'hint'         => null,
													'baseClass'    => ['wrapper' => 'mb-3 col-md-8'],
													'wrapper'      => ['id' => 'locationBox'],
												])
											@endif
										@else
											@php
												$adminType = (in_array($adminType, ['0', '1', '2'])) ? $adminType : 0;
												$relAdminType = (in_array($adminType, ['1', '2'])) ? $adminType : 1;
												$adminCode = data_get($post, 'city.subadmin' . $relAdminType . '_code', 0);
												$adminCode = data_get($post, 'city.subAdmin' . $relAdminType . '.code', $adminCode);
												$adminName = data_get($post, 'city.subAdmin' . $relAdminType . '.name');
												$cityId = data_get($post, 'city.id', 0);
												$cityName = data_get($post, 'city.name');
												$fullCityName = !empty($adminName) ? $cityName . ', ' . $adminName : $cityName;
											@endphp
											<input type="hidden"
											       id="selectedAdminType"
											       name="selected_admin_type"
											       value="{{ old('selected_admin_type', $adminType) }}"
											>
											<input type="hidden"
											       id="selectedAdminCode"
											       name="selected_admin_code"
											       value="{{ old('selected_admin_code', $adminCode) }}"
											>
											<input type="hidden"
											       id="selectedCityId"
											       name="selected_city_id"
											       value="{{ old('selected_city_id', $cityId) }}"
											>
											<input type="hidden"
											       id="selectedCityName"
											       name="selected_city_name"
											       value="{{ old('selected_city_name', $fullCityName) }}"
											>
										@endif
										
										{{-- city_id --}}
										@include('helpers.forms.fields.select2', [
											'label'        => t('city'),
											'id'           => 'cityId',
											'name'         => 'city_id',
											'required'     => true,
											'placeholder'  => t('select_a_city'),
											'options'      => [],
											'largeOptions' => true,
											'hint'         => null,
											'baseClass'    => ['wrapper' => 'mb-3 col-md-8'],
											'wrapper'      => ['id' => 'cityBox'],
										])
										
										{{-- tags --}}
										@php
											$tagHint = t('tags_hint', ['limit' => '{limit}', 'min' => '{min}', 'max' => '{max}']);
										@endphp
										@include('helpers.forms.fields.select2-tagging', [
											'label'       => t('Tags'),
											'id'          => 'tags',
											'name'        => 'tags',
											'placeholder' => t('enter_tags'),
											'options'     => data_get($post, 'tags'),
											'hint'        => $tagHint,
										])
										
										{{-- is_permanent --}}
										@if (config('settings.listing_form.permanent_listings_enabled') == '3')
											<input id="isPermanent"
											       name="is_permanent"
											       type="hidden"
											       value="{{ old('is_permanent', data_get($post, 'is_permanent')) }}"
											>
										@else
											@include('helpers.forms.fields.checkbox', [
												'label'    => t('is_permanent_label'),
												'id'       => 'isPermanent',
												'name'     => 'is_permanent',
												'switch'   => true,
												'required' => false,
												'value'    => data_get($post, 'is_permanent'),
												'hint'     => t('is_permanent_hint'),
												'wrapper'  => ['id' => 'isPermanentBox', 'class' => 'hide']
											])
										@endif
										
										
										<div class="my-4 col-md-12">
											<h5 class="w-100 mb-0 fw-bold fs-5 border rounded p-2">
												<i class="bi bi-person-circle"></i> {{ t('seller_information') }}
											</h5>
										</div>
										
										
										{{-- contact_name --}}
										@include('helpers.forms.fields.text', [
											'label'       => t('your_name'),
											'id'          => 'contactName',
											'name'        => 'contact_name',
											'placeholder' => t('enter_your_name'),
											'required'    => true,
											'value'       => data_get($post, 'contact_name'),
											'prefix'      => '<i class="fa-regular fa-user"></i>',
											'suffix'      => null,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
										])
										
										{{-- auth_field (as notification channel) --}}
										@php
											$authFields = getAuthFields(true);
											$authFieldOptions = collect($authFields)
												->map(fn($item, $key) => ['value' => $key, 'text' => $item])
												->toArray();
											
											$usersCanChooseNotifyChannel = isUsersCanChooseNotifyChannel();
											$authFieldValue = data_get($post, 'auth_field') ?? getAuthField();
											$authFieldValue = $usersCanChooseNotifyChannel ? old('auth_field', $authFieldValue) : $authFieldValue;
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
												'options'    => $authFieldOptions,
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
											'value'       => data_get($post, 'email'),
											'prefix'      => '<i class="fa-regular fa-envelope"></i>',
											'suffix'      => null,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
											'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
										])
										
										{{-- phone --}}
										@php
											$phoneValue = data_get($post, 'phone');
											$phoneCountryValue = data_get($post, 'phone_country') ?? config('country.code');
											
											// phone_hidden
											$phoneHiddenValue = old('phone_hidden', data_get($post, 'phone_hidden'));
											$phoneHiddenChecked = ($phoneHiddenValue == '1') ? ' checked' : '';
											$itiSuffix = '<input id="phoneHidden" name="phone_hidden" type="checkbox" value="1"' . $phoneHiddenChecked . '>';
											$itiSuffix .= '&nbsp;<small>' . t('Hide') . '</small>';
										@endphp
										@include('helpers.forms.fields.intl-tel-input', [
											'label'       => trans('auth.phone_number'),
											'id'          => 'phone',
											'name'        => 'phone',
											'required'    => (getAuthField() == 'phone'),
											'placeholder' => null,
											'value'       => $phoneValue,
											'countryCode' => $phoneCountryValue,
											'suffix'      => $itiSuffix,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
											'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
										])
										
										{{-- buttons --}}
										<div class="col-12 mb-3 mt-5">
											<div class="row">
												<div class="col-md-6 mb-md-0 mb-2 text-start d-grid">
													<a href="{{ $previousStepUrl }}" class="btn btn-secondary btn-lg">
														{!! $previousStepLabel !!}
													</a>
												</div>
												<div class="col-md-6 mb-md-0 mb-2 text-end d-grid">
													<button id="nextStepBtn" class="btn btn-primary btn-lg">
														{!! $nextStepLabel !!}
													</button>
												</div>
											</div>
										</div>
									
									</div>
								</form>
							
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-md-3 reg-sidebar">
					@include('front.post.createOrEdit.partials.right-sidebar')
				</div>
			
			</div>
		</div>
	</div>
	@include('front.post.createOrEdit.partials.category-modal')
@endsection

@section('after_scripts')
	<script>
		defaultAuthField = '{{ old('auth_field', $authFieldValue ?? getAuthField()) }}';
		phoneCountry = '{{ old('phone_country', ($phoneCountryValue ?? '')) }}';
	</script>
@endsection

@include('front.post.createOrEdit.partials.form-assets')
