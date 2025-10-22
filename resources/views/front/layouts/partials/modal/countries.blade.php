@php
	$modalSize ??= 'modal-xl';
	$itemClass ??= '';
	
	$countries ??= collect();
	$countryFlagShape = config('settings.localization.country_flag_shape');
	
	// Languages Selection Modal vars
	$showCountryFlagNextLang = (config('settings.localization.show_country_flag') == 'in_next_lang');
	// Check if the Multi-Countries selection is enabled
	$multiCountryIsEnabled = false;
	if ($showCountryFlagNextLang) {
		if (!empty(config('country.code'))) {
			if ($countries->count() > 1) {
				$multiCountryIsEnabled = true;
			}
		}
	}
@endphp
{{-- Countries List --}}
<div class="modal fade" id="selectCountry" tabindex="-1" aria-labelledby="selectCountryLabel" aria-hidden="true">
	<div class="modal-dialog {{ $modalSize }} modal-dialog-scrollable">
		<div class="modal-content">
			
			<div class="modal-header px-3">
				<h4 class="modal-title fs-5 fw-bold" id="selectCountryLabel">
					<i class="bi bi-geo-alt"></i> {{ t('select_country') }}
				</h4>
				
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>
			
			{{-- style="max-height: 495px; min-height: 140px;" --}}
			{{-- style="max-height: 405px; min-height: 110px;" --}}
			<div class="modal-body" style="max-height: 495px; min-height: 140px;">
				<div id="modalCountryList" class="row row-cols-xl-4 row-cols-lg-3 row-cols-2 px-lg-1 px-md-1 px-sm-3 px-3">
					
					@if ($countries->isNotEmpty())
						@foreach ($countries as $code => $country)
							<div class="col d-flex align-items-center my-1 {{ $itemClass }}">
								@php
									$countryUrl = dmUrl($country, '/', true, !config('plugins.domainmapping.installed'));
									$countryName = $country->get('name');
									$countryNameLimited = str($countryName)->limit(21)->toString();
								@endphp
								@if ($countryFlagShape == 'rectangle')
									<img src="{{ url('images/blank.gif') . getPictureVersion() }}"
										 class="flag flag-{{ $country->get('icode') }} me-2"
									     alt="{{ $countryNameLimited }}"
									>
								@else
									<img src="{{ $country->get('flag16_url') }}"
									     class="me-2"
									     alt="{{ $countryNameLimited }}"
									>
								@endif
								<a href="{{ $countryUrl }}"
								   class="link-primary text-decoration-none"
								   data-bs-toggle="tooltip"
								   data-bs-custom-class="modal-tooltip"
								   title="{{ $countryName }}"
								>
									{{ $countryNameLimited }}
								</a>
							</div>
						@endforeach
					@endif
					
				</div>
			</div>
			
			@if ($showCountryFlagNextLang && $multiCountryIsEnabled)
				<div class="modal-footer auth-login-register">
					<button class="btn btn-sm btn-primary rounded-pill px-4" data-bs-target="#selectLanguage" data-bs-toggle="modal">
						<i class="bi bi-translate"></i>  {{ mb_ucfirst(trans('admin.languages')) }}
					</button>
				</div>
			@endif
			
		</div>
	</div>
</div>
