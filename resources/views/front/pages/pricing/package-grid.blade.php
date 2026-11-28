{{--
 * The plan cards of one package type (adniro pricing page), in the admin's order.
 * A card's icon follows its price: the free plan gets a tag, then each dearer plan a stronger icon.
 *
 * Expects: $packages (array), $message (shown when there are none), $periodFor (fn ($package): string), $ctaUrlFor (fn ($package): string),
 * $ctaAttr.
--}}
@php
	$packages = is_array($packages ?? null) ? $packages : [];
	$paidIcons = ['bi-lightning-charge', 'bi-rocket-takeoff', 'bi-gem', 'bi-trophy'];
	$paidPrices = collect($packages)->map(fn ($p) => (float)data_get($p, 'price', 0))->filter(fn ($price) => $price > 0)
		->unique()->sort()->values();
	$tierIconFor = function ($package) use ($paidIcons, $paidPrices): string {
		$price = (float)data_get($package, 'price', 0);
		if ($price <= 0) {
			return 'bi-tag';
		}
		$rank = (int)$paidPrices->search($price);

		return $paidIcons[min($rank, count($paidIcons) - 1)];
	};
@endphp
@if (count($packages) > 0)
	<div class="pr-grid" data-count="{{ count($packages) }}">
		@foreach ($packages as $package)
			@include('front.pages.pricing.package-card', [
				'package'  => $package,
				'ctaUrl'   => $ctaUrlFor($package),
				'ctaAttr'  => $ctaAttr,
				'period'   => $periodFor($package),
				'tierIcon' => $tierIconFor($package),
				'freeCtaLabel' => $freeCtaLabel ?? null,
			])
		@endforeach
	</div>
@else
	<div class="pr-empty">
		<span class="pr-empty-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
		<p class="pr-empty-title">{{ $message ?: trans('pricing.empty_title') }}</p>
	</div>
@endif
