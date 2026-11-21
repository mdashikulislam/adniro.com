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
	{{-- adniro:ai - the page and its fields live in the shared listing form --}}
	@include('front.post.createOrEdit.form.page', [
		'listingForm'  => $listingForm,
		'isSingleStep' => true,
		'formAction'   => request()->fullUrl(),
		'actions'      => ['backUrl' => url()->previous(), 'backLabel' => e(t('Cancel')), 'submitId' => 'payableFormSubmitButton', 'submitLabel' => e(t('submit'))],
	])
@endsection

@include('front.post.createOrEdit.partials.form-assets')
