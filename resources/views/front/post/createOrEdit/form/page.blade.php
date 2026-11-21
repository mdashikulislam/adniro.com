{{--
 * adniro: the page around the listing form, shared by the four create/edit pages.
 *   listingForm  App\Services\ListingForm\ListingFormState
 *   isSingleStep bool
 *   formAction   string
 *   actions      see form/body.blade.php
 * config('listing_form.v2') picks the redesigned layout; false renders the stock one.
--}}
@php
	$lfV2 = (bool)config('listing_form.v2', true);
	$post ??= [];
	$isEdit = $listingForm->isEdit;
	$postTitle = (string)data_get($post, 'title');
	
	// Settings and text for the listing-form/*.js scripts
	$lfConfig = [
		'titleMin'       => (int)config('settings.listing_form.title_min_length', 2),
		'titleMax'       => (int)config('settings.listing_form.title_max_length', 150),
		'descriptionMin' => (int)config('settings.listing_form.description_min_length', 5),
		'descriptionMax' => (int)config('settings.listing_form.description_max_length', 6000),
		'priceMandatory' => (config('settings.listing_form.price_mandatory') == '1'),
		'currency'       => (string)config('currency.symbol', ''),
		'strings'        => trans('listing_form'),
		'writeUrl'       => url('posts/assistant/write'),
		'priceHintUrl'   => app(\App\Services\ListingForm\PriceHint::class)->enabled() ? url('posts/price-hint') : null,
		'postId'         => $listingForm->postId,
	];
	$lfScripts = ['fields', 'counters', 'validate', 'preview', 'strength-score', 'panel', 'undo-stack', 'ai-field-tools', 'price-hint'];
@endphp

@if ($lfV2)
	@include('front.common.spacer')
	<div class="main-container lf-v2">
		<div class="container">
			<div class="row">

				@include('front.post.partials.notification')

				<div class="col-lg-8">
					<header class="lf-head">
						@if ($isEdit)
							<p class="lf-head-kicker">{{ trans('listing_form.edit_kicker') }}</p>
							<h1 class="lf-head-title">{{ str($postTitle)->limit(80) }}</h1>
							<div class="lf-head-meta">
								<span class="lf-status lf-status--{{ $listingForm->status }}">
									<span class="lf-status-dot" aria-hidden="true"></span>
									{{ trans('listing_form.status_' . $listingForm->status) }}
								</span>
								<a href="{{ urlGen()->post($post) }}" class="lf-head-link">
									{{ trans('listing_form.view_listing') }} <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
								</a>
							</div>
						@else
							<h1 class="lf-head-title">{{ t('create_new_listing') }}</h1>
							<p class="lf-head-sub">{{ trans('listing_form.create_sub') }}</p>
						@endif
					</header>

					<form id="payableForm"
					      action="{{ $formAction }}"
					      method="POST"
					      enctype="multipart/form-data"
					      class="lf-form {{ unsavedFormGuard() }}"
					      data-lf-config="{{ json_encode($lfConfig, JSON_UNESCAPED_UNICODE) }}"
					>
						@include('front.post.createOrEdit.form.hidden-inputs')

						@include('front.post.createOrEdit.form.body', ['lfV2' => true])
					</form>
				</div>

				<div class="col-lg-4">
					@include('front.post.createOrEdit.form.panel')
				</div>

			</div>
		</div>
	</div>
@else
	@include('front.common.spacer')
	<div class="main-container">
		<div class="container">
			<div class="row">

				@include('front.post.partials.notification')

				<div class="col-md-9">
					<div class="container border rounded bg-body-tertiary p-4 p-lg-3 p-md-2 mb-sm-3">
						<h1 class="h3 fw-bold border-bottom pb-3 mb-4">
							@if ($isEdit)
								<i class="fa-solid fa-pen-to-square"></i> {{ t('update_my_listing') }}
								-&nbsp;<a href="{{ urlGen()->post($post) }}"
								          class="{{ linkClass() }}"
								          data-bs-placement="top"
								          data-bs-toggle="tooltip"
								          title="{{ $postTitle }}"
								>{{ str($postTitle)->limit(45) }}</a>
							@else
								<i class="fa-regular fa-pen-to-square"></i> {{ t('create_new_listing') }}
							@endif
						</h1>

						<div class="row d-flex justify-content-center">
							<div class="col-md-10 col-sm-12 col-xs-12">

								<form id="payableForm"
								      action="{{ $formAction }}"
								      method="POST"
								      enctype="multipart/form-data"
								      class="{{ unsavedFormGuard() }}"
								>
									@include('front.post.createOrEdit.form.hidden-inputs')

									@include('front.post.createOrEdit.form.body', ['lfV2' => false])
								</form>

							</div>
						</div>
					</div>
				</div>

				<div class="col-md-3 reg-sidebar">
					@include('front.post.createOrEdit.partials.right-sidebar')
				</div>

			</div>
		</div>
	</div>
@endif

@include('front.post.createOrEdit.partials.category-modal')

@includeWhen(!auth()->check(), 'auth.login.partials.modal')

@if ($lfV2)
	@pushOnce('after_styles_stack', 'listing_form_css')
		<link href="{{ url('assets/css/listing-form.css') }}?v={{ @filemtime(public_path('assets/css/listing-form.css')) }}" rel="stylesheet">
	@endPushOnce
	@pushOnce('after_styles_stack', 'listing_form_panel_css')
		<link href="{{ url('assets/css/listing-form-panel.css') }}?v={{ @filemtime(public_path('assets/css/listing-form-panel.css')) }}" rel="stylesheet">
	@endPushOnce
	@pushOnce('after_styles_stack', 'listing_form_ai_css')
		<link href="{{ url('assets/css/listing-form-ai.css') }}?v={{ @filemtime(public_path('assets/css/listing-form-ai.css')) }}" rel="stylesheet">
	@endPushOnce
	@pushOnce('after_scripts_stack', 'listing_form_js')
		@foreach ($lfScripts as $lfScript)
			<script src="{{ url('assets/js/app/listing-form/' . $lfScript . '.js') }}?v={{ @filemtime(public_path('assets/js/app/listing-form/' . $lfScript . '.js')) }}"></script>
		@endforeach
	@endPushOnce
@endif
