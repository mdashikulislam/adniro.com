@php
	$city ??= null;
	$admin ??= null;
	
	$adminType = config('country.admin_type', 0);
	$adminCode = data_get($city, 'subadmin' . $adminType . '_code') ?? data_get($admin, 'code') ?? 0;
@endphp
{{-- Modal Select Category --}}
<div class="modal fade" id="browseCategories" tabindex="-1" aria-labelledby="categoriesModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title fs-5 fw-bold" id="categoriesModalLabel">
					<i class="bi bi-folder-check"></i> {{ t('select_a_category') }}
				</h4>
				
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>
			
			<div class="modal-body">
				<div class="p-0 m-0" id="selectCats"></div>
			</div>
			
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	<script>
		var editLabel = '{{ t('Edit') }}';
		
		{{-- Modal Default Admin. Code --}}
		var defaultAdminType = '{{ $adminType }}';
		var defaultAdminCode = '{{ $adminCode }}';
	</script>
@endsection
