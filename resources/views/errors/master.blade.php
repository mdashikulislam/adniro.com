@php
	// adniro: branded error page. It stays standalone (no front layout, no database queries), so it
	// still renders when the layout or the database is what failed.
	$htmlLang = getLangTag(config('app.locale'));
	$htmlDir = (config('lang.direction') == 'rtl') ? ' dir="rtl"' : '';
	$appName = (string)config('settings.app.name', config('app.name'));
	$logoFactoryUrl = config('larapen.media.logo-factory');
	$logoDarkUrl = config('settings.app.logo_dark_url', $logoFactoryUrl);
	$logoLightUrl = config('settings.app.logo_light_url', $logoDarkUrl);
	$isInstalling = isFromInstallOrUpgradeProcess();
	$showSearch = ($showSearch ?? false) && !$isInstalling;

	$searchUrl = null;
	$postUrl = null;
	if (!$isInstalling) {
		try {
			$searchUrl = urlGen()->searchWithoutQuery();
			$postUrl = urlGen()->addPost();
		} catch (\Throwable $e) {
			// The page must render even when the URL helpers cannot (e.g. no country yet)
		}
	}
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}"{!! $htmlDir !!}>
<head>
	<meta charset="{{ config('larapen.core.charset', 'utf-8') }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<meta name="googlebot" content="noindex">
	<title>@yield('title') - {{ $appName }}</title>

	@if (!empty(config('settings.app.favicon_url')))
		<link rel="icon" href="{{ config('settings.app.favicon_url') }}">
	@endif
	@if (file_exists(public_path('manifest.json')))
		<link rel="manifest" href="{{ url()->asset('manifest.json') }}">
	@endif

	{{-- App CSS files (Handled by Mix) --}}
	@if (config('lang.direction') == 'rtl')
		<link href="https://fonts.googleapis.com/css?family=Cairo|Changa" rel="stylesheet">
		<link href="{{ url(mix('dist/front/styles.rtl.css')) }}" rel="stylesheet">
	@else
		<link href="{{ url(mix('dist/front/styles.css')) }}" rel="stylesheet">
	@endif
	@unless ($isInstalling)
		{{-- Brand colours (separate requests: the page still renders if they fail) --}}
		<link href="{{ url('common/css/style.css') . getPictureVersion() }}" rel="stylesheet">
		@if (file_exists(public_path('dist/front/custom.css')))
			<link href="{{ url()->asset('dist/front/custom.css') }}" rel="stylesheet">
		@endif
	@endunless
	<style>
		.error-logo { max-height: 44px; width: auto; }
		.error-status { font-size: clamp(4rem, 14vw, 7rem); line-height: 1; letter-spacing: -.04em; }
	</style>
</head>
<body class="bg-body text-body-emphasis d-flex flex-column min-vh-100">

<header class="border-bottom bg-body-tertiary">
	<div class="container d-flex align-items-center justify-content-between py-3">
		<a href="{{ url('/') }}" aria-label="{{ $appName }}">
			<picture>
				<source srcset="{{ $logoLightUrl }}" media="(prefers-color-scheme: dark)">
				<img src="{{ $logoDarkUrl }}" alt="{{ $appName }}" class="error-logo">
			</picture>
		</a>
		@if (!empty($postUrl))
			<a href="{{ $postUrl }}" class="btn btn-primary btn-sm">
				<i class="bi bi-plus-lg me-1"></i>{{ t('create_new_listing') }}
			</a>
		@endif
	</div>
</header>

<main class="flex-grow-1 d-flex align-items-center py-5">
	<div class="container text-center">
		<div class="row justify-content-center">
			<div class="col-md-9 col-lg-7">
				<div class="vstack gap-3">
					<div class="error-status fw-bold text-primary" aria-hidden="true">@yield('status')</div>
					<h1 class="mb-0 fs-3 fw-bold">@yield('title')</h1>
					<p class="text-secondary mb-1">@yield('message')</p>

					@if ($showSearch && !empty($searchUrl))
						<form action="{{ $searchUrl }}" method="GET" role="search" class="mx-auto w-100" style="max-width: 32rem">
							<div class="input-group input-group-lg">
								<label for="errorSearch" class="visually-hidden">{{ t('search') }}</label>
								<input id="errorSearch" type="search" name="q" class="form-control" placeholder="{{ t('what') }}">
								<button type="submit" class="btn btn-primary">
									<i class="bi bi-search"></i><span class="visually-hidden">{{ t('search') }}</span>
								</button>
							</div>
						</form>
					@endif

					<div class="d-flex flex-wrap justify-content-center gap-2">
						@if ($isInstalling)
							@php
								$isPost = in_array(request()->method(), ['POST', 'PUT']);
							@endphp
							<a href="{{ $isPost ? url()->previous() : url()->full() }}" class="btn btn-primary">
								<i class="bi {{ $isPost ? 'bi-arrow-left' : 'bi-arrow-clockwise' }}"></i> {{ $isPost ? t('go_back') : 'Reload' }}
							</a>
						@else
							<a href="{{ url('/') }}" class="btn btn-outline-primary">
								<i class="bi bi-house-door me-1"></i>{{ t('go_home') }}
							</a>
							<a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
								<i class="bi bi-arrow-left me-1"></i>{{ t('go_back') }}
							</a>
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>
</main>

<footer class="border-top py-3">
	<div class="container small text-secondary d-flex flex-wrap justify-content-between gap-2">
		<span>&copy; {{ date('Y') }} {{ $appName }}</span>
		@unless ($isInstalling)
			<a href="{{ url('contact') }}" class="link-secondary">{{ t('Contact') }}</a>
		@endunless
	</div>
</footer>

{{-- No scripts: the site bundle expects the full layout's globals and only raised errors here --}}
</body>
</html>
