<style>
@if (!empty($primaryBgColor))
@php
	$primaryBgColor10 ??= $primaryBgColor;
	
	$primaryBgColorRgb = rgbToCss(hexToRgb($primaryBgColor), true);
	$primaryBgColor10Rgb = rgbToCss(hexToRgb($primaryBgColor10), true);
@endphp
/* === Skin === */
:root,
[data-bs-theme="light"] {
	--bs-themecolor: {{ $primaryBgColor }};
	--bs-themecolor-rgb: {{ $primaryBgColorRgb }};
	--bs-themehovercolor: {{ $primaryBgColor10 }};
	--bs-themehovercolor-rgb: {{ $primaryBgColor10Rgb }};
	--bs-link-color: var(--bs-themecolor);
	--bs-link-color-rgb: var(--bs-themecolor-rgb);
	--bs-link-hover-color: var(--bs-themehovercolor);
	--bs-link-hover-color-rgb: var(--bs-themehovercolor-rgb);
	--bs-primary: var(--bs-themecolor);
	--bs-primary-rgb: var(--bs-themecolor-rgb);
	--bs-primary-text-emphasis: {{ $primaryDarkBgColor }};
	--bs-primary-bg-subtle: {{ $primaryBgColor50 }};
	--bs-primary-border-subtle: {{ $primaryBgColor20d }};
	--bs-body-color: #4c4d4d;
	--bs-body-color-rgb: 76, 77, 77;
	--bs-heading-color: var(--bs-emphasis-color);
	--bs-body-font-family: Poppins, sans-serif;
}

[data-bs-theme="dark"] {
	color-scheme: dark;
	--bs-link-color: var(--bs-themecolor);
	--bs-link-color-rgb: var(--bs-themecolor-rgb);
	--bs-link-hover-color: var(--bs-themehovercolor);
	--bs-link-hover-color-rgb: var(--bs-themehovercolor-rgb);
	--bs-heading-color: var(--bs-emphasis-color);
	--bs-body-color: #dee2e6;
	--bs-body-color-rgb: 222, 226, 230;
}

.btn.disabled,
.btn:disabled,
fieldset:disabled .btn {
	background-color: var(--bs-themecolor);
	border-color: var(--bs-themehovercolor);
	color: var(--bs-btn-disabled-color);
}
@endif
</style>
