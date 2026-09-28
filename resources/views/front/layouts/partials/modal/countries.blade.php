@php
	$modalSize ??= 'modal-xl';
	$itemClass ??= '';

	$countries ??= collect();
	$countryFlagShape = config('settings.localization.country_flag_shape');
	$currentCountryCode = strtolower((string)config('country.code'));

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
<div class="modal fade country-modal" id="selectCountry" tabindex="-1" aria-labelledby="selectCountryLabel" aria-hidden="true">
	<div class="modal-dialog {{ $modalSize }} modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">

			<div class="modal-header country-modal-header">
				<h4 class="modal-title country-modal-title" id="selectCountryLabel">
					<span class="country-modal-title-icon"><i class="bi bi-globe-americas"></i></span>
					<span>
						{{ t('select_country') }}
						<small class="country-modal-count">{{ $countries->count() }}</small>
					</span>
				</h4>

				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>

			<div class="modal-body country-modal-body">
				<div id="modalCountryList" class="country-grid">
					@if ($countries->isNotEmpty())
						@foreach ($countries as $code => $country)
							@php
								$countryUrl = dmUrl($country, '/', true, !config('plugins.domainmapping.installed'));
								$countryName = $country->get('name');
								$countryNameLimited = str($countryName)->limit(24)->toString();
								$isCurrent = (strtolower((string)$code) == $currentCountryCode);
								$flag32Url = $country->get('flag32_url') ?: $country->get('flag16_url');
							@endphp
							<a href="{{ $countryUrl }}"
							   class="country-tile{{ $isCurrent ? ' is-current' : '' }} {{ $itemClass }}"
							   title="{{ $countryName }}"
							   data-name="{{ mb_strtolower($countryName) }}"
							>
								<span class="country-tile-flag">
									@if ($countryFlagShape == 'rectangle')
										<img src="{{ url('images/blank.gif') . getPictureVersion() }}"
										     class="flag flag-{{ $country->get('icode') }}"
										     alt="{{ $countryNameLimited }}"
										>
									@else
										<img src="{{ $flag32Url }}" alt="{{ $countryNameLimited }}">
									@endif
								</span>
								<span class="country-tile-name">{{ $countryNameLimited }}</span>
								@if ($isCurrent)
									<span class="country-tile-check"><i class="bi bi-check-lg"></i></span>
								@else
									<span class="country-tile-arrow"><i class="bi bi-arrow-right"></i></span>
								@endif
							</a>
						@endforeach
					@endif
				</div>
			</div>

			@if ($showCountryFlagNextLang && $multiCountryIsEnabled)
				<div class="modal-footer country-modal-footer">
					<button class="btn btn-primary country-modal-lang-btn" data-bs-target="#selectLanguage" data-bs-toggle="modal">
						<i class="bi bi-translate"></i> {{ mb_ucfirst(trans('admin.languages')) }}
					</button>
				</div>
			@endif

		</div>
	</div>
</div>

