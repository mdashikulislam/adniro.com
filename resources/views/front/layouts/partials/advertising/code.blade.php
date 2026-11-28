{{--
	adniro: one ad network's code in an ad slot. Where the cookie banner applies, it is kept inert in a
	<template> and run in place by public/assets/js/app/consent.js: at once for visitors outside the UK
	and the EEA, after "Accept" for the others. Expects: $code.
--}}
@if (trim((string)($code ?? '')) !== '')
	@if (\App\Helpers\Common\CookieConsent::required())
		<template data-consent-ad>{!! $code !!}</template>
	@else
		{!! $code !!}
	@endif
@endif
