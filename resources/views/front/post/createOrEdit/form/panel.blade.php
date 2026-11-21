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
	// Live buyer preview: a saved cover on edit pages (single-step pages also show photos chosen in the form),
	// and the seller as buyers will see them: the form's name (the account's until changed), the verified-email tick
	$previewCover = (string)data_get($post, 'pictures.0.url.medium', '');
	$previewPhotoHint = ($isSingleStep ?? false) ? trans('listing_form.live_photo_below') : trans('listing_form.live_photo_next');
	$sellerUser = auth()->user();
	$sellerVerified = $sellerUser instanceof \App\Models\User && \App\Services\SellerService::isVerifiedSeller($sellerUser);
	$sellerName = trim((string)(data_get($post, 'contact_name') ?: data_get($sellerUser, 'name', '')));
	$sellerInitials = collect(preg_split('/\s+/u', $sellerName, -1, PREG_SPLIT_NO_EMPTY))->take(2)
		->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') ?: '?';
@endphp
<div class="offcanvas-lg offcanvas-bottom lf-panel" tabindex="-1" id="lfPanel" aria-labelledby="lfPanelTitle">
	<div class="offcanvas-header d-lg-none">
		<h2 class="offcanvas-title h5 mb-0" id="lfPanelTitle">{{ trans('listing_form.panel_title') }}</h2>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#lfPanel"
		        aria-label="{{ trans('listing_form.panel_close') }}"></button>
	</div>
	<div class="offcanvas-body lf-panel-body">

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

		{{-- Live buyer preview: the listing as buyers will see it, as a feed card or a detail view (listing-form/preview.js; text only) --}}
		<section class="lf-panel-card lf-live" aria-labelledby="lfLiveTitle" data-lf-live data-view="feed"
		         data-lf-cover="{{ $previewCover }}" data-lf-seller-verified="{{ $sellerVerified ? '1' : '0' }}"
		         data-lf-seller-default="{{ $sellerName }}">
			<div class="lf-live-head">
				<h2 class="lf-panel-heading lf-live-heading" id="lfLiveTitle">
					<span class="lf-live-dot" aria-hidden="true"></span>{{ trans('listing_form.live_title') }}
				</h2>
				<div class="lf-live-switch" role="group" aria-label="{{ trans('listing_form.live_view') }}">
					<button type="button" class="is-active" data-lf-view="feed" aria-pressed="true">{{ trans('listing_form.live_feed') }}</button>
					<button type="button" data-lf-view="detail" aria-pressed="false">{{ trans('listing_form.live_detail') }}</button>
				</div>
			</div>

			<article class="lf-live-card" aria-hidden="true">
				<div class="lf-live-media">
					<img class="lf-live-photo" alt="" data-lf-live-photo hidden>
					<div class="lf-live-empty" data-lf-live-empty>
						<span class="lf-live-empty-icon"><i class="bi bi-image"></i></span>
						<span>{{ $previewPhotoHint }}</span>
					</div>
					<span class="lf-live-cat" data-lf-live="category">{{ trans('listing_form.preview_category') }}</span>
					<span class="lf-live-fav"><i class="bi bi-heart"></i></span>
				</div>
				<div class="lf-live-body">
					<div class="lf-live-priceline">
						<p class="lf-live-price is-empty" data-lf-live="price">{{ trans('listing_form.preview_price') }}</p>
						<span class="lf-live-condition" data-lf-live="condition" hidden></span>
					</div>
					<h3 class="lf-live-name is-empty" data-lf-live="title">{{ trans('listing_form.live_title_empty') }}</h3>
					<ul class="lf-live-specs" data-lf-live-specs hidden></ul>
					<p class="lf-live-specs-empty" data-lf-live-specs-empty>{{ trans('listing_form.live_specs_empty') }}</p>
					{{-- Detail view only: every chosen detail, and the description --}}
					<dl class="lf-live-details" data-lf-live-details hidden></dl>
					<p class="lf-live-desc is-empty" data-lf-live="description">{{ trans('listing_form.live_description_empty') }}</p>
					<div class="lf-live-meta">
						<span class="lf-live-city"><i class="bi bi-geo-alt"></i> <span data-lf-live="city">{{ trans('listing_form.preview_city') }}</span></span>
						<span>{{ trans('listing_form.live_just_now') }}</span>
					</div>
					<div class="lf-live-seller">
						<span class="lf-live-avatar" data-lf-live="initials">{{ $sellerInitials }}</span>
						<span class="lf-live-seller-text">
							<span class="lf-live-seller-name">
								<span data-lf-live="seller">{{ $sellerName !== '' ? $sellerName : trans('listing_form.live_seller_empty') }}</span>
								<i class="bi bi-patch-check lf-live-verified" title="{{ trans('listing_form.live_verified') }}" @unless ($sellerVerified) hidden @endunless></i>
							</span>
							<span class="lf-live-negotiable" data-lf-live="negotiable" hidden>{{ trans('listing_form.preview_negotiable') }}</span>
						</span>
						<span class="lf-live-cta lf-live-cta-feed">{{ trans('listing_form.live_cta') }} <i class="bi bi-arrow-right"></i></span>
						<span class="lf-live-cta lf-live-cta-detail"><i class="bi bi-chat-dots"></i> {{ trans('listing_form.live_contact') }}</span>
					</div>
				</div>
			</article>
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
