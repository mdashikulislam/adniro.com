{{-- adniro: the form buttons; the submit button keeps its ID for package-payment.js --}}
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
