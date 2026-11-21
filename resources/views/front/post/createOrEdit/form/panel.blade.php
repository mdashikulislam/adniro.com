{{--
 * adniro: the side panel of the redesigned listing form: listing quality, live buyer preview, tips.
 * Details step: a sticky column on large screens and a bottom sheet on phones (Bootstrap responsive offcanvas),
 * opened from the button in the form's sticky bar, filled from the form by listing-form/*.js.
 * Multi-step Photos step ($panelState given): filled from the details saved in step 1 (listing-form/summary.js),
 * with photos scored live; under the photos on phones, since that page has no sticky bar.
--}}
@section('body_class', 'page-listing-form')
@php
	$post ??= [];
	$panelState ??= null;
	$onPhotosStep = is_array($panelState);
	$listingForm ??= (object)['isEdit' => false];
	$isOwner = !$onPhotosStep && $listingForm->isEdit && auth()->check() && auth()->user()->getAuthIdentifier() == data_get($post, 'user_id');
	$checks = [
		'category'    => '#catsContainer',
		'title'       => '#title',
		'description' => '#lfSection-what .lf-description',
		'photos'      => '#lfSection-photos',
		'price'       => '#price',
		'city'        => '#cityBox',
		'contact'     => '#lfSection-contact',
	];
	// Photos are scored where they are added: the multi-step Photos step
	if (!$onPhotosStep) {
		unset($checks['photos']);
	}
	// Good-length targets in the row labels; panel.js keeps them current (same rule as strength-score.js)
	$titleGoodMin = max(15, (int)config('settings.listing_form.title_min_length', 2));
	$labelLimits = [
		'{min}' => $titleGoodMin,
		'{max}' => max($titleGoodMin, min(80, (int)config('settings.listing_form.title_max_length', 150))),
	];
	$descriptionLimits = ['{min}' => max(200, (int)config('settings.listing_form.description_min_length', 5))];
	$photoLimits = ['{min}' => 3]; // strength-score.js PHOTOS_GOOD
	$tips = collect(range(1, 5))->map(fn ($i) => t('sell_quickly_advice_' . $i));
	// Live buyer preview: a saved cover on edit pages; single-step pages also show the photos chosen in the form
	$previewCover = (string)data_get($post, 'pictures.0.url.medium', '');
	$previewPhotoHint = match (true) {
		$onPhotosStep => trans('listing_form.live_photo_here'),
		($isSingleStep ?? false) => trans('listing_form.live_photo_below'),
		default => trans('listing_form.live_photo_next'),
	};
@endphp
@if ($onPhotosStep)
<div class="lf-panel lf-panel-inline" id="lfPanel" data-lf-panel-state="{{ json_encode($panelState, JSON_UNESCAPED_UNICODE) }}">
	<div class="lf-panel-body">
@else
<div class="offcanvas-lg offcanvas-bottom lf-panel" tabindex="-1" id="lfPanel" aria-labelledby="lfPanelTitle">
	<div class="offcanvas-header d-lg-none">
		<h2 class="offcanvas-title h5 mb-0" id="lfPanelTitle">{{ trans('listing_form.panel_title') }}</h2>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#lfPanel"
		        aria-label="{{ trans('listing_form.panel_close') }}"></button>
	</div>
	<div class="offcanvas-body lf-panel-body">
@endif

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
							<span class="lf-check-text" data-lf-label>{{ strtr(trans('listing_form.check_' . $check), match ($check) { 'description' => $descriptionLimits, 'photos' => $photoLimits, default => $labelLimits }) }}</span>
							<span class="lf-check-worth" data-lf-worth aria-hidden="true"></span>
							<span class="visually-hidden lf-check-state"></span>
						</a>
					</li>
				@endforeach
			</ul>

			@unless (($isSingleStep ?? false) || $onPhotosStep)
				<p class="lf-strength-photos">
					<i class="bi bi-images" aria-hidden="true"></i> {{ trans('listing_form.strength_photos_next') }}
				</p>
			@endunless
		</section>

		{{-- Live buyer preview: the site's own listing card (search results) and listing page, filled from the form
		     as the seller types (listing-form/preview.js; text only) --}}
		<section class="lf-panel-card lf-live" aria-labelledby="lfLiveTitle" data-lf-live data-view="feed"
		         data-lf-cover="{{ $previewCover }}">
			<div class="lf-live-head">
				<h2 class="lf-panel-heading lf-live-heading" id="lfLiveTitle">
					<span class="lf-live-dot" aria-hidden="true"></span>{{ trans('listing_form.live_title') }}
				</h2>
				<div class="lf-live-switch" role="group" aria-label="{{ trans('listing_form.live_view') }}">
					<button type="button" class="is-active" data-lf-view="feed" aria-pressed="true">{{ trans('listing_form.live_feed') }}</button>
					<button type="button" data-lf-view="detail" aria-pressed="false">{{ trans('listing_form.live_detail') }}</button>
				</div>
			</div>

			<div class="lf-live-stage" aria-hidden="true">
				{{-- Feed: the listing card from search results (front/search/partials/posts/template/grid.blade.php) --}}
				<article class="listing-card lf-live-card lf-live-feed">
					<div class="listing-card-media">
						<div class="listing-card-img lf-live-media">
							<img class="listing-card-picture" alt="" data-lf-live-photo hidden>
							<div class="lf-live-empty" data-lf-live-empty>
								<span class="lf-live-empty-icon"><i class="bi bi-image"></i></span>
								<span>{{ $previewPhotoHint }}</span>
							</div>
						</div>
						<span class="btn listing-fav"><i class="bi bi-heart small"></i></span>
						<span class="listing-card-location" data-lf-live-city-pill hidden>
							<i class="bi bi-geo-alt-fill"></i> <span data-lf-live="city"></span>
						</span>
					</div>
					<div class="listing-card-body">
						<div class="listing-card-topline">
							<span class="listing-card-cat" data-lf-live="category">{{ trans('listing_form.preview_category') }}</span>
						</div>
						<h3 class="h5 listing-card-title" data-lf-live="title">{{ trans('listing_form.live_title_empty') }}</h3>
						<p class="listing-card-desc" data-lf-live="excerpt" hidden></p>
						<div class="listing-card-footer">
							<div class="listing-card-price" data-lf-live="price">{{ trans('listing_form.preview_price') }}</div>
							<div class="listing-card-footer-right">
								<span class="listing-card-date"><i class="fa-regular fa-clock"></i> {{ trans('listing_form.live_just_now') }}</span>
							</div>
						</div>
					</div>
				</article>

				{{-- Detail: the listing page's main column (front/post/show/index.blade.php and partials/details), scaled down --}}
				<div class="lf-live-page">
					<div class="listing-main">
						<header class="listing-header">
							<div class="listing-header-top">
								<div class="listing-chips">
									<span class="listing-chip listing-chip-cat">
										<i class="bi bi-folder-fill"></i> <span data-lf-live="page-category">{{ trans('listing_form.preview_category') }}</span>
									</span>
								</div>
								<div class="listing-price-card" data-lf-live-price-card>
									<span class="listing-price-icon"><i class="bi bi-tag-fill"></i></span>
									<span class="listing-price-body">
										<span class="listing-price-value">
											<span data-lf-live="page-price">{{ trans('listing_form.preview_price') }}</span>
											<span class="listing-price-negotiable" data-lf-live="negotiable" hidden>{{ t('negotiable') }}</span>
										</span>
									</span>
								</div>
							</div>
							<h3 class="listing-title" data-lf-live="page-title">{{ trans('listing_form.live_title_empty') }}</h3>
							<div class="listing-header-bottom">
								<ul class="listing-meta list-unstyled">
									<li class="listing-meta-city"><i class="bi bi-geo-alt-fill"></i> <span data-lf-live="page-city">{{ trans('listing_form.preview_city') }}</span></li>
									<li><i class="bi bi-clock"></i> {{ trans('listing_form.live_just_now') }}</li>
								</ul>
							</div>
						</header>

						<div class="listing-gallery">
							<div class="gallery-container lf-live-gallery">
								<img alt="" data-lf-live-page-photo hidden>
								<div class="lf-live-empty" data-lf-live-page-empty>
									<span class="lf-live-empty-icon"><i class="bi bi-image"></i></span>
									<span>{{ $previewPhotoHint }}</span>
								</div>
							</div>
						</div>

						<div class="items-details listing-details">
							<div class="tab-content listing-panel">
								<div class="items-details-info">
									<section class="listing-section">
										<h4 class="listing-section-title">
											<span class="listing-section-icon"><i class="bi bi-card-text"></i></span>
											{{ t('Description') }}
										</h4>
										<div class="listing-description" data-lf-live="description">{{ trans('listing_form.live_description_empty') }}</div>
									</section>
									<section class="listing-section" data-lf-live-tags-section hidden>
										<h4 class="listing-section-title">
											<span class="listing-section-icon"><i class="bi bi-tags"></i></span>
											{{ t('Tags') }}
										</h4>
										<div class="listing-tags" data-lf-live-tags></div>
									</section>
									<section class="listing-section" data-lf-live-specs-section hidden>
										<h4 class="listing-section-title">
											<span class="listing-section-icon"><i class="bi bi-list-check"></i></span>
											{{ t('Additional Details') }}
										</h4>
										<div class="listing-specs" data-lf-live-specs></div>
									</section>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
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
