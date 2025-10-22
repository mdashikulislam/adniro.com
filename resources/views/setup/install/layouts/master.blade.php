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
@php
	$helpers = getViewHelpersNames(snakeCase: true);
	$version = config('version.app');
	$majorVersion = is_string($version) ? explode('.', $version)[0] : date('Y');
	$version = !empty($version) ? '<span class="ms-3 fs-6 text-secondary">v' . $majorVersion . '</span>' : null;
@endphp
<!DOCTYPE html>
<html lang="{{ getLangTag(config('app.locale', 'en')) }}">
<head>
	<meta charset="{{ config('larapen.core.charset', 'utf-8') }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex,nofollow"/>
	<meta name="googlebot" content="noindex">
	<title>@yield('title')</title>
	
	@yield('before_styles')
	
	<link href="{{ url(mix('dist/front/styles.css')) }}" rel="stylesheet">
	
	@yield('after_styles')
	@stack('after_styles_stack')
	
	@if (!empty($helpers))
		@foreach($helpers as $helper)
			@stack($helper . '_styles')
		@endforeach
	@endif
	
	@include('front.common.js.document')
	
	<script>
		paceOptions = {
			elements: true
		};
	</script>
	<script src="{{ url()->asset('assets/plugins/pace-js/0.4.17/pace.min.js') }}"></script>
</head>
<body class="bg-body text-body-emphasis">
@section('header')
	@include('setup.install.layouts.partials.header')
@show

<main>
	<div class="container mt-5 mb-3">
		<div class="row d-flex justify-content-center">
			<div class="col-12 px-4">
				@include('helpers.titles.title-1', [
					'title'        => trans('messages.installer'),
					'titleClass'   => 'mb-0 me-3 fs-2', // text-body-tertiary
					'rightContent' => $version
				])
				@include('setup.install.layouts.partials._steps')
			</div>
		</div>
	</div>
	
	<div class="container">
		<div class="row d-flex justify-content-center">
			<div class="col-xl-10 col-12">
				@php
					$hasFormErrors = (isset($errors) && $errors->any());
				@endphp
				
				@if ($hasFormErrors)
					<div class="row">
						<div class="col-12">
							<div class="alert alert-danger">
								<h5 class="fs-5 fw-bold">
									{{ t('validation_errors_title') }}
								</h5>
								<ul class="mb-0 list-unstyled">
									@foreach ($errors->all() as $error)
										<li class="lh-lg"><i class="bi bi-check-lg me-1"></i>{!! $error !!}</li>
									@endforeach
								</ul>
							</div>
						</div>
					</div>
				@endif
				
				@include('helpers.flash.default')
				
				@php
					$mtClass = $hasFormErrors ? ' mt-2' : ' mt-4';
				@endphp
				
				<div class="card border-1 bg-body-tertiary mt-4">
					<div class="card-body">
						@yield('content')
					</div>
				</div>
			</div>
		</div>
	</div>
	
</main>

@section('footer')
	@include('setup.install.layouts.partials.footer')
@show

@yield('before_scripts')

<script>
	/* Init. vars */
	var siteUrl = '{{ url('/') }}';
	var languageCode = '{{ config('app.locale') }}';
	var countryCode = '{{ config('country.code', 0) }}';
	
	/* Init. Translation vars */
	var langLayout = {
		'hideMaxListItems': {
			'moreText': "{{ t('View More') }}",
			'lessText': "{{ t('View Less') }}"
		}
	};
</script>

<script src="{{ url(mix('dist/front/scripts.js')) }}"></script>

@yield('after_scripts')
@stack('after_scripts_stack')

@if (!empty($helpers))
	@foreach($helpers as $helper)
		@stack($helper . '_scripts')
	@endforeach
@endif
</body>
</html>
