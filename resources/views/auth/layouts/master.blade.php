@php
	$htmlLang = getLangTag(config('app.locale'));
	$langDirection = config('lang.direction');
	$userThemePreference = currentUserThemePreference();
	
	$htmlDir = ($langDirection == 'rtl') ? ' dir="rtl"' : '';
	$htmlTheme = ($userThemePreference == 'dark') ? ' theme="dark"' : '';
	$showIconOnly = false;
	
	$helpers = getViewHelpersNames(snakeCase: true);
	
	// Logo
	$logoFactoryUrl = config('larapen.media.logo-factory');
	$logoUrl = '';
	try {
        if (is_link(public_path('storage'))) {
			$logoDarkUrl = config('settings.app.logo_dark_url', $logoFactoryUrl);
			$logoLightUrl = config('settings.app.logo_light_url', $logoFactoryUrl);
			$logoUrl = $logoLightUrl;
		}
    } catch (\Throwable $e) {}
    $logoUrl = !empty($logoUrl) ? $logoUrl : $logoFactoryUrl;
	$logoWidth = (int)config('settings.upload.img_resize_logo_width', 200);
	$logoHeight = (int)config('settings.upload.img_resize_logo_height', 45);
	$logoWidth = \Illuminate\Support\Number::clamp($logoWidth, min: 150, max: 250);
	$logoHeight = \Illuminate\Support\Number::clamp($logoWidth, min: 40, max: 60);
	$logoCssSize = "max-width:{$logoWidth}px; max-height:{$logoHeight}px; width:auto; height:auto;";
    $appName = config('app.name', 'SiteName');
    $logoLabel = config('settings.app.name', $appName);
	$logoAlt = strtolower($logoLabel);
	
	// Hero Background Image
	$heroBgStyle = '';
    try {
        if (is_link(public_path('storage'))) {
            $bgImgUrl = config('settings.auth.hero_image_url');
            $heroBgStyle = 'background-image:url(' . $bgImgUrl . ');';
        }
    } catch (\Throwable $e) {}
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}"{!! $htmlDir . $htmlTheme !!} data-bs-theme="dark">
<head>
	<meta charset="{{ config('larapen.core.charset', 'utf-8') }}"/>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
	<link href="{{ config('settings.app.favicon_url') }}" rel="icon"/>
	<title>{!! MetaTag::get('title') !!}</title>
	{!! MetaTag::tag('description') !!}{!! MetaTag::tag('keywords') !!}
	<link rel="canonical" href="{{ request()->fullUrl() }}"/>
	
	{{-- Specify a default target for all hyperlinks and forms on the page --}}
	<base target="_top"/>
	
	@yield('before_styles')
	
	{{-- Auth Module's CSS files (Handled by Mix) --}}
	@if ($langDirection == 'rtl')
		<link href="https://fonts.googleapis.com/css?family=Cairo|Changa" rel="stylesheet">
		<link href="{{ url(mix('dist/auth/styles.rtl.css')) }}" rel="stylesheet">
	@else
		<link href="{{ url(mix('dist/auth/styles.css')) }}" rel="stylesheet">
	@endif
	
	{{-- Generated CSS from Settings (Handled by FileController) --}}
	@php
		$skinQs = request()->filled('skin') ? '?skin=' . request()->query('skin') : null;
		$styleCssUrl = url('auth/common/css/skin.css') . $skinQs . getPictureVersion(!empty($skinQs));
	@endphp
	<link href="{{ $styleCssUrl }}" rel="stylesheet">
	
	{{-- The site's theme colors (generated from the front settings) & the auth pages' design --}}
	@php
		$frontStyleCssUrl = url('common/css/style.css') . $skinQs . getPictureVersion(!empty($skinQs));
		$authCustomCssPath = public_path('dist/auth/custom.css');
		$authCustomCssVersion = file_exists($authCustomCssPath) ? '?v=' . filemtime($authCustomCssPath) : '';
	@endphp
	<link href="{{ $frontStyleCssUrl }}" rel="stylesheet">
	<link href="{{ url()->asset('dist/auth/custom.css') . $authCustomCssVersion }}" rel="stylesheet">
	
	@yield('after_styles')
	@stack('after_styles_stack')
	
	@if (!empty($helpers))
		@foreach($helpers as $helper)
			@stack($helper . '_styles')
		@endforeach
	@endif
	
	@include('front.common.js.document')
	
	@if (!empty($helpers))
		@foreach($helpers as $helper)
			@stack($helper . '_head_scripts')
		@endforeach
	@endif
</head>
<body>

{{-- Preloader --}}
{{--
<div class="preloader">
	<div class="lds-ellipsis">
		<div></div>
		<div></div>
		<div></div>
		<div></div>
	</div>
</div>
--}}

<div id="main-wrapper" class="auth-login-register auth-shell">
	
	{{-- Brand panel --}}
	<aside class="auth-brand d-none d-lg-flex">
		<div class="auth-brand-bg" style="{!! $heroBgStyle !!}"></div>
		<div class="auth-brand-overlay"></div>
		
		<div class="auth-brand-inner">
			<a class="auth-brand-logo" href="{{ url('/') }}" title="{!! $logoLabel !!}">
				<img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" style="{!! $logoCssSize !!}">
			</a>
			
			<div class="auth-brand-content">
				@php
					$defaultCoverTitle = trans('auth.default_cover_title', ['appName' => config('app.name')]);
					$defaultCoverDescription = trans('auth.default_cover_description');
				@endphp
				<span class="auth-brand-eyebrow"><i class="fa-solid fa-shield-halved"></i> {{ t('auth_brand_eyebrow') }}</span>
				<h1 class="auth-brand-title">{!! $coverTitle ?? $defaultCoverTitle !!}</h1>
				<p class="auth-brand-text">{!! $coverDescription ?? $defaultCoverDescription !!}</p>
				
				<ul class="auth-brand-features">
					<li>
						<span class="auth-brand-feature-icon"><i class="fa-solid fa-bullhorn"></i></span>
						<span>{{ t('auth_feature_post') }}</span>
					</li>
					<li>
						<span class="auth-brand-feature-icon"><i class="fa-regular fa-comments"></i></span>
						<span>{{ t('auth_feature_chat') }}</span>
					</li>
					<li>
						<span class="auth-brand-feature-icon"><i class="fa-regular fa-heart"></i></span>
						<span>{{ t('auth_feature_save') }}</span>
					</li>
				</ul>
			</div>
			
			<div class="auth-brand-footer">
				&copy; {{ date('Y') }} {{ $logoLabel }}
			</div>
		</div>
	</aside>
	
	{{-- Form panel --}}
	<main class="auth-main">
		<div class="auth-topbar">
			<a class="auth-topbar-logo d-lg-none" href="{{ url('/') }}" title="{!! $logoLabel !!}">
				<img src="{{ $logoUrl }}" alt="{{ $logoAlt }}">
			</a>
			<a class="auth-topbar-home" href="{{ url('/') }}">
				<i class="fa-solid fa-arrow-left"></i> {{ trans('auth.back_to_home') }}
			</a>
		</div>
		
		<div class="auth-main-body">
			<div class="auth-card @yield('auth_card_class')">
				@php
					$hasNotifications = (
						(isset($errors) && $errors->any())
						|| session()->has('flash_messages')
						|| session()->has('resendEmailVerificationData')
						|| session()->has('resendPhoneVerificationData')
						|| session()->has('status')
						|| session()->has('email')
						|| session()->has('phone')
						|| session()->has('login')
						|| session()->has('code')
					);
				@endphp
				
				<div class="auth-notifications">
					@if (isset($errors) && $errors->any())
						<div class="alert alert-danger">
							@if (request()->segment(2) == 'register')
								<h5 class="fw-bold text-danger-emphasis mb-2">
									{{ trans('auth.validation_errors_title') }}
								</h5>
							@endif
							<ul class="mb-0 list-unstyled">
								@foreach ($errors->all() as $error)
									<li class="lh-lg"><i class="bi bi-exclamation-circle me-1"></i>{!! $error !!}</li>
								@endforeach
							</ul>
						</div>
					@endif
					
					@include('helpers.flash.default')
					
					@yield('notifications')
				</div>
				
				@yield('content')
			</div>
		</div>
		
		<div class="auth-main-footer">
			@include('auth.layouts.partials.select-language')
		</div>
	</main>

</div>

@section('modal')
@show
@include('front.layouts.partials.modal.countries', ['modalSize' => 'modal-xl'])

@include('front.common.js.init')

<script>
	var countryCode = '{{ config('country.code', 0)  }}';
	
	{{-- Theme Preference (light/dark/system) --}}
	var isSettingsAppDarkModeEnabled = {{ isSettingsAppDarkModeEnabled() ? 'true' : 'false' }};
	var isSettingsAppSystemThemeEnabled = {{ isSettingsAppSystemThemeEnabled() ? 'true' : 'false' }};
	var userThemePreference = {!! !empty($userThemePreference) ? "'$userThemePreference'" : 'null' !!};
	var showIconOnly = {{ $showIconOnly ? 'true' : 'false' }};
	
	{{-- The app's default auth field --}}
	var defaultAuthField = '{{ old('auth_field', getAuthField()) }}';
	var phoneCountry = '{{ config('country.code') }}';
</script>

@yield('before_scripts')

{{-- Toggle Password Visibility --}}
@include('auth.layouts.js.translations')

{{-- App JS files (Handled by Mix) --}}
<script src="{{ url(mix('dist/auth/scripts.js')) }}"></script>

@yield('after_scripts')
@stack('after_scripts_stack')

@if (!empty($helpers))
	@foreach($helpers as $helper)
		@stack($helper . '_scripts')
	@endforeach
@endif
</body>
</html>
