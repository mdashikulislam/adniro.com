@php
	$searchFormOptions = $searchFormOptions ?? [];
	$locationsOptions = $locationsOptions ?? [];
@endphp
<style>
/* === Homepage: Search Form Area === */
@if (!empty($searchFormOptions['height']))
	@php
        $searchFormOptions['height'] = forceToInt($searchFormOptions['height']) . 'px';
    @endphp
	#homepage .hero-wrap:not(.only-search-bar) {
		height: {{ $searchFormOptions['height'] }};
		max-height: {{ $searchFormOptions['height'] }};
	}
@endif
@if (!empty($searchFormOptions['background_color']))
	#homepage .hero-wrap:not(.only-search-bar) {
		background-color: {{ $searchFormOptions['background_color'] }} !important;
	}
@endif
@php
	$bgImgFound = false;
	$bgImgDarken = data_get($searchFormOptions, 'background_image_darken', 0.0);
@endphp
@if (!empty(config('country.background_image_url')))
	#homepage .hero-wrap:not(.only-search-bar) {
		background-image: linear-gradient(rgba(0, 0, 0, {{ $bgImgDarken }}),rgba(0, 0, 0, {{ $bgImgDarken }})),url({{ config('country.background_image_url') }}) !important;
		background-size: cover;
	}
	@php
		$bgImgFound = true;
	@endphp
@endif
@if (!$bgImgFound)
	@if (!empty($searchFormOptions['background_image_url']))
		#homepage .hero-wrap:not(.only-search-bar) {
			background-image: linear-gradient(rgba(0, 0, 0, {{ $bgImgDarken }}),rgba(0, 0, 0, {{ $bgImgDarken }})),url({{ $searchFormOptions['background_image_url'] }}) !important;
			background-size: cover;
		}
	@endif
@endif
@if (!empty($searchFormOptions['big_title_color']))
	#homepage .hero-wrap:not(.only-search-bar) h1,
	#homepage .hero-wrap:not(.only-search-bar) h1.text-white {
		color: {{ $searchFormOptions['big_title_color'] }} !important;
	}
@endif
@if (!empty($searchFormOptions['sub_title_color']))
	#homepage .hero-wrap:not(.only-search-bar) h5,
	#homepage .hero-wrap:not(.only-search-bar) h5.text-white {
		color: {{ $searchFormOptions['sub_title_color'] }} !important;
	}
@endif

{{-- Search Form Hero --}}
@if (!empty($searchFormOptions['form_border_width']))
	@php
		$formBorderWidth = forceToInt($searchFormOptions['form_border_width']);
		$formBtnBorderWidth = abs($formBorderWidth - 1);
		$searchFormOptions['form_border_width'] = $formBorderWidth . 'px';
		$formBtnBorderWidthPx = $formBtnBorderWidth . 'px';
	@endphp
	#homepage .search-row .search-col:first-child > div,
	#homepage .search-row .search-col > div {
		border-width: {{ $searchFormOptions['form_border_width'] }} !important;
	}
	#homepage .search-row .search-col button {
		border-width: {{ $formBtnBorderWidthPx }} !important;
	}
	
	@media (max-width: 767px) {
		.search-row .search-col:first-child > div,
		.search-row .search-col > div {
			border-width: {{ $searchFormOptions['form_border_width'] }} !important;
		}
		.search-row .search-col button {
			border-width: {{ $formBtnBorderWidthPx }} !important;
		}
	}
@endif
@php
	$origFormBorderRadius = $searchFormOptions['form_border_radius'] ?? null;
	$origFormBorderRadius = (ctype_digit($origFormBorderRadius) || is_int($origFormBorderRadius)) ? forceToInt($origFormBorderRadius) : null;
	
	$formBorderRadius = 24;
	$fieldsBorderRadius = 24;
	if (is_int($origFormBorderRadius)) {
		$formBorderRadius = $origFormBorderRadius;
		
		// Based on default radius
		$fieldsBorderRadius = (int)round((($formBorderRadius * 18) / 24));
		
		// Based on the default radius & default border width
		if (!empty($searchFormOptions['form_border_width'])) {
			$formBorderWidth = forceToInt($searchFormOptions['form_border_width']);
			
			// Get the difference between the default wrapper & the fields radius, based on the default border width
			$borderRadiusDiff = (24 - 18) / 5;
			
			// Apply the diff. obtained above to the customized wrapper radius to get the fields radius
			$fieldsBorderRadius = (int)round(($formBorderRadius - $borderRadiusDiff));
		}
	}
	
	// $formBorderRadiusOut = getFormBorderRadiusCSS($formBorderRadius, $fieldsBorderRadius);
	$formBorderRadiusOut = getFormBorderRadiusCSS($formBorderRadius, $formBorderRadius);
@endphp

{!! $formBorderRadiusOut !!}

@if (!empty($searchFormOptions['form_border_color']))
	#homepage .search-row .search-col:first-child > div,
	#homepage .search-row .search-col > div,
	#homepage .search-row .search-col button {
		border-color: {{ $searchFormOptions['form_border_color'] }} !important;
	}
	
	@media (max-width: 767px) {
		#homepage .search-row .search-col:first-child > div,
		#homepage .search-row .search-col > div,
		#homepage .search-row .search-col button {
			border-color: {{ $searchFormOptions['form_border_color'] }} !important;
		}
	}
@endif
@if (!empty($searchFormOptions['form_btn_background_color']))
	.skin #homepage .search-row .search-col button {
		background-color: {{ $searchFormOptions['form_btn_background_color'] }} !important;
		border-color: {{ $searchFormOptions['form_btn_background_color'] }} !important;
	}
@endif
@if (!empty($searchFormOptions['form_btn_text_color']))
	.skin #homepage .search-row .search-col button {
		color: {{ $searchFormOptions['form_btn_text_color'] }} !important;
	}
@endif
@if (!empty(config('settings.style.page_width')))
	@php
		$pageWidth = forceToInt(config('settings.style.page_width')) . 'px';
	@endphp
	@media (min-width: 1200px) {
		#homepage .hero-wrap.only-search-bar .container {
			max-width: {{ $pageWidth }};
		}
	}
@endif

/* === Homepage: Locations & SVG Map === */
@if (!empty($locationsOptions['background_color']))
	#homepage .location-card .card.bg-body-tertiary {
		background-color: {{ $locationsOptions['background_color'] }} !important;
	}
@endif
@if (!empty($locationsOptions['border_width']))
	@php
		$locationsOptions['border_width'] = forceToInt($locationsOptions['border_width']) . 'px';
	@endphp
	#homepage .location-card .card {
		border-width: {{ $locationsOptions['border_width'] }};
	}
@endif
@if (!empty($locationsOptions['border_color']))
	#homepage .location-card .card {
		border-color: {{ $locationsOptions['border_color'] }};
	}
@endif
@if (!empty($locationsOptions['text_color']))
	#homepage .location-card .card,
	#homepage .location-card .card p,
	#homepage .location-card .card h1,
	#homepage .location-card .card h2,
	#homepage .location-card .card h3,
	#homepage .location-card .card h4,
	#homepage .location-card .card h5 {
		color: {{ $locationsOptions['text_color'] }};
	}
@endif
@if (!empty($locationsOptions['link_color']))
	#homepage .location-card .card a:not(.btn),
	#homepage .location-card .card a.link-body-emphasis {
		color: {{ $locationsOptions['link_color'] }} !important;
	}
@endif
@if (!empty($locationsOptions['link_hover_color']))
	#homepage .location-card .card a:not(.btn):hover,
	#homepage .location-card .card a:not(.btn):focus,
	#homepage .location-card .card a.link-body-emphasis:hover,
	#homepage .location-card .card a.link-body-emphasis:focus {
		color: {{ $locationsOptions['link_hover_color'] }} !important;
	}
@endif
</style>
