{{--
	adniro: the footer tracking code (Admin > Settings > Footer). With the cookie banner on, it is kept
	inert in a <template> and only runs after the visitor accepts (public/assets/js/app/consent.js).
	The decision is made in the browser, so cached guest pages work for everyone.
--}}
@php
	$trackingCode = (string)config('settings.footer.tracking_code');
	$needsConsent = \App\Helpers\Common\CookieConsent::required();
@endphp
@if ($trackingCode !== '')
	@if ($needsConsent)
		<template id="consentGatedScripts">{!! printJs($trackingCode) !!}</template>
	@else
		{!! printJs($trackingCode) . "\n" !!}
	@endif
@endif
