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
	$authUser ??= auth()->user();
@endphp
@section('search')
	@parent
	@include('front.pages.contact.intro')
@endsection

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row clearfix">
				
				@if (isset($errors) && $errors->any())
					<div class="col-12">
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<h5><strong>{{ t('validation_errors_title') }}</strong></h5>
							<ul>
								@foreach ($errors->all() as $error)
									<li>{!! $error !!}</li>
								@endforeach
							</ul>
						</div>
					</div>
				@endif
				
				<div class="col-md-12">
					<div class="container rounded bg-body-tertiary p-4 p-lg-3 p-md-2">
						<h3 class="fw-bold border-bottom border-light-subtle pb-3 mb-4">
							{{ t('Contact Us') }}
						</h3>
						<div class="row d-flex justify-content-center">
							<div class="col-md-12">
								<form action="{{ urlGen()->contact() }}" method="post" class="{{ unsavedFormGuard() }} needs-validation">
									@csrf
									@honeypot
									
									<div class="row">
										{{-- name --}}
										@include('helpers.forms.fields.text', [
											'label'       => t('Name'),
											'name'        => 'name',
											'placeholder' => t('enter_your_name'),
											'required'    => true,
											'value'       => $authUser->name ?? null,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
										])
										
										{{-- company_name --}}
										@include('helpers.forms.fields.text', [
											'label'       => t('company_name'),
											'name'        => 'company_name',
											'placeholder' => t('company_name'),
											'value'       => null,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
										])
										
										{{-- email --}}
										@include('helpers.forms.fields.email', [
											'label'       => trans('auth.email'),
											'id'          => 'contactEmail',
											'name'        => 'email',
											'placeholder' => trans('auth.email_address'),
											'required'    => true,
											'value'       => $authUser->email ?? null,
											'attributes'  => ['data-valid-type' => 'email'],
											'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
										])
										
										{{-- phone --}}
										@include('helpers.forms.fields.tel', [
											'label'       => trans('auth.phone_number'),
											'name'        => 'phone',
											'placeholder' => trans('auth.phone_number'),
											'required'    => true,
											'value'       => $authUser->phone ?? null,
											'baseClass'   => ['wrapper' => 'mb-3 col-md-6'],
										])
										
										{{-- message --}}
										@include('helpers.forms.fields.textarea', [
											'label'         => t('Message'),
											'name'          => 'message',
											'placeholder'   => t('enter_your_message'),
											'required'      => true,
											'value'         => null,
											'attributes'    => ['rows' => 7],
											'pluginOptions' => ['height' => 200]
										])
										
										{{-- captcha --}}
										@include('helpers.forms.fields.captcha', ['label' => trans('auth.captcha_human_verification')])
										
										{{-- button --}}
										<div class="col-12 mt-4">
											<div class="row">
												<div class="col-md-6 text-start">
													<button type="submit" class="btn btn-primary btn-lg btn-block">
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
			</div>
		</div>
	</div>
	
	@includeWhen(!auth()->check(), 'auth.login.partials.modal')
@endsection

@section('after_scripts')
	<script>
		onDocumentReady((event) => {
			formValidate("form", formValidateOptions);
		});
	</script>
@endsection
