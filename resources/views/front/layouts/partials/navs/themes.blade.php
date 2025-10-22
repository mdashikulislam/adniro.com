@php
	use App\Enums\ThemePreference;
@endphp
@if (isSettingsAppDarkModeEnabled() || isFromAdminPanel())
	@php
		$showIconOnly ??= false;
		
		// Get all themes
		$userThemes = getFormattedThemes();
		
		// Get selected theme
		$defaultTheme = isSettingsAppSystemThemeEnabled()
			? ThemePreference::SYSTEM->value
			: ThemePreference::LIGHT->value;
		$defaultThemeLabel = isSettingsAppSystemThemeEnabled()
			? ThemePreference::SYSTEM->label()
			: ThemePreference::LIGHT->label();
		$selectedTheme = getThemePreference() ?? $defaultTheme;
		$selectedThemeLabel = getFormattedThemes(theme: $selectedTheme, iconOnly: $showIconOnly)['label'] ?? $defaultThemeLabel;
		$selectedThemeForcedLabel = getFormattedThemes(theme: $selectedTheme)['label'] ?? $defaultThemeLabel;
		
		// Tag & CSS Classes
		$dropdownTag ??= 'div';
		$dropdownClass ??= '';
		$buttonClass ??= ''; // btn btn-secondary
		$menuAlignment ??= ''; // dropdown-menu-end
		
		$dropdownTag = in_array($dropdownTag, ['div', 'li', 'span', 'p']) ? $dropdownTag : 'div';
		$dropdownClass = !empty($dropdownClass) ? ' ' . $dropdownClass : '';
		$menuAlignment = !empty($menuAlignment) ? ' ' . $menuAlignment : '';
		
		$defaultLinkColorClass ??= linkClass('body-emphasis');
		$defaultLinkColorClass = !empty($defaultLinkColorClass) ? ' ' . trim($defaultLinkColorClass) : '';
		
		$defaultHeaderItemShadowClass ??= '';
		$itemShadowClass = !empty($defaultHeaderItemShadowClass) ? ' ' . trim($defaultHeaderItemShadowClass) : '';
	@endphp
	@if (!empty($userThemes))
		<{{ $dropdownTag }} id="themeSwitcher" class="dropdown{{ $dropdownClass }}">
			<a href="#"
			   data-theme="{{ $selectedTheme }}"
			   class="{{ $buttonClass }} dropdown-toggle{{ $defaultLinkColorClass . $itemShadowClass }}"
			   role="button"
			   data-bs-toggle="dropdown"
			   aria-expanded="false"
			>
				<span class="large-screen d-none d-xl-inline-block">{!! $selectedThemeLabel !!}</span>
				<span class="small-screen d-inline-block d-xl-none ms-1">{!! $selectedThemeForcedLabel !!}</span>
			</a>
			
			<ul id="themesNavDropdown" class="dropdown-menu shadow-sm{{ $menuAlignment }}">
				@foreach($userThemes as $key => $label)
					@php
						$activeClass = ($selectedTheme == $key) ? ' active' : '';
					@endphp
					<li>
						<a href=""
						   data-csrf-token="{{ csrf_token() }}"
						   data-theme="{{ $key }}"
						   data-user-id="{{ $authUser->id ?? null }}"
						   class="dropdown-item{{ $activeClass }}"
						>
							{!! $label['label'] ?? '' !!}
						</a>
					</li>
				@endforeach
			</ul>
		</{{ $dropdownTag }}>
	@endif
@endif
