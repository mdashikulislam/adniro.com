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
@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">
				<div class="col-md-3">
					@include('front.account.partials.sidebar')
				</div>
				
				<div class="col-md-9">
					@if (isset($errors) && $errors->any())
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
							<h5 class="fw-bold text-danger-emphasis mb-3">
								{{ t('validation_errors_title') }}
							</h5>
							<ul class="mb-0 list-unstyled">
								@foreach ($errors->all() as $error)
									<li class="lh-lg"><i class="bi bi-check-lg me-1"></i>{!! $error !!}</li>
								@endforeach
							</ul>
						</div>
					@endif
					
					{{-- Photo upload fileinput messages handlers --}}
					<div id="avatarUploadError" class="center-block" style="width:100%; display:none"></div>
					<div id="avatarUploadSuccess" class="alert alert-success fade show" style="display:none;"></div>
					
					@include('front.account.partials.header', [
						'headerTitle' => '<i class="bi bi-person-circle"></i> ' . trans('auth.profile')
					])
					
					<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2">
						<div class="row gy-3">
							@include('front.account.partials.profile-photo')
							@include('front.account.partials.profile-details')
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
@endsection
