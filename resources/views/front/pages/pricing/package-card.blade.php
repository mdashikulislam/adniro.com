{{--
 * One plan on the pricing page (adniro), for promotion and subscription packages alike.
 * Every part comes from the package's admin options: short name, price, promotion days, the
 * description lines (features), the ribbon colour (a paid card's tint) and "recommended" (the
 * highlighted card). Styles: public/assets/css/pricing.css.
 *
 * Expects: $package (array), $ctaUrl, $ctaAttr (extra attributes, already safe), $period (e.g. "per listing"),
 * $tierIcon (a Bootstrap Icons class), $freeCtaLabel (optional: the button of a free plan).
--}}
@php
	// The ribbon colours an admin can pick are Bootstrap's own; only those reach the style attribute
	$tintableColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];

	$name = (string)(data_get($package, 'short_name') ?: data_get($package, 'name'));
	$price = (float)data_get($package, 'price', 0);
	$isFree = ($price <= 0);
	$isFeatured = ((int)data_get($package, 'recommended') === 1);
	$promoDays = (int)data_get($package, 'promotion_time', 0);
	$ribbon = (string)data_get($package, 'ribbon');
	$tint = (!$isFree && in_array($ribbon, $tintableColors, true)) ? $ribbon : null;

	$features = collect((array)data_get($package, 'description_array'))
		->map(fn ($line) => trim((string)$line))
		->filter(fn ($line) => $line !== '')
		->values();

	$cardClass = $isFeatured ? 'pr-card pr-card--featured' : 'pr-card';
	$ctaClass = $isFeatured ? 'pr-card-cta pr-card-cta--solid' : 'pr-card-cta';
	$ctaLabel = $isFree ? ($freeCtaLabel ?? trans('pricing.post_free')) : trans('pricing.choose', ['plan' => $name]);
	$featuresId = 'pr-features-' . data_get($package, 'id');
@endphp
<article class="{{ $cardClass }}"@if ($tint) style="--pr-tier: var(--bs-{{ $tint }}-rgb)"@endif>
	@if ($isFeatured)
		<span class="pr-card-badge">
			<i class="bi bi-stars" aria-hidden="true"></i>
			<span>{{ trans('pricing.popular') }}</span>
		</span>
	@endif

	<header class="pr-card-head">
		<span class="pr-card-icon" aria-hidden="true"><i class="bi {{ $tierIcon }}"></i></span>
		<h3 class="pr-card-name">
			{{ $name }}
		</h3>
		<span class="pr-card-chip">
			@if ($promoDays > 0)
				<i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>
				{{ trans('pricing.boost_days', ['count' => $promoDays]) }}
			@else
				{{ trans('pricing.standard') }}
			@endif
		</span>
	</header>

	<p class="pr-card-price">
		<span class="pr-card-amount">
			{!! data_get($package, 'price_formatted') !!}
		</span>
		<span class="pr-card-period">{{ $period }}</span>
	</p>

	<a href="{{ $ctaUrl }}" class="{{ $ctaClass }}"{!! $ctaAttr !!}>
		<span>{{ $ctaLabel }}</span>
		<i class="bi bi-arrow-right" aria-hidden="true"></i>
	</a>

	@if ($features->isNotEmpty())
		<p class="visually-hidden" id="{{ $featuresId }}">{{ trans('pricing.features_label', ['plan' => $name]) }}</p>
		<ul class="pr-features" aria-labelledby="{{ $featuresId }}">
			@foreach ($features as $feature)
				<li class="pr-feature">
					<i class="bi bi-check-circle-fill" aria-hidden="true"></i>
					<span>{!! $feature !!}</span>
				</li>
			@endforeach
		</ul>
	@endif
</article>
