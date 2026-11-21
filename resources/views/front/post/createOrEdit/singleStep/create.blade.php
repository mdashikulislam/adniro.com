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
	
	$listingForm = \App\Services\ListingForm\ListingFormState::forCreate([], auth()->user()); // adniro:ai
@endphp

@section('content')
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">
				
				@include('front.post.partials.notification')
				
				<div class="col-md-9">
					<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 mb-sm-3">
						<h1 class="h3 fw-bold border-bottom pb-3 mb-4">
							<i class="fa-regular fa-pen-to-square"></i> {{ t('create_new_listing') }}
						</h1>
						
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
									
									{{-- adniro:ai - the fields live in the shared listing form --}}
									@include('front.post.createOrEdit.form.body', [
										'listingForm'  => $listingForm,
										'isSingleStep' => true,
										'actions'      => ['backUrl' => url()->previous(), 'backLabel' => e(t('Cancel')), 'submitId' => 'payableFormSubmitButton', 'submitLabel' => e(t('submit'))],
									])
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
