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
@section('title', trans('messages.finish_title'))

@php
	$itemName = config('larapen.core.item.name');
	$itemTitle = config('larapen.core.item.title');
	$itemUrl = config('larapen.core.item.url');
	$itemLinkLabel = str($itemUrl)->remove('https://')->rtrim('/')->toString();
	
	$loginUrl = urlGen()->signIn();
	$homePageUrl = url('/');
	
	$supportUrl = 'https://support.laraclassifier.com/';
	
	$messages = [
		trans('messages.finish_env_file_hint'),
		trans('messages.finish_site_hint', ['loginUrl' => $loginUrl, 'homePageUrl' => $homePageUrl]),
		trans('messages.finish_help_hint', ['supportUrl' => $supportUrl]),
	];
@endphp
@section('content')
	<div class="row">
		<div class="mb-5 col-md-12">
			<h5 class="mb-0 fs-5 border-bottom pb-3 text-success">
				<i class="fa-regular fa-circle-check"></i> {!! trans('messages.finish_success', ['itemName' => $itemName, 'itemTitle' => $itemTitle]) !!}
			</h5>
		</div>
		
		<div class="mb-5 col-md-12">
			<div class="d-flex flex-column gap-2 ps-4">
				@foreach($messages as $message)
					<div class="d-flex">
						<i class="bi bi-check-lg pe-2"></i>
						<div>{!! $message !!}</div>
					</div>
				@endforeach
			</div>
		</div>
		
		<div class="col-md-12">
			{!! trans('messages.finish_thanks', ['itemName' => $itemName, 'itemUrl' => $itemUrl, 'itemLinkLabel' => $itemLinkLabel]) !!}
		</div>
	</div>
@endsection

@section('after_scripts')
@endsection
