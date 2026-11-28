{{--
 * Promotion packages (paid once per listing) on the pricing page (adniro).
 * Guests who cannot post get the sign-in pop-up; everyone else goes to the listing form with the plan.
--}}
@php
	$addListingUrl ??= urlGen()->addPost();
	$ctaAttr = '';
	if (!doesGuestHaveAbilityToCreateListings()) {
		$addListingUrl = '#quickLogin';
		$ctaAttr = ' data-bs-toggle="modal"';
	}
	$ctaUrlFor = fn ($package): string => str_starts_with($addListingUrl, '#')
		? $addListingUrl
		: $addListingUrl . '?packageId=' . data_get($package, 'id');
@endphp
<div id="promoPackages">
	@include('front.pages.pricing.package-grid', [
		'packages'  => $packages ?? [],
		'message'   => $message ?? '',
		'periodFor' => fn ($package): string => trans('pricing.per_listing'),
		'ctaUrlFor' => $ctaUrlFor,
		'ctaAttr'   => $ctaAttr,
	])
</div>
