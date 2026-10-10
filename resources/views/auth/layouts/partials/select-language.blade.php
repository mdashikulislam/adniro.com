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
	<div class="auth-prefs">
		<div class="auth-prefs-theme">
			@if (isSettingsAppDarkModeEnabled())
				@include('front.layouts.partials.navs.themes', [
					'showIconOnly' => $showIconOnly,
				])
			@endif
		</div>
		@if ($isLangOrCountryCanBeSelected)
			<a href="#selectLanguage"
			   role="button"
			   data-bs-toggle="modal"
			   class="auth-prefs-lang auto-tooltip"
			   title="{{ t('change_language') }}"
			>
				<i class="bi bi-translate"></i> {{ $currentLanguageName }} <i class="bi bi-chevron-expand"></i>
			</a>
		@endif
	</div>
@endif

@section('modal')
	@include('front.layouts.partials.modal.languages', ['modalSize' => 'modal-lg', 'itemClass' => 'text-center'])
@endsection
