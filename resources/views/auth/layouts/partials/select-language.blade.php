@php
	/*
	 * The languages selection nav-item view variables are also used in the languages modal view
	 * available at: ../modal/languages.blade.php
	 */
	 
	$countries ??= collect();
	$showCountryFlagNextLang = (config('settings.localization.show_country_flag') == 'in_next_lang');
	
	$showCountrySpokenLang = config('settings.localization.show_country_spoken_languages');
	$showCountrySpokenLang = str_starts_with($showCountrySpokenLang, 'active');
	$supportedLanguages = $showCountrySpokenLang ? getCountrySpokenLanguages() : getSupportedLanguages();
	
	$supportedLanguagesExist = (count($supportedLanguages) > 1);
	$isLangOrCountryCanBeSelected = ($supportedLanguagesExist || $showCountryFlagNextLang);
	
	// Check if the Multi-Countries selection is enabled
	$multiCountryIsEnabled = false;
	$multiCountryLabel = '';
	if ($showCountryFlagNextLang) {
		if (!empty(config('country.code'))) {
			if ($countries->count() > 1) {
				$multiCountryIsEnabled = true;
			}
		}
	}
	
	$countryName = config('country.name');
	$countryFlag32Url = config('country.flag32_url');
	
	$countryFlagImg = $showCountryFlagNextLang
		? '<img class="flag-icon me-2" src="' . $countryFlag32Url . '" alt="' . $countryName . '">'
		: null;
	
	$currentLanguageCode = strtolower(config('app.locale', 'en'));
	$currentLanguage = $supportedLanguages[$currentLanguageCode] ?? strtoupper($currentLanguageCode);
	$currentLanguageName = $currentLanguage['name'] ?? strtoupper($currentLanguageCode);
	$currentLanguageName = $currentLanguage['native'] ?? $currentLanguageName;
	
	// Theme Preference (light/dark/system)
	$showIconOnly ??= false;
@endphp
@if ($isLangOrCountryCanBeSelected || isSettingsAppDarkModeEnabled())
	<div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-11 col-xxl-10 mx-auto mt-5">
		<div class="row d-flex justify-content-center align-items-end">
			<div class="col-md-6 col-sm-12 text-md-start text-center">
				@if (isSettingsAppDarkModeEnabled())
					@include('front.layouts.partials.navs.themes', [
						'showIconOnly' => $showIconOnly,
					])
				@endif
			</div>
			<div class="col-md-6 col-sm-12 text-md-end text-center">
				@if ($isLangOrCountryCanBeSelected)
					<div class="">
						<a href="#selectLanguage"
						   role="button"
						   data-bs-toggle="modal"
						   class="text-secondary auto-tooltip"
						   title="{{ t('change_language') }}"
						>
							<i class="bi bi-translate"></i> {{ $currentLanguageName }} <i class="bi bi-chevron-expand"></i>
						</a>
					</div>
				@endif
			</div>
		</div>
	</div>
@endif

@section('modal')
	@include('front.layouts.partials.modal.languages', ['modalSize' => 'modal-lg', 'itemClass' => 'text-center'])
@endsection
