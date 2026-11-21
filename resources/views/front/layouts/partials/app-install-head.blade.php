{{--
	adniro: the installable app in the page head: iPhone app tags, and the start screen of the installed app.
	The start screen shows the site logo on white with a soft teal glow and a slim loading ring, on the first page
	of a launch only. It fades out as soon as the page is parsed; the timer is only a safety net, never a display time.
--}}
@php
	$appName = (string)config('settings.app.name', config('app.name'));
	$splashLogo = (string)(config('settings.app.logo_dark_url') ?: config('larapen.media.logo-factory')); // as the header
	$splashBrand = \App\Http\Controllers\Web\Front\SiteIconController::themeColor();
@endphp
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $appName }}">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
@if ($splashLogo !== '')
	<style>
		html.adn-splash { --adn-splash-brand: {{ $splashBrand }}; overflow: hidden; }
		html.adn-splash::before { content: ""; position: fixed; inset: 0; z-index: 2147483000; background: url({{ $splashLogo }}) 50% 44% / min(58vw, 240px) auto no-repeat, radial-gradient(circle at 50% 44%, color-mix(in srgb, var(--adn-splash-brand) 14%, transparent), transparent 58%), #fff; transition: opacity .3s ease-out; }
		html.adn-splash::after { content: ""; position: fixed; left: 50%; bottom: calc(14% + env(safe-area-inset-bottom, 0px)); z-index: 2147483001; width: 30px; height: 30px; margin-left: -15px; border: 3px solid color-mix(in srgb, var(--adn-splash-brand) 18%, transparent); border-top-color: var(--adn-splash-brand); border-radius: 50%; animation: adnSplashSpin .8s linear infinite; transition: opacity .2s ease-out; }
		html.adn-splash.adn-splash-out::before, html.adn-splash.adn-splash-out::after { opacity: 0; pointer-events: none; }
		html.adn-splash.adn-splash-out { overflow: auto; }
		@keyframes adnSplashSpin { to { transform: rotate(360deg); } }
		@media (prefers-reduced-motion: reduce) { html.adn-splash::before, html.adn-splash::after { transition: none; animation: none; } }
	</style>
	<script>
		(function () {
			try {
				var installed = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
				if (!installed || sessionStorage.getItem('adn_splash')) {
					return;
				}
				sessionStorage.setItem('adn_splash', '1');
				var root = document.documentElement;
				var done = false;
				var hide = function () {
					if (done) {
						return;
					}
					done = true;
					root.classList.add('adn-splash-out');
					setTimeout(function () { root.classList.remove('adn-splash', 'adn-splash-out'); }, 350);
				};
				root.classList.add('adn-splash');
				document.addEventListener('DOMContentLoaded', hide);
				setTimeout(hide, 3000);
			} catch (e) {
				// never block the page
			}
		})();
	</script>
@endif
