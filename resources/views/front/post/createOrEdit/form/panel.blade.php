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

		{{-- Listing strength: a weighted score with a tier, the one step that helps most, live hints (listing-form/panel.js) --}}
		<section class="lf-panel-card lf-strength" aria-labelledby="lfStrengthTitle" data-lf-strength data-tier="weak">
			<div class="lf-strength-head">
				<h2 class="lf-panel-heading" id="lfStrengthTitle">{{ trans('listing_form.strength') }}</h2>
				<p class="lf-strength-score" aria-hidden="true"><span data-lf-score>0</span><span class="lf-strength-of">/100</span></p>
			</div>
			<div class="lf-strength-meter" role="meter" aria-labelledby="lfStrengthTitle"
			     aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-lf-meter>
				@foreach (['weak', 'fair', 'strong', 'excellent'] as $tier)
					<span class="lf-strength-step" data-lf-step="{{ $tier }}"></span>
				@endforeach
			</div>
			<p class="lf-strength-tier" aria-live="polite">
				<strong data-lf-tier>{{ trans('listing_form.strength_weak') }}</strong>
				<span data-lf-tier-text>{{ trans('listing_form.strength_weak_text') }}</span>
			</p>

			<a href="#" class="lf-next" data-lf-next data-lf-target="" hidden>
				<span class="lf-next-body">
					<span class="lf-next-label">{{ trans('listing_form.strength_next') }}</span>
					<span class="lf-next-text" data-lf-next-text></span>
				</span>
				<span class="lf-points lf-next-points" data-lf-next-points></span>
				<i class="bi bi-arrow-right lf-next-arrow" aria-hidden="true"></i>
			</a>

			<ul class="lf-strength-list">
				@foreach ($checks as $check => $target)
					<li class="lf-check" data-lf-check="{{ $check }}">
						<a href="#" class="lf-check-link" data-lf-target="{{ $target }}">
							<span class="lf-check-icon" aria-hidden="true"></span>
							<span class="lf-check-body">
								<span class="lf-check-text">{{ trans('listing_form.check_' . $check) }}</span>
								<span class="lf-check-hint" data-lf-hint></span>
								@if (in_array($check, ['title', 'description']))
									<span class="lf-check-bar" aria-hidden="true"><span data-lf-bar></span></span>
								@endif
							</span>
							<span class="lf-points" data-lf-points aria-hidden="true"></span>
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
