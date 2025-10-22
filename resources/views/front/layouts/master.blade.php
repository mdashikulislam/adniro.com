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
	$htmlLang = getLangTag(config('app.locale'));
	$langDirection = config('lang.direction');
	$userThemePreference = currentUserThemePreference();
	
	$htmlDir = ($langDirection == 'rtl') ? ' dir="rtl"' : '';
	$htmlTheme = ($userThemePreference == 'dark') ? ' data-bs-theme="dark"' : '';
	$showIconOnly = true;
	
	$helpers = getViewHelpersNames(snakeCase: true);
	$plugins = array_keys((array)config('plugins'));
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}"{!! $htmlDir . $htmlTheme !!}>
<head>
	<meta charset="{{ config('larapen.core.charset', 'utf-8') }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	@include('front.common.meta-robots')
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" href="{{ config('settings.app.favicon_url') }}">
	<title>{!! MetaTag::get('title') !!}</title>
	{!! MetaTag::tag('description') !!}{!! MetaTag::tag('keywords') !!}
	<link rel="canonical" href="{{ request()->fullUrl() }}"/>
	{{-- Specify a default target for all hyperlinks and forms on the page --}}
	<base target="_top"/>
	@if (isset($post))
		@if (isVerifiedPost($post))
			@if (config('services.facebook.client_id'))
				<meta property="fb:app_id" content="{{ config('services.facebook.client_id') }}" />
			@endif
			{!! $og->renderTags() !!}
			{!! MetaTag::twitterCard() !!}
		@endif
	@else
		@if (config('services.facebook.client_id'))
			<meta property="fb:app_id" content="{{ config('services.facebook.client_id') }}" />
		@endif
		{!! $og->renderTags() !!}
		{!! MetaTag::twitterCard() !!}
	@endif
	@include('feed::links')
	{!! seoSiteVerification() !!}
	
	@if (file_exists(public_path('manifest.json')))
		<link rel="manifest" href="{{ url()->asset('manifest.json') }}">
	@endif
	
    @yield('before_styles')
	
	{{-- App CSS files (Handled by Mix) --}}
	@if ($langDirection == 'rtl')
		<link href="https://fonts.googleapis.com/css?family=Cairo|Changa" rel="stylesheet">
		<link href="{{ url(mix('dist/front/styles.rtl.css')) }}" rel="stylesheet">
	@else
		<link href="{{ url(mix('dist/front/styles.css')) }}" rel="stylesheet">
	@endif
	
	{{-- AdsBlocker Plugin CSS --}}
	@if (config('plugins.detectadsblocker.installed'))
		<link href="{{ url('plugins/detectadsblocker/assets/css/style.css') . getPictureVersion() }}" rel="stylesheet">
	@endif
	
	{{-- Generated CSS from Settings (Handled by FileController) --}}
	@php
		$skinQs = request()->filled('skin') ? '?skin=' . request()->query('skin') : null;
		if (request()->filled('display')) {
			$skinQs .= !empty($skinQs) ? '&' : '?';
			$skinQs .= 'display=' . request()->query('display');
		}
		$styleCssUrl = url('common/css/style.css') . $skinQs . getPictureVersion(!empty($skinQs));
	@endphp
	<link href="{{ $styleCssUrl }}" rel="stylesheet">
	
	{{-- Generated CSS from Home Section --}}
	@php
		$homeStyle = '';
		if (isset($searchFormOptions) && is_array($searchFormOptions)) {
			$homeStyle = view('front.common.css.homepage', ['searchFormOptions', $searchFormOptions])->render();
		}
	@endphp
	{!! $homeStyle !!}
	
	{{-- Custom CSS --}}
	<link href="{{ url()->asset('dist/front/custom.css') . getPictureVersion() }}" rel="stylesheet">
	
    @yield('after_styles')
	@stack('after_styles_stack')
	
	@stack('before_helpers_styles_stack')
	
	@if (!empty($helpers))
		@foreach($helpers as $helper)
			@stack($helper . '_styles')
		@endforeach
	@endif
	
	@stack('after_helpers_styles_stack')
	
	@if (!empty($plugins))
		@foreach($plugins as $plugin)
			@yield($plugin . '_styles')
		@endforeach
	@endif
    
    @if (config('settings.style.custom_css'))
		{!! printCss(config('settings.style.custom_css')) . "\n" !!}
    @endif
	
	@if (config('settings.other.js_code'))
		{!! printJs(config('settings.other.js_code')) . "\n" !!}
	@endif
	
	@include('front.common.js.document')
 
	<script>
		paceOptions = {
			elements: true
		};
	</script>
	<script src="{{ url()->asset('assets/plugins/pace-js/1.2.4/pace.min.js') }}"></script>
	<link href="{{ url()->asset('assets/plugins/pace-js/1.2.4/pace-theme-default.min.css') }}" rel="stylesheet">
	
	@if (!empty($helpers))
		@foreach($helpers as $helper)
			@stack($helper . '_head_scripts')
		@endforeach
	@endif
</head>
<body class="bg-body text-body-emphasis skin">
@section('header')
	@include('front.layouts.partials.header')
@show

<main>
	@section('search')
	@show
	
	@section('wizard')
	@show
	
	@include('helpers.flash.default')
	
	@yield('content')
	
	@section('info')
	@show
	
	@include('front.layouts.partials.advertising.auto')
	
	@section('modal_location')
	@show
	@section('modal_languages')
	@show
	@section('modal_abuse')
	@show
	@section('modal_message')
	@show
	
	@include('front.layouts.partials.modal.countries')
	@include('front.layouts.partials.modal.error')
	@include('cookie-consent::index')
	
	@if (config('plugins.detectadsblocker.installed'))
		@if (view()->exists('detectadsblocker::modal'))
			@include('detectadsblocker::modal')
		@endif
	@endif
</main>

@section('footer')
	@include('front.layouts.partials.footer')
@show

@include('front.common.js.init')

<script>
	var countryCode = '{{ config('country.code', 0)  }}';
	var timerNewMessagesChecking = {{ (int)config('settings.other.timer_new_messages_checking', 0)  }};
	
	{{-- Theme Preference (light/dark/system) --}}
	var isSettingsAppDarkModeEnabled = {{ isSettingsAppDarkModeEnabled() ? 'true' : 'false' }};
	var isSettingsAppSystemThemeEnabled = {{ isSettingsAppSystemThemeEnabled() ? 'true' : 'false' }};
	var userThemePreference = {!! !empty($userThemePreference) ? "'$userThemePreference'" : 'null' !!};
	var showIconOnly = {{ $showIconOnly ? 'true' : 'false' }};
	
	{{-- The app's default auth field --}}
	var defaultAuthField = '{{ old('auth_field', getAuthField()) }}';
	var phoneCountry = '{{ config('country.code') }}';
	
	{{-- Others global variables --}}
	var fakeLocationsResults = "{{ config('settings.listings_list.fake_locations_results', 0) }}";
</script>

@stack('before_scripts_stack')
@yield('before_scripts')

{{-- Toggle Password Visibility --}}
@if (view()->exists('auth.layouts.js.translations'))
	@include('auth.layouts.js.translations')
@endif

{{-- App JS files (Handled by Mix) --}}
<script src="{{ url(mix('dist/front/scripts.js')) }}"></script>

{{-- Lazy Loading JS --}}
@if (config('settings.optimization.lazy_loading_activation') == 1)
	<script src="{{ url('assets/plugins/lazysizes/lazysizes.min.js') }}" async=""></script>
@endif

{{-- AdsBlocker Plugin JS --}}
@if (config('plugins.detectadsblocker.installed'))
	<script src="{{ url('plugins/detectadsblocker/assets/js/script.js') . getPictureVersion() }}"></script>
@endif

<script>
	onDocumentReady((event) => {
		{{-- Social Media Share --}}
		SocialShare.init({width: 640, height: 480});
		
		{{-- Modal Login --}}
		@if (isset($errors) && $errors->any())
			@if ($errors->any() && old('quickLoginForm')=='1')
				{{-- Re-open the modal if error occured --}}
				openLoginModal();
			@endif
		@endif
	});
</script>

@yield('after_scripts')
@stack('after_scripts_stack')

@stack('before_helpers_scripts_stack')

@if (!empty($helpers))
	@foreach($helpers as $helper)
		@stack($helper . '_scripts')
	@endforeach
@endif

@stack('after_helpers_scripts_stack')

@if (!empty($plugins))
	@foreach($plugins as $plugin)
		@yield($plugin . '_scripts')
	@endforeach
@endif

@if (config('settings.footer.tracking_code'))
	{!! printJs(config('settings.footer.tracking_code')) . "\n" !!}
@endif
</body>
</html>
