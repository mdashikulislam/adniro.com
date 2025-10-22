@php
	$admin ??= null;
	$city ??= null;
	
	$adminType = config('country.admin_type', 0);
	$relAdminType = (in_array($adminType, ['1', '2'])) ? $adminType : 1;
	$adminCode = data_get($city, 'subadmin' . $relAdminType . '_code') ?? data_get($admin, 'code') ?? 0;
	
	$inputs = request()->all();
	$currSearch = base64_encode(serialize($inputs));
@endphp
{{-- Modal Select City --}}
<div class="modal fade" id="browseLocations" tabindex="-1" aria-labelledby="browseLocationsLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title fs-5 fw-bold" id="browseLocationsLabel">
					<i class="fa-regular fa-map"></i> {{ t('select_a_location') }}
				</h4>
				
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>
			
			<div class="modal-body">
				<div class="row">
					<div class="col-12">
						<div id="locationsTitle" class="mb-3">
							{!! t('locations_in_country', ['country' => config('country.name')]) !!}
						</div>
						
						<div class="col-12 border-bottom p-0 pb-3 mb-3">
							<form id="locationsModalForm" method="POST">
								@csrf
								
								<input type="hidden" id="modalCountryChanged" name="country_changed" value="0">
								<input type="hidden" id="modalTriggerName" name="trigger_name" value="">
								<input type="hidden" id="modalUrl" name="url" value="">
								<input type="hidden" id="modalAdminType" name="admin_type" value="{{ $adminType }}">
								<input type="hidden" id="modalAdminCode" name="admin_code" value="">
								<input type="hidden" id="currSearch" name="curr_search" value="{!! $currSearch !!}">
								
								<div class="row g-3">
									<div class="col-sm-12 col-md-11 col-lg-10">
										<div class="input-group position-relative d-inline-flex align-items-center">
											<input type="text"
												   id="modalQuery"
												   name="query"
												   class="form-control input-md"
												   placeholder="{{ t('search_a_location') }}"
												   aria-label="{{ t('search_a_location') }}"
												   value=""
												   autocomplete="off"
											>
											<span class="input-group-text">
												<i id="modalQueryClearBtn" class="bi bi-x-lg" style="cursor: pointer;"></i>
											</span>
										</div>
									</div>
									<div class="col-sm-12 col-md-3 col-lg-2 d-grid">
										<button id="modalQuerySearchBtn" class="btn btn-primary"> {{ t('find') }} </button>
									</div>
								</div>
							</form>
						</div>
					</div>
					
					<div class="col-12" id="locationsList"></div>
				</div>
			</div>
			
		</div>
	</div>
</div>

@section('after_scripts')
	@parent
	<script>
		{{-- Modal Default Admin1 Code --}}
		var defaultAdminType = '{{ $adminType }}';
		var defaultAdminCode = '{{ $adminCode }}';
	</script>
	<script src="{{ url('assets/js/app/browse.locations.js') . vTime() }}"></script>
@endsection
