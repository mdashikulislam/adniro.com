{{-- recaptcha --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$wrapper ??= [];
	$viewName = 'recaptcha';
	$type = 'hidden';
	$label ??= null; // trans('auth.captcha_human_verification')
	$id = 'gRecaptchaResponse';
	$name = 'g-recaptcha-response';
	$hint ??= null;
	
	$pluginOptions ??= [];
	
	$captchaType = config('settings.security.captcha');
	$siteKey = $pluginOptions['siteKey'] ?? config('recaptcha.site_key');
	$secretKey = $pluginOptions['secretKey'] ?? config('recaptcha.secret_key');
	$theme = $pluginOptions['theme'] ?? ((getThemePreference() == 'dark') ? 'dark' : 'light'); // Not used (This is handle in JS instead)
	$version = $pluginOptions['version'] ?? config('recaptcha.version', 'v2');
	$formId = $pluginOptions['formId'] ?? '';
	
	$isReCaptchaEnabled = (
		$captchaType == 'recaptcha' &&
		!empty($siteKey) &&
		!empty($secretKey) &&
		in_array($version, ['v2', 'v3'])
	);
	
	// JS configurations
	$jsConfig = [
		'field_id' => $id,
	];
	if ($version == 'v3') {
		$jsConfig = array_merge($jsConfig, [
			'action' 		     => request()->path(),
			'callbackThenFnName' => 'reCaptchaV3ThenCallback',
		]);
	}
	
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($name) ? 'is-invalid' : '';
	
	$wrapper = \App\Helpers\Common\Html\HtmlAttr::append($wrapper, 'class', $isInvalidClass);
@endphp
@if ($isReCaptchaEnabled)
	@if ($version == 'v3')
		<input type="hidden" name="{{ $name }}" id="{{ $id }}">
	@endif
	@if ($version == 'v2')
		<div @include('helpers.forms.attributes.field-wrapper')>
			@include('helpers.forms.partials.label')
			
			@if ($isHorizontal)
				<div class="{{ $colField }}">
					@endif
					
					{!! recaptchaHtmlFormSnippet() !!}
					
					@include('helpers.forms.partials.hint')
					@include('helpers.forms.partials.validation')
					
					@if ($isHorizontal)
				</div>
			@endif
		</div>
		@include('helpers.forms.partials.newline')
	@endif
@endif

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	@if ($isReCaptchaEnabled)
		<style>
			.is-invalid .g-recaptcha iframe {
				border: 1px solid var(--bs-danger)!important;
			}
			
			[data-bs-theme="dark"] .g-recaptcha, /* auth-panel */
			[data-theme="dark"] .g-recaptcha,    /* admin-panel */
			html[theme="dark"] .g-recaptcha {    /* front-end */
				overflow: hidden;
				width: 298px;
				height: 74px;
			}
			
			[data-bs-theme="dark"] .g-recaptcha iframe,
			[data-theme="dark"] .g-recaptcha iframe,
			html[theme="dark"] .g-recaptcha iframe {
				margin: -1px 0 0 -2px;
			}
			
			[data-bs-theme="dark"] .is-invalid .g-recaptcha,
			[data-theme="dark"] .is-invalid .g-recaptcha,
			html[theme="dark"] .is-invalid .g-recaptcha {
				width: 304px;
				height: 78px;
			}
			[data-bs-theme="dark"] .is-invalid .g-recaptcha iframe,
			[data-theme="dark"] .is-invalid .g-recaptcha iframe,
			html[theme="dark"] .is-invalid .g-recaptcha iframe {
				margin: inherit;
			}
		</style>
	@endif
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_head_scripts")
	@if ($isReCaptchaEnabled)
		@if ($version == 'v3')
			<script type="text/javascript">
				var recaptchaFieldId = '{{ $id }}';
				
				/**
				 * Custom callback function
				 * @param token
				 */
				function reCaptchaV3ThenCallback(token) {
					const gRecaptchaResponseEl = document.getElementById(recaptchaFieldId);
					if (gRecaptchaResponseEl) {
						gRecaptchaResponseEl.value = token;
					}
				}
				
				/* Unused Functions */
				function reCaptchaV3CallbackCatch(err) {
					console.log(err);
					reCaptchaV3UpdateBadge(false);
				}
				
				function reCaptchaV3UpdateBadge(isSuccessResponse) {
					const gRecaptchaBadge = document.querySelectorAll('.grecaptcha-badge');
					if (gRecaptchaBadge.length > 0) {
						gRecaptchaBadge.forEach((el) => {
							el.style.border = !isSuccessResponse ? '1px solid var(--bs-danger)!important' : 'none';
						});
					}
				}
			</script>
			{!! recaptchaApiV3JsScriptTag($jsConfig) !!}
		@else
			{!! recaptchaApiJsScriptTag($formId, $jsConfig) !!}
		@endif
	@endif
@endpush
