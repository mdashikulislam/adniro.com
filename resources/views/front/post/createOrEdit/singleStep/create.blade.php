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

@php
	$postTypes ??= [];
	$countries ??= [];
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
							<i class="fa-regular fa-pen-to-square"></i> {{ t('create_new_listing') }}
						</h3>
						
						<div class="row d-flex justify-content-center">
							<div class="col-md-10 col-sm-12 col-xs-12">
								
								<form id="payableForm"
								      action="{{ request()->fullUrl() }}"
								      method="POST"
								      enctype="multipart/form-data"
								      class="{{ unsavedFormGuard() }}"
								>
									@csrf
									@honeypot
									
									<div class="row">
										
										{{-- category_id --}}
										@php
											$categoryIdError = (isset($errors) && $errors->has('category_id')) ? ' is-invalid' : '';
											$catSelectionUrl = url('browsing/categories/select');
											
											$categoryId = old('category_id', 0);
											$categoryType = old('category_type');
											
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
												'value'           => null,
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
											'value'       => null,
											'hint'        => t('a_great_title_needs_at_least_60_characters'),
										])
										
										{{-- description --}}
										@include('helpers.forms.fields.wysiwyg', [
											'label'       => t('Description'),
											'name'        => 'description',
											'placeholder' => t('enter_your_message'),
											'required'    => true,
											'value'       => null,
											'height'      => 350,
											'attributes'  => ['rows' => 15],
											'hint'        => t('describe_what_makes_your_listing_unique'),
										])
										
										@if (isset($picturesLimit) && is_numeric($picturesLimit) && $picturesLimit > 0)
											{{-- pictures --}}
											@php
												$pictureMultipleSelectionsAllowed = (
													config('settings.listing_form.one_picture_field_for_multiple_selections') == '1'
												);
												$pictures ??= [];
												$picturesRequired = (config('settings.listing_form.picture_mandatory') == '1');
												
												$savedPictures = collect($pictures)
													->map(function ($item) {
														return [
															'key'  => $item['id'] ?? null,
															'path' => $item['file_path'] ?? null,
															'url'  => $item['url']['medium'] ?? null,
														];
													})->toArray();
												
												$picturesHint = t('add_up_to_x_pictures_text', ['pictures_number' => $picturesLimit]);
												$picturesHint .= ' ' . t('file_types', ['file_types' => getAllowedFileFormatsHint('image')]);
											@endphp
											@if ($pictureMultipleSelectionsAllowed)
												@include('helpers.forms.fields.fileinput', [
													'label'       => t('pictures'),
													'name'        => 'pictures',
													'required'    => $picturesRequired,
													'attributes'  => ['accept' => 'image/*'],
													'value'       => $savedPictures,
													'hint'        => $picturesHint,
													'allowsMultiple'   => true,
													'limit'            => $picturesLimit,
													'pluginOptions'    => [
														'previewFileType'   => 'image',
														'showPreview'       => 'true',
														'dropZoneEnabled'   => 'true',
														'browseOnZoneClick' => 'true',
														'showCaption'       => 'false',
														'uploadUrl'         => '/',
													],
												])
											@else
												@include('helpers.forms.fields.fileinput-multiple', [
													'label'       => t('pictures'),
													'name'        => 'pictures',
													'placeholder' => t('Picture X', ['number' => '{index}']),
													'required'    => $picturesRequired,
													'attributes'  => ['accept' => 'image/*'],
													'value'       => $savedPictures,
													'hint'        => $picturesHint,
													'limit'       => $picturesLimit,
													'pluginOptions'    => [
														'previewFileType' => 'image',
														'showPreview'     => 'true',
													],
												])
											@endif
										@endif
										
										{{-- cfContainer --}}
										<div id="cfContainer"></div>
										
										{{-- price --}}
										@php
											$currencySymbol = config('currency.symbol', 'X');
											$price = old('price');
											$price = \App\Helpers\Common\Num::format($price, 2, '.', '');
											$isPriceMandatory = (config('settings.listing_form.price_mandatory') == '1');
											$priceHint = !$isPriceMandatory ? t('price_hint') : null;
											
											// negotiable
											$negotiable = old('negotiable');
											$negotiableChecked = ($negotiable == '1') ? ' checked' : '';
											
											$priceSuffix = '<input id="negotiable" name="negotiable" type="checkbox" value="1"' . $negotiableChecked . '>';
											$priceSuffix .= '&nbsp;<small>' . t('negotiable') . '</small>';
										@endphp
										@include('helpers.forms.fields.number', [
											'label'       => t('price'),
											'name'        => 'price',
											'required'    => $isPriceMandatory,
											'placeholder' => t('enter_your_price'),
											'value'       => $price,
											'step'        => getInputNumberStep((int)config('currency.decimal_places', 2)),
											'prefix'      => $currencySymbol,
											'suffix'      => $priceSuffix,
											'hint'        => $priceHint,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
											'wrapper'     => ['id' => 'priceBloc'],
										])
										
										{{-- country_code --}}
										@php
											$countryCodeOptions = collect($countries)
												->map(function($item) {
													return [
														'value'      => $item['code'] ?? null,
														'text'       => $item['name'] ?? null,
														'attributes' => ['data-admin-type' => $item['admin_type'] ?? 0],
													];
												})->toArray();
											
											$selectedCountryCode = !empty(config('ipCountry.code')) ? config('ipCountry.code') : 0;
										@endphp
										@if (empty(config('country.code')))
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
											<input type="hidden"
											       id="selectedAdminType"
											       name="selected_admin_type"
											       value="{{ old('selected_admin_type', $adminType) }}"
											>
											<input type="hidden"
											       id="selectedAdminCode"
											       name="selected_admin_code"
											       value="{{ old('selected_admin_code', 0) }}"
											>
											<input type="hidden"
											       id="selectedCityId"
											       name="selected_city_id"
											       value="{{ old('selected_city_id', 0) }}"
											>
											<input type="hidden"
											       id="selectedCityName"
											       name="selected_city_name"
											       value="{{ old('selected_city_name') }}"
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
											'options'     => [],
											'hint'        => $tagHint,
										])
										
										{{-- is_permanent --}}
										@if (config('settings.listing_form.permanent_listings_enabled') == '3')
											<input type="hidden" name="is_permanent" id="isPermanent" value="0">
										@else
											@include('helpers.forms.fields.checkbox', [
												'label'    => t('is_permanent_label'),
												'id'       => 'isPermanent',
												'name'     => 'is_permanent',
												'switch'   => true,
												'required' => false,
												'value'    => null,
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
										@if (auth()->check())
											<input id="contactName" name="contact_name" type="hidden" value="{{ auth()->user()->name }}">
										@else
											@include('helpers.forms.fields.text', [
												'label'       => t('your_name'),
												'id'          => 'contactName',
												'name'        => 'contact_name',
												'placeholder' => t('enter_your_name'),
												'required'    => true,
												'value'       => null,
												'prefix'      => '<i class="fa-regular fa-user"></i>',
												'suffix'      => null,
												'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
											])
										@endif
										
										{{-- auth_field (as notification channel) --}}
										@php
											$authFields = getAuthFields(true);
											$authFieldOptions = collect($authFields)
												->map(fn($item, $key) => ['value' => $key, 'text' => $item])
												->toArray();
											
											$usersCanChooseNotifyChannel = isUsersCanChooseNotifyChannel();
											$authFieldValue = getAuthField();
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
										@php
											$emailValue = (auth()->check() && isset(auth()->user()->email)) ? auth()->user()->email : '';
										@endphp
										@include('helpers.forms.fields.email', [
											'label'       => trans('auth.email'),
											'id'          => 'email',
											'name'        => 'email',
											'required'    => (getAuthField() == 'email'),
											'placeholder' => t('enter_your_email'),
											'value'       => $emailValue,
											'prefix'      => '<i class="fa-regular fa-envelope"></i>',
											'suffix'      => null,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-8'],
											'wrapper'     => ['class' => "auth-field-item{$forceToDisplay}"],
										])
										
										{{-- phone --}}
										@php
											$phoneValue = null;
											$phoneCountryValue = config('country.code');
											if (
												auth()->check()
												&& isset(auth()->user()->country_code)
												&& !empty(auth()->user()->phone)
												&& isset(auth()->user()->phone_country)
												// && auth()->user()->country_code == config('country.code')
											) {
												$phoneValue = auth()->user()->phone;
												$phoneCountryValue = auth()->user()->phone_country;
											}
											
											// phone_hidden
											$phoneHiddenChecked = (old('phone_hidden') == '1') ? ' checked' : '';
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
										
										@if (!auth()->check())
											@if (in_array(config('settings.listing_form.auto_registration'), [1, 2]))
												{{-- auto_registration --}}
												@if (config('settings.listing_form.auto_registration') == 1)
													@include('helpers.forms.fields.checkbox', [
														'label'    => t('I want to register by submitting this listing'),
														'name'     => 'auto_registration',
														'required' => false,
														'value'    => 1,
														'hint'     => t('You will receive your authentication information by email'),
													])
												@else
													<input type="hidden" name="auto_registration" id="auto_registration" value="1">
												@endif
											@endif
										@endif
										
										@include('front.post.createOrEdit.singleStep.partials.packages')
										
										{{-- captcha --}}
										@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
										
										@if (!auth()->check())
											{{-- accept_terms --}}
											@include('helpers.forms.fields.checkbox', [
												'label'     => t('accept_terms_label', ['attributes' => getUrlPageByType('terms')]),
												'id'        => 'acceptTerms',
												'name'      => 'accept_terms',
												'required'  => true,
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
										@endif
										
										{{-- buttons --}}
										<div class="col-12 mb-3 mt-5">
											<div class="row">
												<div class="col-md-6 mb-md-0 mb-2 text-start d-grid">
													<a href="{{ url()->previous() }}" class="btn btn-secondary btn-lg">
														{{ t('Cancel') }}
													</a>
												</div>
												<div class="col-md-6 mb-md-0 mb-2 text-end d-grid">
													<button id="payableFormSubmitButton" class="btn btn-primary btn-lg">
														{{ t('submit') }}
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
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection

@include('front.post.createOrEdit.partials.form-assets')
