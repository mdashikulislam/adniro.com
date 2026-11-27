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
	// Photos are scored on every step (when the site takes photos): live where they are added (single-step form,
	// Photos step), else from the photos already saved; on multi-step step 1 the row leads to the Next button
	$photosLimit = (int)($picturesLimit ?? config('settings.listing_form.pictures_limit', 5));
	$photosHere = $onPhotosStep || ($isSingleStep ?? false);
	if ($photosLimit <= 0) {
		unset($checks['photos']);
	} elseif (!$photosHere) {
		$checks['photos'] = '#nextStepBtn';
	}
	$savedPhotoCount = $listingForm->isEdit
		? count((array)data_get($post, 'pictures', []))
		: count((array)session('picturesInput', []));
	// Good-length targets in the row labels; panel.js keeps them current (same rule as strength-score.js)
	$titleGoodMin = max(15, (int)config('settings.listing_form.title_min_length', 2));
	$labelLimits = [
		'{min}' => $titleGoodMin,
		'{max}' => max($titleGoodMin, min(80, (int)config('settings.listing_form.title_max_length', 150))),
	];
	$descriptionLimits = ['{min}' => max(200, (int)config('settings.listing_form.description_min_length', 5))];
	$photoLimits = ['{min}' => 3]; // strength-score.js PHOTOS_GOOD
	// The five tips (sell_quickly_advice_1..5) and the Bootstrap icon that goes with what each one is about:
	// title and description, category, photos, price, a last check
	$tipIcons = ['card-text', 'grid', 'camera', 'tag', 'check2-circle'];
	$tips = collect($tipIcons)->map(fn ($icon, $i) => ['icon' => $icon, 'text' => t('sell_quickly_advice_' . ($i + 1))]);
	// The cover: the saved listing's first photo (edit), else the first photo already added in this create flow
	// (back from the multi-step Photos step), as the Photos step shows it; never the site's default picture
	$sessionCover = (string)collect((array)session('picturesInput', []))->first();
	$editCoverPath = (string)data_get($post, 'pictures.0.file_path', '');
	$previewCover = match (true) {
		$listingForm->isEdit && $editCoverPath !== '' && \App\Helpers\Common\Files\Storage\StorageDisk::getDisk()->exists($editCoverPath)
			=> (string)data_get($post, 'pictures.0.url.medium', ''),
		$listingForm->isEdit => '',
		$sessionCover !== '' && \App\Helpers\Common\Files\Storage\StorageDisk::getDisk()->exists($sessionCover)
			=> thumbService($sessionCover)->resize('picture-md')->url(),
		default => '',
	};
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
<div class="offcanvas-lg offcanvas-bottom lf-panel" tabindex="-1" id="lfPanel" aria-labelledby="lfPanelTitle"
     data-lf-photo-count="{{ $savedPhotoCount }}">
	<div class="offcanvas-header d-lg-none">
		<h2 class="offcanvas-title h5 mb-0" id="lfPanelTitle">{{ trans('listing_form.panel_title') }}</h2>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#lfPanel"
		        aria-label="{{ trans('listing_form.panel_close') }}"></button>
	</div>
	<div class="offcanvas-body lf-panel-body">
@endif

		{{-- Edit pages: manage the listing (view it, its photos, promote it), first in the panel --}}
		@if ($isOwner)
			<section class="lf-panel-card lf-manage" aria-labelledby="lfManageTitle">
				<h2 class="lf-panel-heading" id="lfManageTitle">{{ trans('listing_form.manage_title') }}</h2>
				<div class="lf-manage-list">
					<a href="{{ urlGen()->post($post) }}" class="lf-manage-item">
						<span class="lf-manage-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
						<span class="lf-manage-text">
							<span class="lf-manage-label">{{ t('Return to the listing') }}</span>
							<span class="lf-manage-hint">{{ trans('listing_form.manage_view_hint') }}</span>
						</span>
						<i class="bi bi-chevron-right lf-manage-arrow" aria-hidden="true"></i>
					</a>
					@unless ($isSingleStep ?? false)
						<a href="{{ url('posts/' . data_get($post, 'id') . '/photos') }}" class="lf-manage-item">
							<span class="lf-manage-icon" aria-hidden="true"><i class="bi bi-images"></i></span>
							<span class="lf-manage-text">
								<span class="lf-manage-label">{{ t('Update Photos') }}</span>
								<span class="lf-manage-hint">{{ trans('listing_form.manage_photos_hint', ['count' => $savedPhotoCount]) }}</span>
							</span>
							<i class="bi bi-chevron-right lf-manage-arrow" aria-hidden="true"></i>
						</a>
						@if (isset($countPackages) && isset($countPaymentMethods) && $countPackages > 0 && $countPaymentMethods > 0)
							<a href="{{ url('posts/' . data_get($post, 'id') . '/payment') }}" class="lf-manage-item lf-manage-item-premium">
								<span class="lf-manage-icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
								<span class="lf-manage-text">
									<span class="lf-manage-label">{{ t('Make It Premium') }}</span>
									<span class="lf-manage-hint">{{ trans('listing_form.manage_premium_hint') }}</span>
								</span>
								<i class="bi bi-chevron-right lf-manage-arrow" aria-hidden="true"></i>
							</a>
						@endif
					@endunless
				</div>
			</section>
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

		{{-- Live buyer preview: the site's own listing card, as in search results, filled from the form as the
		     seller types (listing-form/preview.js; text only) --}}
		<section class="lf-panel-card lf-live" aria-labelledby="lfLiveTitle" data-lf-live
		         data-lf-cover="{{ $previewCover }}">
			<div class="lf-live-head">
				<h2 class="lf-panel-heading lf-live-heading" id="lfLiveTitle">
					<span class="lf-live-dot" aria-hidden="true"></span>{{ trans('listing_form.live_title') }}
				</h2>
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
			</div>
		</section>

		{{-- Tips to sell faster: on the details step only, where the seller writes the listing. Always in view:
		     a tinted head, then the tips down a rail, each with its icon --}}
		@unless ($onPhotosStep)
			<section class="lf-panel-card lf-tips" aria-labelledby="lfTipsTitle">
				<header class="lf-tips-head">
					<span class="lf-tips-badge" aria-hidden="true"><i class="bi bi-lightbulb-fill"></i></span>
					<h2 class="lf-panel-heading lf-tips-title" id="lfTipsTitle">{{ trans('listing_form.tips') }}</h2>
				</header>
				<ol class="lf-tips-list" role="list">
					@foreach ($tips as $tip)
						<li class="lf-tips-item">
							<span class="lf-tips-icon" aria-hidden="true"><i class="bi bi-{{ $tip['icon'] }}"></i></span>
							<span class="lf-tips-text">{{ $tip['text'] }}</span>
						</li>
					@endforeach
				</ol>
			</section>
		@endunless

	</div>
</div>
