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

		{{-- What is still missing --}}
		<section class="lf-panel-card lf-checklist" aria-labelledby="lfChecklistTitle">
			<div class="lf-checklist-head">
				<h2 class="lf-panel-heading" id="lfChecklistTitle">{{ trans('listing_form.checklist') }}</h2>
				<span class="lf-checklist-count" data-lf-progress-text></span>
			</div>
			<div class="progress lf-progress" role="progressbar" aria-labelledby="lfChecklistTitle"
			     aria-valuemin="0" aria-valuemax="{{ count($checks) }}" aria-valuenow="0" data-lf-progress>
				<div class="progress-bar" style="width: 0%"></div>
			</div>
			<ul class="lf-checklist-list">
				@foreach ($checks as $check => $target)
					<li class="lf-check" data-lf-check="{{ $check }}">
						<a href="#" class="lf-check-link" data-lf-target="{{ $target }}">
							<span class="lf-check-icon" aria-hidden="true"></span>
							<span class="lf-check-text">{{ trans('listing_form.check_' . $check) }}</span>
							<span class="visually-hidden lf-check-state"></span>
						</a>
					</li>
				@endforeach
			</ul>
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
