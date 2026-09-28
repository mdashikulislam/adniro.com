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
<div class="modal fade country-modal location-modal" id="browseLocations" tabindex="-1" aria-labelledby="browseLocationsLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			
			<div class="modal-header country-modal-header">
				<h4 class="modal-title country-modal-title" id="browseLocationsLabel">
					<span class="country-modal-title-icon"><i class="bi bi-geo-alt-fill"></i></span>
					<span>{{ t('select_a_location') }}</span>
				</h4>
				
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>
			
			<div class="modal-body country-modal-body">
				<div class="row">
					<div class="col-12">
						<div id="locationsTitle" class="location-modal-title">
							{!! t('locations_in_country', ['country' => config('country.name')]) !!}
						</div>
						
						<div class="col-12 location-modal-search">
							<form id="locationsModalForm" method="POST">
								@csrf
								
								<input type="hidden" id="modalCountryChanged" name="country_changed" value="0">
								<input type="hidden" id="modalTriggerName" name="trigger_name" value="">
								<input type="hidden" id="modalUrl" name="url" value="">
								<input type="hidden" id="modalAdminType" name="admin_type" value="{{ $adminType }}">
								<input type="hidden" id="modalAdminCode" name="admin_code" value="">
								<input type="hidden" id="currSearch" name="curr_search" value="{!! $currSearch !!}">
								
								<div class="location-modal-search-row">
									<div class="location-modal-field">
										<i class="bi bi-search"></i>
										<input type="text"
											   id="modalQuery"
											   name="query"
											   class="form-control"
											   placeholder="{{ t('search_a_location') }}"
											   aria-label="{{ t('search_a_location') }}"
											   value=""
											   autocomplete="off"
										>
										<span class="location-modal-clear">
											<i id="modalQueryClearBtn" class="bi bi-x-lg" style="cursor: pointer;"></i>
										</span>
									</div>
									<button id="modalQuerySearchBtn" class="btn btn-primary location-modal-find">
										<i class="bi bi-search"></i> {{ t('find') }}
									</button>
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
