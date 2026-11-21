{{-- adniro: the form buttons; the submit button keeps its ID for package-payment.js --}}
@if ($lfV2 ?? false)
	{{-- On phones this bar sticks to the bottom of the screen while the form is in view --}}
	<div class="lf-actions">
		<button type="button" class="lf-actions-chip d-lg-none"
		        data-bs-toggle="offcanvas" data-bs-target="#lfPanel" aria-controls="lfPanel">
			<i class="bi bi-list-check" aria-hidden="true"></i>
			<span>{{ trans('listing_form.panel_open') }}</span>
			<span class="lf-actions-chip-count" data-lf-progress-count></span>
		</button>
		<a href="{{ $actions['backUrl'] }}" class="btn btn-secondary btn-lg lf-actions-back">
			{!! $actions['backLabel'] !!}
		</a>
		<button id="{{ $actions['submitId'] }}" class="btn btn-primary btn-lg lf-actions-submit">
			{!! $actions['submitLabel'] !!}
		</button>
	</div>
@else
	<div class="col-12 mb-3 mt-5">
		<div class="row">
			<div class="col-md-6 mb-md-0 mb-2 text-start d-grid">
				<a href="{{ $actions['backUrl'] }}" class="btn btn-secondary btn-lg">
					{!! $actions['backLabel'] !!}
				</a>
			</div>
			<div class="col-md-6 mb-md-0 mb-2 text-end d-grid">
				<button id="{{ $actions['submitId'] }}" class="btn btn-primary btn-lg">
					{!! $actions['submitLabel'] !!}
				</button>
			</div>
		</div>
	</div>
@endif
