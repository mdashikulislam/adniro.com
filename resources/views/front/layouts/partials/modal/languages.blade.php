@php
	$modalSize ??= '';
	$itemClass ??= '';

	$supportedLanguagesExist ??= true;
	$showCountryFlagNextLang ??= false;
	$multiCountryIsEnabled ??= false;
	$supportedLanguages ??= [];
	$countryName ??= config('country.name');
	$countryFlagImg ??= null;

	$isCountryTitle = (!$supportedLanguagesExist && ($showCountryFlagNextLang && $multiCountryIsEnabled));
	$langCount = is_countable($supportedLanguages) ? count($supportedLanguages) : 0;
@endphp
{{-- Languages List --}}
<div class="modal fade country-modal lang-modal" id="selectLanguage" tabindex="-1" aria-labelledby="selectLanguageLabel" aria-hidden="true">
	<div class="modal-dialog {{ $modalSize }} modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">

			<div class="modal-header country-modal-header">
				<h4 class="modal-title country-modal-title" id="selectLanguageLabel">
					@if ($isCountryTitle)
						<span class="country-modal-title-icon"><i class="bi bi-geo-alt-fill"></i></span>
						<span>{{ t('country') }}</span>
					@else
						<span class="country-modal-title-icon"><i class="bi bi-translate"></i></span>
						<span>
							{{ t('language') }}
							@if ($langCount > 0)
								<small class="country-modal-count">{{ $langCount }}</small>
							@endif
						</span>
					@endif
				</h4>

				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ t('Close') }}"></button>
			</div>

			<div class="modal-body country-modal-body">
				<div id="modalBodyLanguageList" class="country-grid lang-grid">
					@if ($supportedLanguagesExist)
						@foreach($supportedLanguages as $langCode => $lang)
							@php
								// Get the language infos
								$langName = $lang['name'] ?? '--';
								$langNativeName = $lang['native'] ?? '--';
								$langTag = $lang['tag'] ?? getLangTag($langCode);
								$langFlag = $lang['flag'] ?? '';

								// Language flag
								$langFlagCountry = str_replace('flag-icon-', '', $langFlag);
								$isFlagEnabled = (
									config('settings.localization.show_languages_flags')
									&& !empty(trim($langFlag)) && is_string($langFlag)
								);

								// Is it the current language?
								$isActivatedLang = (strtolower($langCode) == strtolower(config('app.locale')));

								// Language URL & link infos
								$langUrl = url('locale/' . $langCode);
								$langTitle = ($langName != $langNativeName)
									? $langNativeName . ' - ' . $langName
									: $langName;
							@endphp
							@if ($isActivatedLang)
								<span class="country-tile lang-tile is-current {{ $itemClass }}" title="{{ $langTitle }}" lang="{{ $langTag }}">
							@else
								<a href="{{ $langUrl }}"
								   class="country-tile lang-tile {{ $itemClass }}"
								   title="{{ $langTitle }}"
								   tabindex="-1"
								   rel="alternate"
								   hreflang="{{ $langTag }}"
								   lang="{{ $langTag }}"
								>
							@endif
								<span class="country-tile-flag">
									@if ($isFlagEnabled)
										<img alt="{{ $langName }}" src="{{ getCountryFlagUrl($langFlagCountry) }}">
									@else
										<span class="lang-tile-code">{{ strtoupper(substr($langCode, 0, 2)) }}</span>
									@endif
								</span>
								<span class="country-tile-name lang-tile-name">
									<span class="lang-tile-native">{{ $langNativeName }}</span>
									@if ($langName != $langNativeName)
										<span class="lang-tile-en">{{ $langName }}</span>
									@endif
								</span>
								@if ($isActivatedLang)
									<span class="country-tile-check"><i class="bi bi-check-lg"></i></span>
								@else
									<span class="country-tile-arrow"><i class="bi bi-arrow-right"></i></span>
								@endif
							@if ($isActivatedLang)
								</span>
							@else
								</a>
							@endif
						@endforeach
					@endif
				</div>
			</div>

			@if ($showCountryFlagNextLang && $multiCountryIsEnabled)
				<div class="modal-footer country-modal-footer lang-modal-footer">
					@php
						$surfingOn = t('surfing_on', [
							'appName' => config('app.name'),
							'country' => $countryName
						]);
						$changeCountry = t('change_country');
					@endphp
					<span class="lang-modal-surfing">
						@if (!empty($countryFlagImg))
							{!! $countryFlagImg !!}
						@endif
						<span>{!! $surfingOn !!}</span>
					</span>
					<button data-bs-target="#selectCountry"
					   data-bs-toggle="modal"
					   class="btn btn-primary country-modal-lang-btn"
					   title="{{ $changeCountry }}"
					>
						<i class="bi bi-globe-americas"></i> {{ $changeCountry }}
					</button>
				</div>
			@endif

		</div>
	</div>
</div>
