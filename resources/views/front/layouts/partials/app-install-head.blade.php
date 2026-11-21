{{--
	adniro: the installable app in the page head: iPhone app tags, and a short startup screen (the app icon on
	white, like Android's) on the first page of a launch of the installed app. It goes as soon as the page is
	parsed; the timer is only a safety net, never a display time.
--}}
@php
	$appName = (string)config('settings.app.name', config('app.name'));
	$splashIcon = !empty(config('settings.app.favicon')) ? url('icon-512.png') : null;
@endphp
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $appName }}">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
@if (!empty($splashIcon))
	<style>
		html.adn-splash::before { content: ""; position: fixed; inset: 0; z-index: 2147483000; background: #fff url({{ $splashIcon }}) 50% 45% / 104px 104px no-repeat; transition: opacity .2s ease-out; }
		html.adn-splash.adn-splash-out::before { opacity: 0; pointer-events: none; }
		@media (prefers-reduced-motion: reduce) { html.adn-splash::before { transition: none; } }
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
					setTimeout(function () { root.classList.remove('adn-splash', 'adn-splash-out'); }, 260);
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
