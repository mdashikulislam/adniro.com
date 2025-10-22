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
		? '<img class="flag-icon" src="' . $countryFlag32Url . '" alt="' . $countryName . '">'
		: null;
	
	// Links CSS Class
	$defaultLinkColorClass ??= linkClass('body-emphasis');
	$defaultLinkColorClass = !empty($defaultLinkColorClass) ? ' ' . trim($defaultLinkColorClass) : '';
	
	$defaultHeaderItemShadowClass ??= '';
	$itemShadowClass = !empty($defaultHeaderItemShadowClass) ? ' ' . trim($defaultHeaderItemShadowClass) : '';
@endphp

@if ($isLangOrCountryCanBeSelected)
	<li class="nav-item dropdown">
		<a href="#selectLanguage"
		   class="nav-link dropdown-toggle{{ $defaultLinkColorClass . $itemShadowClass }}"
		   role="button"
		   data-bs-toggle="modal"
		>
			@if (!empty($countryFlagImg))
				<span>
					{!! $countryFlagImg !!}<span class="d-none d-xl-inline-block ms-0 ms-xl-1">{{ strtoupper(config('app.locale')) }}</span>
				</span>
			@else
				<span><i class="bi bi-translate"></i></span>
			@endif
			<span class="d-inline-block d-xl-none ms-1">{{ t('language') }}</span>
		</a>
	</li>
@endif

@section('modal_languages')
	@include('front.layouts.partials.modal.languages')
@endsection
