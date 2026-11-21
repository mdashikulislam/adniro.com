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
	
	$listingForm = \App\Services\ListingForm\ListingFormState::fromPost($post); // adniro:ai
@endphp

@section('content')
	{{-- adniro:ai - the page and its fields live in the shared listing form --}}
	@include('front.post.createOrEdit.form.page', [
		'listingForm'  => $listingForm,
		'isSingleStep' => false,
		'formAction'   => $formActionUrl,
		'actions'      => ['backUrl' => $previousStepUrl, 'backLabel' => $previousStepLabel, 'submitId' => 'nextStepBtn', 'submitLabel' => $nextStepLabel],
	])
@endsection

@section('after_scripts')
	<script>
		defaultAuthField = '{{ old('auth_field', $listingForm->authField) }}'; {{-- adniro:ai --}}
		phoneCountry = '{{ old('phone_country', ($listingForm->phoneCountry ?? '')) }}'; {{-- adniro:ai --}}
	</script>
@endsection

@include('front.post.createOrEdit.partials.form-assets')
