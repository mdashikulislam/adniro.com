{{--
 * Subscription packages on the pricing page (adniro): each card leads to the account's subscription page.
--}}
@php
	$subscriptionUrl = url(urlGen()->getAccountBasePath() . '/subscription');
	$ctaUrlFor = fn ($package): string => $subscriptionUrl . '?packageId=' . data_get($package, 'id');
@endphp
<div id="subsPackages">
	<p class="pr-tab-hint">{{ t('subs_packages_hint') }}</p>
	@include('front.pages.pricing.package-grid', [
		'packages'  => $packages ?? [],
		'message'   => $message ?? '',
		'periodFor' => fn ($package): string => filled(data_get($package, 'interval'))
			? trans('pricing.per_interval', ['interval' => data_get($package, 'interval')])
			: '',
		'ctaUrlFor' => $ctaUrlFor,
		'ctaAttr'   => '',
		'freeCtaLabel' => t('get_started'),
	])
</div>
