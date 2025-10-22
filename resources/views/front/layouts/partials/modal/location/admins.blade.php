@php
	$countryCode ??= config('country.code');
	$countryCode = strtolower($countryCode);
	$adminType ??= 0;
	
	$apiResult ??= [];
	$admins = data_get($apiResult, 'data');
	$totalAdmins = (int)data_get($apiResult, 'meta.total', 0);
	$areAdminsPageable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	
	$languageCode ??= config('app.locale');
	$currSearch ??= [];
	
	$queryArray = is_array($currSearch) ? $currSearch : [];
	$adminQueryArray = $queryArray;
	if (isset($adminQueryArray['distance'])) {
		unset($adminQueryArray['distance']);
	}
@endphp
@if (!empty($admins) && $totalAdmins > 0)
	@php
		$rowCols = ($adminType == 2)
			? 'row-cols-lg-3 row-cols-md-2 row-cols-sm-1'
			: 'row-cols-lg-4 row-cols-md-3 row-cols-sm-2';
	@endphp
	<div id="modalAdminList" class="row {{ $rowCols }} row-cols-1">
		@php
			$url = url('browsing/locations/' . $countryCode . '/cities');
			$url = urlBuilder($url)->setParameters($adminQueryArray)->toString();
		@endphp
		<div class="col mb-1 list-link list-unstyled">
			<a href="" data-url="{{ $url }}" class="is-admin link-primary text-decoration-none" data-ignore-guard="true">
				{{ t('all_cities', [], 'global', $languageCode) }}
			</a>
		</div>
		@foreach($admins as $admin)
			@php
				$adminCode = data_get($admin, 'code');
				$url = url('browsing/locations/' . $countryCode . '/admins/' . $adminType . '/' . $adminCode . '/cities');
				$url = urlBuilder($url)->setParameters($adminQueryArray)->toString();
				
				$admin1 = null;
				$adminName = data_get($admin, 'name');
				if ($adminType == 2) {
					$admin1 = data_get($admin, 'subAdmin1');
					$admin1Name = data_get($admin1, 'name');
					$fullAdminName = !empty($admin1Name) ? $adminName . ', ' . $admin1Name : $adminName;
				} else {
					$fullAdminName = $adminName;
				}
			@endphp
			<div class="col mb-1 list-link list-unstyled">
				<a href=""
				   data-url="{{ $url }}"
				   class="is-admin link-primary text-decoration-none"
				   data-bs-toggle="tooltip"
				   data-bs-custom-class="modal-tooltip"
				   title="{{ $fullAdminName }}"
				   data-ignore-guard="true"
				>
					{{ $fullAdminName }}
				</a>
			</div>
		@endforeach
	</div>
	@if ($areAdminsPageable)
		<div class="row">
			<div class="col-12">
				@include('vendor.pagination.ajax.bootstrap-5')
			</div>
		</div>
	@endif
@else
	<div class="row">
		<div class="col-12">
			{{ t('no_admin_divisions_found', [], 'global', $languageCode) }}
		</div>
	</div>
@endif
