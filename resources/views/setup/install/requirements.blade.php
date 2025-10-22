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
@section('title', trans('messages.requirements_checking_title'))

@php
	$checkComponents ??= false;
	$components ??= [];
	
	$checkPermissions ??= false;
	$permissions ??= [];
	
	// Get steps URLs & labels
	$previousStepUrl ??= null;
	$previousStepLabel ??= null;
	$formActionUrl ??= request()->fullUrl();
	$nextStepUrl ??= url('/');
	$nextStepLabel ??= trans('messages.next');
@endphp
@section('content')
	<div class="row">
		@if (!$checkComponents)
			<div class="mb-4 col-md-12">
				<h5 class="mb-0 fs-5 border-bottom pb-3">
					<i class="fa-solid fa-list"></i> {{ trans('messages.requirements') }}
				</h5>
			</div>
			
			<div class="mb-3 col-md-12">
				<ul class="mb-0 list-unstyled text-nowrap overflow-x-auto installation">
					@foreach ($components as $key => $item)
						@continue($item['isOk'])
						<li>
							<h6 class="fs-6 fw-bold">
								@if ($item['isOk'])
									<i class="bi bi-check-lg text-success fs-5"></i>
								@else
									<i class="bi bi-x-lg text-danger fs-5"></i>
								@endif
								{{ $item['name'] }}
							</h6>
							<p class="ms-4">
								{!! ($item['isOk']) ? $item['success'] : $item['warning'] !!}
							</p>
						</li>
					@endforeach
				</ul>
			</div>
		@endif
		
		<div class="mb-4 col-md-12">
			<h5 class="mb-0 fs-5 border-bottom pb-3">
				<i class="fa-regular fa-folder"></i> {{ trans('messages.permissions') }}
			</h5>
		</div>
		
		<div class="mb-3 col-md-12">
			<ul class="mb-0 list-unstyled text-nowrap overflow-x-auto installation">
				@foreach ($permissions as $key => $item)
					<li>
						<h6 class="fs-6 fw-bold">
							@if ($item['isOk'])
								<i class="bi bi-check-lg text-success fs-5"></i>
							@else
								<i class="bi bi-x-lg text-danger fs-5"></i>
							@endif
							{{ $item['name'] }}
						</h6>
						<p class="ms-4">
							{!! ($item['isOk']) ? $item['success'] : $item['warning'] !!}
						</p>
					</li>
				@endforeach
			</ul>
		</div>
		
		<div class="col-md-12 text-end border-top pt-3">
			@if ($checkComponents && $checkPermissions)
				<a href="{{ $nextStepUrl }}" class="btn btn-primary">
					{!! $nextStepLabel !!} <i class="fa-solid fa-chevron-right position-right"></i>
				</a>
			@else
				<a href="{{ $formActionUrl }}" class="btn btn-primary">
					<i class="fa-solid fa-rotate-right position-right"></i> {!! trans('messages.try_again') !!}
				</a>
			@endif
		</div>
	</div>
@endsection

@section('after_scripts')
@endsection
