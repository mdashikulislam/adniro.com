{{-- adniro: cookie banner, shown by consent.js when the visitor has not chosen yet --}}
@if (\App\Helpers\Common\CookieConsent::required())
	@php
		$cookiePolicyUrl = null;
		try {
			$cookiePolicy = \App\Models\Page::query()->where('slug', \App\Helpers\Common\CookieConsent::POLICY_SLUG)->first();
			$cookiePolicyUrl = $cookiePolicy ? urlGen()->page($cookiePolicy) : null;
		} catch (\Throwable $e) {
			report($e);
		}
	@endphp
	@php
		// On a local copy there is no Cloudflare: ?consent_region=IN (or GB...) tries either path
		$localRegion = app()->isLocal() ? strtoupper((string)request()->query('consent_region')) : '';
		$localRegion = preg_match('/^[A-Z]{2}$/', $localRegion) ? $localRegion : '';
	@endphp
	<div id="cookieConsent" class="cookie-consent card shadow-lg d-none text-start" role="dialog" aria-live="polite" aria-labelledby="cookieConsentTitle"
	     data-cookie="{{ \App\Helpers\Common\CookieConsent::COOKIE }}" data-max-age="{{ \App\Helpers\Common\CookieConsent::MAX_AGE }}"
	     data-consent-regions="{{ implode(',', \App\Helpers\Common\CookieConsent::REGIONS) }}"
	     data-region-url="{{ \App\Helpers\Common\CookieConsent::REGION_URL }}"@if ($localRegion !== '') data-region="{{ $localRegion }}"@endif>
		<div class="card-body">
			<h2 id="cookieConsentTitle" class="h6 fw-bold mb-1">{{ t('cookie_banner_title') }}</h2>
			<p class="small text-secondary mb-3">
				{{ t('cookie_banner_text') }}
				@if (!empty($cookiePolicyUrl))
					<a href="{{ $cookiePolicyUrl }}">{{ t('cookie_banner_policy') }}</a>
				@endif
			</p>
			<div class="d-flex flex-wrap gap-2 justify-content-end">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-consent="denied">{{ t('cookie_banner_decline') }}</button>
				<button type="button" class="btn btn-primary btn-sm" data-consent="granted">{{ t('cookie_banner_accept') }}</button>
			</div>
		</div>
	</div>
	<style>
		.cookie-consent { position: fixed; z-index: 1080; bottom: 16px; left: 16px; right: 16px; max-width: 420px; border-radius: 14px; }
		@media (min-width: 576px) { .cookie-consent { right: auto; } }
	</style>
	<script src="{{ url('assets/js/app/consent.js') }}?v={{ @filemtime(public_path('assets/js/app/consent.js')) }}" defer></script>
@endif
