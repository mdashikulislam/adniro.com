{{--
 * adniro: the side panel of the redesigned listing form: live preview, checklist, tips.
 * A sticky column on large screens; a bottom sheet on phones (Bootstrap responsive offcanvas),
 * opened from the checklist button in the form's sticky bar. Filled by listing-form/*.js.
--}}
@section('body_class', 'page-listing-form')
@php
	$post ??= [];
	$isOwner = $listingForm->isEdit && auth()->check() && auth()->user()->getAuthIdentifier() == data_get($post, 'user_id');
	$checks = [
		'category'    => '#catsContainer',
		'title'       => '#title',
		'description' => '#lfSection-what .lf-description',
		'price'       => '#price',
		'city'        => '#cityBox',
		'contact'     => '#lfSection-contact',
	];
	// Good-length targets in the row labels; panel.js keeps them current (same rule as strength-score.js)
	$titleGoodMin = max(15, (int)config('settings.listing_form.title_min_length', 2));
	$labelLimits = [
		'{min}' => $titleGoodMin,
		'{max}' => max($titleGoodMin, min(80, (int)config('settings.listing_form.title_max_length', 150))),
	];
	$descriptionLimits = ['{min}' => max(200, (int)config('settings.listing_form.description_min_length', 5))];
	$tips = collect(range(1, 5))->map(fn ($i) => t('sell_quickly_advice_' . $i));
@endphp
<div class="offcanvas-lg offcanvas-bottom lf-panel" tabindex="-1" id="lfPanel" aria-labelledby="lfPanelTitle">
	<div class="offcanvas-header d-lg-none">
		<h2 class="offcanvas-title h5 mb-0" id="lfPanelTitle">{{ trans('listing_form.panel_title') }}</h2>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#lfPanel"
		        aria-label="{{ trans('listing_form.panel_close') }}"></button>
	</div>
	<div class="offcanvas-body lf-panel-body">

		{{-- Live preview: text only, set with textContent --}}
		<section class="lf-panel-card lf-preview" aria-labelledby="lfPreviewTitle">
			<h2 class="lf-panel-heading" id="lfPreviewTitle">{{ trans('listing_form.preview') }}</h2>
			<div class="lf-preview-card">
				<div class="lf-preview-photo" aria-hidden="true"><i class="bi bi-image"></i></div>
				<div class="lf-preview-text">
					<p class="lf-preview-category" data-lf-preview="category">{{ trans('listing_form.preview_category') }}</p>
					<p class="lf-preview-title" data-lf-preview="title">{{ trans('listing_form.preview_title') }}</p>
					<p class="lf-preview-price" data-lf-preview="price">{{ trans('listing_form.preview_price') }}</p>
					<p class="lf-preview-city">
						<i class="bi bi-geo-alt" aria-hidden="true"></i>
						<span data-lf-preview="city">{{ trans('listing_form.preview_city') }}</span>
					</p>
				</div>
			</div>
		</section>

		{{-- Listing quality: a ring with the score, a tier badge, and what each item is worth (listing-form/panel.js) --}}
		<section class="lf-panel-card lf-strength" aria-labelledby="lfStrengthTitle" data-lf-strength data-tier="weak">
			<div class="lf-strength-head">
				<div class="lf-ring" role="meter" aria-labelledby="lfStrengthTitle"
				     aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-lf-meter>
					<svg class="lf-ring-svg" viewBox="0 0 64 64" aria-hidden="true">
						<circle class="lf-ring-track" cx="32" cy="32" r="27" pathLength="100"></circle>
						<circle class="lf-ring-value" cx="32" cy="32" r="27" pathLength="100" data-lf-ring></circle>
					</svg>
					<span class="lf-ring-label" aria-hidden="true">
						<span class="lf-ring-number"><span data-lf-score>0</span>%</span>
						<span class="lf-ring-caption">{{ trans('listing_form.strength_score') }}</span>
					</span>
				</div>
				<div class="lf-strength-intro">
					<div class="lf-strength-titlebar">
						<h2 class="lf-panel-heading" id="lfStrengthTitle">{{ trans('listing_form.strength') }}</h2>
						<span class="lf-tier-badge" data-lf-tier>{{ trans('listing_form.strength_weak') }}</span>
					</div>
					<p class="lf-strength-text" data-lf-tier-text aria-live="polite">{{ trans('listing_form.strength_weak_text') }}</p>
				</div>
			</div>

			<ul class="lf-strength-list">
				@foreach ($checks as $check => $target)
					<li class="lf-check" data-lf-check="{{ $check }}">
						<a href="#" class="lf-check-link" data-lf-target="{{ $target }}">
							<span class="lf-check-icon" aria-hidden="true"></span>
							<span class="lf-check-text" data-lf-label>{{ strtr(trans('listing_form.check_' . $check), $check === 'description' ? $descriptionLimits : $labelLimits) }}</span>
							<span class="lf-check-worth" data-lf-worth aria-hidden="true"></span>
							<span class="visually-hidden lf-check-state"></span>
						</a>
					</li>
				@endforeach
			</ul>

			@unless ($isSingleStep ?? false)
				<p class="lf-strength-photos">
					<i class="bi bi-images" aria-hidden="true"></i> {{ trans('listing_form.strength_photos_next') }}
				</p>
			@endunless
		</section>


		@if ($isOwner)
			<section class="lf-panel-card lf-panel-actions">
				<div class="d-grid gap-2">
					<a href="{{ urlGen()->post($post) }}" class="btn btn-outline-primary">
						<i class="fa-regular fa-hand-point-right"></i> {{ t('Return to the listing') }}
					</a>
					@if (!$isSingleStep)
						<a href="{{ url('posts/' . data_get($post, 'id') . '/photos') }}" class="btn btn-secondary">
							<i class="fa-solid fa-camera"></i> {{ t('Update Photos') }}
						</a>
						@if (isset($countPackages) && isset($countPaymentMethods) && $countPackages > 0 && $countPaymentMethods > 0)
							<a href="{{ url('posts/' . data_get($post, 'id') . '/payment') }}" class="btn btn-success">
								<i class="fa-regular fa-circle-check"></i> {{ t('Make It Premium') }}
							</a>
						@endif
					@endif
				</div>
			</section>
		@endif

		<details class="lf-panel-card lf-tips">
			<summary class="lf-tips-summary">
				<i class="bi bi-lightbulb" aria-hidden="true"></i> {{ trans('listing_form.tips') }}
			</summary>
			<ul class="lf-tips-list">
				@foreach ($tips as $tip)
					<li>{{ $tip }}</li>
				@endforeach
			</ul>
		</details>

	</div>
</div>
