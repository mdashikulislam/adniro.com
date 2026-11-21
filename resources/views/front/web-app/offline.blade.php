{{--
	adniro: the installable app's offline page (WebAppController::offline). Shown by the service worker without a
	network, so everything it needs is in this file: no stylesheets, scripts, fonts or images to download.
--}}
@php
	$langDirection = (config('lang.direction') == 'rtl') ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $langDirection }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="robots" content="noindex">
	<meta name="theme-color" content="{{ $themeColor }}">
	<title>{{ trans('app_install.offline_title') }} · {{ $siteName }}</title>
	<style>
		:root { --brand: {{ $themeColor }}; color-scheme: light dark; }
		* { box-sizing: border-box; }
		body {
			display: grid; place-items: center; min-height: 100vh; min-height: 100dvh; margin: 0;
			padding: 24px 20px calc(24px + env(safe-area-inset-bottom));
			font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial, sans-serif;
			color: #1f2937; background: #f6f8f9; text-align: center;
		}
		main { width: 100%; max-width: 360px; }
		.mark {
			display: inline-grid; place-items: center; width: 84px; height: 84px; margin-bottom: 22px; border-radius: 26px;
			color: var(--brand); background: color-mix(in srgb, var(--brand) 12%, #fff);
		}
		.mark svg { width: 42px; height: 42px; }
		.site { margin: 0 0 6px; font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--brand); }
		h1 { margin: 0 0 10px; font-size: 1.45rem; line-height: 1.25; text-wrap: balance; }
		p { margin: 0 0 26px; font-size: .95rem; line-height: 1.55; color: #4b5563; text-wrap: pretty; }
		button {
			display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 50px;
			padding: 0 22px; border: 0; border-radius: 999px; font: inherit; font-weight: 700; color: #fff;
			background: var(--brand); box-shadow: 0 8px 18px -8px color-mix(in srgb, var(--brand) 70%, transparent);
			cursor: pointer; transition: transform .15s ease;
		}
		button:active { transform: scale(.97); }
		button:focus-visible { outline: 3px solid color-mix(in srgb, var(--brand) 40%, transparent); outline-offset: 3px; }
		@media (prefers-color-scheme: dark) {
			body { color: #e5e7eb; background: #111418; }
			.mark { background: color-mix(in srgb, var(--brand) 22%, #111418); }
			p { color: #9ca3af; }
		}
		@media (prefers-reduced-motion: reduce) { button { transition: none; } }
	</style>
</head>
<body>
	<main>
		<span class="mark" aria-hidden="true">
			{{-- Wi-Fi with a slash --}}
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M2 8.8a15 15 0 0 1 4.2-2.6M10.3 5.1A15 15 0 0 1 22 8.8M5 12.5a10 10 0 0 1 3.4-2M15.8 10.6A10 10 0 0 1 19 12.5M8.5 16.1a5 5 0 0 1 7 0M12 20h.01M3 3l18 18"/>
			</svg>
		</span>
		<p class="site">{{ $siteName }}</p>
		<h1>{{ trans('app_install.offline_title') }}</h1>
		<p>{{ trans('app_install.offline_text') }}</p>
		<button type="button" onclick="location.reload()">{{ trans('app_install.offline_retry') }}</button>
	</main>
	<script>
		// Back online: reload the page the visitor wanted
		window.addEventListener('online', function () { location.reload(); });
	</script>
</body>
</html>
