@php
	$sectionOptions = $searchFormOptions ?? [];
	
	// Get Search Form Options
	$enableFormAreaCustomization = $sectionOptions['enable_extended_form_area'] ?? '0';
	$fullHeight = $sectionOptions['full_height'] ?? '0';
	$isFullHeightEnabled = ($fullHeight == '1');
	$scrollCue = $isFullHeightEnabled ? ($sectionOptions['scroll_cue'] ?? null) : null;
	$style = $isFullHeightEnabled ? 'height: 100vh; min-height: 100dvh;' : '';
	$parallax = $sectionOptions['parallax'] ?? '0';
	$locale = config('app.locale');
	
	$hideTitle = $sectionOptions['hide_title'] ?? '0';
	$headerTitle = $sectionOptions["title_{$locale}"] ?? null;
	$headerTitle = !empty($headerTitle) ? replaceGlobalPatterns($headerTitle) : null;
	$titleHtmlAttr = $sectionOptions['title_html_attributes'] ?? '';
	$titleHtmlAttr = !empty($titleHtmlAttr) ? " $titleHtmlAttr" : '';
	
	$hideSubTitle = $sectionOptions['hide_subtitle'] ?? '0';
	$headerSubTitle = $sectionOptions["sub_title_{$locale}"] ?? null;
	$headerSubTitle = !empty($headerSubTitle) ? replaceGlobalPatterns($headerSubTitle) : null;
	$subtitleHtmlAttr = $sectionOptions['subtitle_html_attributes'] ?? '';
	$subtitleHtmlAttr = !empty($subtitleHtmlAttr) ? " $subtitleHtmlAttr" : '';
	
	$hideSearchBar = $sectionOptions['hide_searchbar'] ?? '0';
	$searchBarHtmlAttr = $sectionOptions['searchbar_html_attributes'] ?? '';
	$searchBarHtmlAttr = !empty($searchBarHtmlAttr) ? " $searchBarHtmlAttr" : '';
	
	$isAutocompleteEnabled = (config('settings.listings_list.enable_cities_autocompletion') == '1');
	$autocompleteClass = $isAutocompleteEnabled ? ' autocomplete-enabled' : '';
	
	$statesSearchTip = t('states_search_tip', ['prefix' => t('area'), 'suffix' => t('state_name')]);
	$displayStatesSearchTip = config('settings.listings_list.display_states_search_tip');
	$searchTooltip = $displayStatesSearchTip
		? ' data-bs-placement="top" data-bs-toggle="tooltipHover" title="' . $statesSearchTip . '"'
		: '';
	
	$cssClasses = $sectionOptions['css_classes'] ?? '';
	$cssClasses = !empty($cssClasses) ? " {$cssClasses}" : '';
@endphp
@if (isset($enableFormAreaCustomization) && $enableFormAreaCustomization == '1')
	@php
		$parallaxClass = ($parallax == '1') ? ' parallax' : '';
	@endphp
	<div class="hero-wrap bg-secondary d-flex align-items-center{{ $cssClasses . $parallaxClass }}" style="{!! $style !!}">
		<div class="container text-center">
			
			@if ($hideTitle != '1')
				<h1 class="text-uppercase fw-bold text-white text-shadow"{!! $titleHtmlAttr !!}>
					{{ $headerTitle }}
				</h1>
			@endif
			@if ($hideSubTitle != '1')
				<h5 class="fs-4 lead text-white text-shadow mb-3"{!! $subtitleHtmlAttr !!}>
					{!! $headerSubTitle !!}
				</h5>
			@endif
			
			@if ($hideSearchBar != '1')
				<div class="row d-flex justify-content-center"{!! $searchBarHtmlAttr !!}>
					<div class="col-9">
						<form id="searchForm"
						      name="search"
						      action="{{ urlGen()->searchWithoutQuery() }}"
						      method="GET"
						      class="home-search-form"
						      data-csrf-token="{{ csrf_token() }}"
						>
							<div class="w-100">
								@include('front.sections.home.search-form.form-fields')
							</div>
						</form>
					</div>
				</div>
			@endif
			
		</div>
		
		@include('front.layouts.partials.tools.scroll-cue')
	</div>
	
@else
	
	<div class="d-flex align-items-center only-search-bar{{ $cssClasses }}">
		<div class="container text-center">
			
			<div class="row d-flex justify-content-center px-2">
				<div class="col-12">
					<form id="searchForm"
					      name="search"
					      action="{{ urlGen()->searchWithoutQuery() }}"
					      method="GET"
					      class="home-search-form"
					      data-csrf-token="{{ csrf_token() }}"
					>
						<div class="w-100">
							@include('front.sections.home.search-form.form-fields')
						</div>
					</form>
				</div>
			</div>
			
		</div>
	</div>
	
@endif
