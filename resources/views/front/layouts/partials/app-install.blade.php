{{--
	adniro: "Get the app" on phones (public/assets/js/app/app-install.js, public/assets/css/app-install.css).
	The card and the step-by-step guide stay hidden; the script, loaded on phones and in the installed app only,
	registers the service worker (public/sw.js) and decides what to show:
	Android: the browser's own install prompt; iPhone: the "Add to Home Screen" steps; Samsung Internet: open in Chrome.
--}}
@php
	$appName = (string)config('settings.app.name', config('app.name'));
	$appIcon = !empty(config('settings.app.favicon')) ? url('icon-192.png') : config('settings.app.logo_dark_url');
	$appHost = request()->getHost();
	$swUrl = url('sw.js') . '?v=' . @filemtime(public_path('sw.js'));
	$installJs = url('assets/js/app/app-install.js') . '?v=' . @filemtime(public_path('assets/js/app/app-install.js'));
	$guideSteps = [
		'android'    => ['steps' => ['android_1', 'android_2', 'android_3'], 'note' => 'android_note', 'icon' => 'bi-android2'],
		'safari'     => ['steps' => ['safari_1', 'safari_2', 'safari_3', 'safari_4'], 'note' => null, 'icon' => 'bi-apple'],
		'ios-chrome' => ['steps' => ['ios_chrome_1', 'ios_chrome_2', 'ios_chrome_3'], 'note' => 'ios_chrome_note', 'icon' => 'bi-apple'],
	];
	$guideTabs = ['android' => 'tab_android', 'safari' => 'tab_safari', 'ios-chrome' => 'tab_ios_chrome'];
@endphp

{{-- The card, above the app bar --}}
<section class="adn-install d-md-none" id="adnInstall" hidden aria-labelledby="adnInstallTitle"
         data-sw="{{ $swUrl }}"
         data-label-install="{{ trans('app_install.install') }}"
         data-label-home="{{ trans('app_install.add_home') }}"
         data-label-chrome="{{ trans('app_install.open_chrome') }}"
         data-text-chrome="{{ trans('app_install.chrome_text', ['name' => $appName]) }}">
	<img class="adn-install-icon" src="{{ $appIcon }}" alt="" width="48" height="48">
	<div class="adn-install-text">
		<strong id="adnInstallTitle">{{ trans('app_install.title', ['name' => $appName]) }}</strong>
		<span data-adn-install-text>{{ trans('app_install.card_text', ['name' => $appName]) }}</span>
	</div>
	<button type="button" class="adn-install-close" data-adn-install-dismiss aria-label="{{ trans('app_install.not_now') }}">
		<i class="bi bi-x-lg" aria-hidden="true"></i>
	</button>
	<button type="button" class="btn btn-primary adn-install-go" data-adn-install>
		<i class="bi bi-download" aria-hidden="true"></i>
		<span data-adn-install-label>{{ trans('app_install.install') }}</span>
	</button>
</section>

{{-- The step-by-step guide (iPhone, and browsers without an install prompt) --}}
<div class="adn-install-sheet" id="adnInstallSheet" hidden role="dialog" aria-modal="true" aria-labelledby="adnInstallSheetTitle">
	<div class="adn-install-sheet-backdrop" data-adn-sheet-close></div>
	<div class="adn-install-sheet-panel">
		<div class="adn-install-sheet-head">
			<img class="adn-install-icon" src="{{ $appIcon }}" alt="" width="52" height="52">
			<div>
				<h2 id="adnInstallSheetTitle">{{ trans('app_install.title', ['name' => $appName]) }}</h2>
				<p>{{ trans('app_install.sheet_text', ['name' => $appName]) }}</p>
			</div>
			<button type="button" class="adn-install-close" data-adn-sheet-close aria-label="{{ trans('app_install.close') }}">
				<i class="bi bi-x-lg" aria-hidden="true"></i>
			</button>
		</div>
		<div class="adn-install-tabs" role="tablist">
			@foreach ($guideTabs as $tab => $label)
				<button type="button" role="tab" id="adnInstallTab-{{ $tab }}" aria-controls="adnInstallPane-{{ $tab }}"
				        aria-selected="false" tabindex="-1" data-adn-tab="{{ $tab }}">{{ trans('app_install.' . $label) }}</button>
			@endforeach
		</div>
		@foreach ($guideSteps as $tab => $guide)
			<div class="adn-install-pane" role="tabpanel" id="adnInstallPane-{{ $tab }}" aria-labelledby="adnInstallTab-{{ $tab }}" data-adn-pane="{{ $tab }}" hidden>
				<ol class="adn-install-steps">
					@foreach ($guide['steps'] as $number => $step)
						<li>
							<span class="adn-install-step-number" aria-hidden="true">{{ $number + 1 }}</span>
							<span>{{ trans('app_install.' . $step, ['name' => $appName]) }}</span>
						</li>
					@endforeach
				</ol>
				@if (!empty($guide['note']))
					<p class="adn-install-note"><i class="bi bi-info-circle" aria-hidden="true"></i> {{ trans('app_install.' . $guide['note'], ['host' => $appHost]) }}</p>
				@endif
			</div>
		@endforeach
		<button type="button" class="btn btn-primary adn-install-done" data-adn-sheet-close>{{ trans('app_install.got_it') }}</button>
	</div>
</div>

{{-- Loaded on phones and in the installed app only: never on desktop --}}
<script>
	if (window.matchMedia && (matchMedia('(max-width: 767.98px)').matches || matchMedia('(display-mode: standalone)').matches)) {
		var adnAppInstall = document.createElement('script');
		adnAppInstall.src = {!! json_encode($installJs, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!};
		adnAppInstall.defer = true;
		document.head.appendChild(adnAppInstall);
	}
</script>
