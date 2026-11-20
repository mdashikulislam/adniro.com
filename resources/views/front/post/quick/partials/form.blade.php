{{-- Quick post composer form (adniro:ai) --}}
					<div class="border rounded bg-body-tertiary p-4 mb-4" id="quickPost">
	<h1 class="h3 fw-bold mb-1"><i class="bi bi-stars"></i> {{ trans('ai.qp_title') }}</h1>
	<p class="text-muted mb-4">{{ trans('ai.qp_intro') }}</p>
	
	<form id="quickPostForm" novalidate>
		<label for="qpText" class="form-label fw-bold">{{ trans('ai.qp_text_label') }}</label>
		<textarea id="qpText"
		          name="text"
		          class="form-control form-control-lg mb-2"
		          rows="5"
		          maxlength="{{ $maxText }}"
		          placeholder="{{ trans('ai.qp_text_placeholder') }}"
		          autofocus
		>{{ $redraftText ?? '' }}</textarea>
		
		<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
			<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="qpLocation">
				<i class="bi bi-geo-alt"></i> <span>{{ trans('ai.qp_use_location') }}</span>
			</button>
			<span class="small text-muted ms-auto" id="qpCounter">0 / {{ $maxText }}</span>
		</div>
		
		<div class="alert alert-warning d-none" id="qpMessage" role="alert"></div>
		
		<div class="d-grid mb-3">
			<button type="submit" class="btn btn-primary btn-lg" id="qpSubmit" disabled>
				<i class="bi bi-magic"></i> {{ trans('ai.qp_submit') }}
			</button>
		</div>
		
		<div class="d-flex flex-wrap justify-content-between gap-2 small">
			<span class="text-muted" id="qpLimit">
				@if ($limit->reached())
					{{ trans('ai.qp_limit_reached', ['when' => $limit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL')]) }}
				@elseif (!empty($limitText))
					{{ $limitText }}
				@endif
			</span>
			<a href="{{ $formUrl }}" class="fw-bold">{{ trans('ai.qp_fill_myself') }}</a>
		</div>
	</form>
	
	<p class="small text-muted mt-4 mb-0">{{ trans('ai.qp_privacy') }}</p>
</div>
