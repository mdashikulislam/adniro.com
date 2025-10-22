@php
	// Logo
	$logoFactoryUrl = config('larapen.media.logo-factory');
	$logoDarkUrl = config('settings.app.logo_dark_url', $logoFactoryUrl);
	$logoLightUrl = config('settings.app.logo_light_url', $logoFactoryUrl);
	$logoAlt = strtolower(config('settings.app.name'));
	$logoWidth = (int)config('settings.upload.img_resize_logo_width', 454);
	$logoHeight = (int)config('settings.upload.img_resize_logo_height', 80);
	$logoStyle = "max-width:{$logoWidth}px !important; max-height:{$logoHeight}px !important; width:auto !important;";
	
	// Theme Preference (light/dark/system)
	$showIconOnly ??= false;
	
	// Fallback Navbar Parameters
	$fallbackHeight = 80;
	$fallbackNavbarClass = 'fixed-top navbar-sticky bg-body-tertiary border-bottom';
	$fallbackContainerClass = 'container';
	$textShadowClass = 'text-shadow';
	
	$defaultHeight = forceToInt(config('settings.header.default_height'), $fallbackHeight);
	$defaultStyle = "min-height: {$defaultHeight}px";
@endphp
@php
	// Navbar Parameters
	$isDefaultHeaderAnimationEnabled = (config('settings.header.default_animation') == '1');
	$isFixedHeaderEnabled = (config('settings.header.fixed_top') == '1');
	$navbarFixedHeightOffset = config('settings.header.fixed_height_offset');
	$navbarFixedHeightOffset = (!empty($navbarFixedHeightOffset) && is_numeric($navbarFixedHeightOffset)) ? $navbarFixedHeightOffset : 'null';
	
	$isDefaultHeaderDarkThemeEnabled = (config('settings.header.default_dark') == '1');
	$defaultCssClasses = config('settings.header.default_css_classes');
	$defaultCssClasses = !empty($defaultCssClasses) ? $defaultCssClasses : $fallbackNavbarClass;
	$defaultContainerCssClasses = config('settings.header.default_container_css_classes');
	$defaultContainerCssClasses = !empty($defaultContainerCssClasses) ? $defaultContainerCssClasses : $fallbackContainerClass;
	$defaultBgColor = config('settings.header.default_background_color');
	$defaultBorderColor = config('settings.header.default_border_color');
	$defaultLinkColorClass = config('settings.header.default_link_color_class');
	$defaultLinkColorClass = !empty($defaultLinkColorClass) ? " $defaultLinkColorClass" : '';
	$defaultLinkColor = config('settings.header.default_link_color');
	$defaultLinkHoverColor = config('settings.header.default_link_hover_color');
	$defaultTextColorClass = config('settings.header.default_text_color_class');
	$defaultTextColorClass = !empty($defaultTextColorClass) ? " $defaultTextColorClass" : '';
	$defaultTextColor = config('settings.header.default_text_color');
	$isDefaultHeaderItemShadowEnabled = (config('settings.header.default_item_shadow') == '1');
	
	$isFixedHeaderDarkThemeEnabled = (config('settings.header.fixed_dark') == '1');
	$fixedHeight = forceToInt(config('settings.header.fixed_height'), $defaultHeight);
	$fixedCssClasses = config('settings.header.fixed_css_classes');
	$fixedContainerCssClasses = config('settings.header.fixed_container_css_classes');
	$fixedContainerCssClasses = !empty($fixedContainerCssClasses) ? $fixedContainerCssClasses : $fallbackContainerClass;
	$fixedBgColor = config('settings.header.fixed_background_color');
	$fixedBorderColor = config('settings.header.fixed_border_color');
	$fixedLinkClass = config('settings.header.fixed_link_color_class');
	$fixedLinkColor = config('settings.header.fixed_link_color');
	$fixedLinkHoverColor = config('settings.header.fixed_link_hover_color');
	$fixedTextColorClass = config('settings.header.fixed_text_color_class');
	$fixedTextColor = config('settings.header.fixed_text_color');
	$isFixedHeaderItemShadowEnabled = (config('settings.header.static_item_shadow') == '1');
	
	$defaultExpandedBgColorClass = config('settings.header.default_expanded_background_color_class');
	$defaultExpandedLinkColorClass = config('settings.header.default_expanded_link_color_class');
	$defaultExpandedTextColorClass = config('settings.header.default_expanded_text_color_class');
	
	// Other Navbar Vars
	$defaultHeaderThemeAttr = $isDefaultHeaderDarkThemeEnabled ? ' data-bs-theme="dark"' : '';
	$defaultHeaderItemShadowClass = $isDefaultHeaderItemShadowEnabled ? " {$textShadowClass}" : '';
@endphp
@pushonce('before_scripts_stack')
	<script>
		if (typeof window.headerOptions === 'undefined') {
			window.headerOptions = {};
		}
		window.headerOptions = {
			animationEnabled: {{ $isDefaultHeaderAnimationEnabled ? 'true' : 'false' }},
			navbarHeightOffset: {{ $navbarFixedHeightOffset }},
			default: {
				darkThemeEnabled: {{ $isDefaultHeaderDarkThemeEnabled ? 'true' : 'false' }},
				height: {{ $defaultHeight }},
				cssClasses: '{{ $defaultCssClasses }}',
				containerCssClasses: '{{ $defaultContainerCssClasses }}',
				bgColor: '{{ $defaultBgColor }}',
				borderColor: '{{ $defaultBorderColor }}',
				linkColorClass: '{{ $defaultLinkColorClass }}',
				linkColor: '{{ $defaultLinkColor }}',
				linkHoverColor: '{{ $defaultLinkHoverColor }}',
				textColorClass: '{{ $defaultTextColorClass }}',
				textColor: '{{ $defaultTextColor }}',
				itemShadowClass: '{{ $isDefaultHeaderItemShadowEnabled ? $textShadowClass : '' }}',
			},
			fixed: {
				enabled: {{ $isFixedHeaderEnabled ? 'true' : 'false' }},
				darkThemeEnabled: {{ $isFixedHeaderDarkThemeEnabled ? 'true' : 'false' }},
				height: {{ $fixedHeight }},
				cssClasses: '{{ $fixedCssClasses }}',
				containerCssClasses: '{{ $fixedContainerCssClasses }}',
				bgColor: '{{ $fixedBgColor }}',
				borderColor: '{{ $fixedBorderColor }}',
				linkColorClass: '{{ $fixedLinkClass }}',
				linkColor: '{{ $fixedLinkColor }}',
				linkHoverColor: '{{ $fixedLinkHoverColor }}',
				textColorClass: '{{ $fixedTextColorClass }}',
				textColor: '{{ $fixedTextColor }}',
				itemShadowClass: '{{ $isFixedHeaderItemShadowEnabled ? $textShadowClass : '' }}',
			},
			expandedBgColorClass: '{{ $defaultExpandedBgColorClass }}',
			expandedLinkColorClass: '{{ $defaultExpandedLinkColorClass }}',
			expandedTextColorClass: '{{ $defaultExpandedTextColorClass }}',
		};
	</script>
@endpushonce
<header{!! $defaultHeaderThemeAttr !!}>
	<nav class="navbar {{ $defaultCssClasses }} navbar-expand-md" role="navigation" id="mainNavbar" style="{{ $defaultStyle }}">
		<div class="{{ $defaultContainerCssClasses }}" id="mainNavbarContainer">
			
			{{-- Logo --}}
			<a href="{{ url('/') }}" class="navbar-brand logo logo-title">
				<img src="{{ $logoDarkUrl }}"
				     alt="{{ $logoAlt }}"
				     class="main-logo dark-logo"
				     style="{!! $logoStyle !!}"
				/>
				<img src="{{ $logoLightUrl }}"
				     alt="{{ $logoAlt }}"
				     class="main-logo light-logo"
				     style="{!! $logoStyle !!}"
				/>
			</a>
			
			{{-- Toggle Nav (Mobile) --}}
			<button class="navbar-toggler float-end"
			        type="button"
			        data-bs-toggle="collapse"
			        data-bs-target="#navbarNav"
			        aria-controls="navbarNav"
			        aria-expanded="false"
			        aria-label="Toggle navigation"
			>
				<span class="navbar-toggler-icon"></span>
			</button>
			
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav me-md-auto">
					{{----}}
				</ul>
				
				<ul class="navbar-nav ms-auto">
					{{-- Dark/Light Mode Dropdown --}}
					@if (isSettingsAppDarkModeEnabled())
						@include('front.layouts.partials.navs.themes', [
							'dropdownTag'    => 'li',
							'dropdownClass'  => 'nav-item',
							'buttonClass'    => 'nav-link',
							'menuAlignment'  => 'dropdown-menu-end',
							'showIconOnly'   => $showIconOnly,
							'linkColorClass' => $defaultLinkColorClass,
						])
					@endif
					
					{{-- Languages Dropdown/Modal Link --}}
					@include('front.layouts.partials.navs.languages')
				</ul>
			</div>
			
		</div>
	</nav>
</header>
