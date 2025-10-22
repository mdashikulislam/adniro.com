{{-- Show AJAX Errors (for JS) --}}
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title fs-5 fw-bold" id="errorModalLabel">
					{{ t('error_found') }}
				</h4>
				
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>
			
			<div class="modal-body">
				<div class="row">
					<div id="errorModalBody" class="col-12">
						...
					</div>
				</div>
			</div>
			
			<div class='modal-footer'>
				<button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ t('Close') }}</button>
			</div>
			
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
@endsection
