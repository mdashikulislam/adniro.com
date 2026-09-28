@php
	$city ??= null;
	$admin ??= null;
	
	$adminType = config('country.admin_type', 0);
	$adminCode = data_get($city, 'subadmin' . $adminType . '_code') ?? data_get($admin, 'code') ?? 0;
@endphp
{{-- Modal Select Category --}}
<div class="modal fade country-modal category-modal" id="browseCategories" tabindex="-1" aria-labelledby="categoriesModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			
			<div class="modal-header country-modal-header">
				<h4 class="modal-title country-modal-title" id="categoriesModalLabel">
					<span class="country-modal-title-icon"><i class="bi bi-grid-fill"></i></span>
					<span>{{ t('select_a_category') }}</span>
				</h4>
				
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>
			
			<div class="modal-body country-modal-body">
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
		
		{{-- Open the categories modal when clicking anywhere on the category box --}}
		onDocumentReady((event) => {
			document.addEventListener('click', (e) => {
				const box = e.target.closest('#catsContainer');
				if (!box || e.target.closest('a')) return;
				const link = box.querySelector('a.modal-cat-link:last-of-type') || box.querySelector('a.modal-cat-link');
				if (link) {
					e.preventDefault();
					link.click();
				}
			});
		});
	</script>
@endsection
