{{-- cloudflare turnstile --}}
@php
	use Illuminate\Support\ViewErrorBag;
	
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$wrapper ??= [];
	$viewName = 'turnstile';
	$type = 'hidden';
	$label ??= null;
	$id = 'cfTurnstileResponse';
	$name = 'cf-turnstile-response';
	$hint ??= null;
	
	$pluginOptions ??= [];
	
	$captchaType = config('settings.security.captcha');
	$siteKey = $pluginOptions['siteKey'] ?? config('settings.security.turnstile_site_key');
	$secretKey = $pluginOptions['secretKey'] ?? config('settings.security.turnstile_secret_key');
	$theme = $pluginOptions['theme'] ?? ((getThemePreference() == 'dark') ? 'dark' : 'light');
	$formId = $pluginOptions['formId'] ?? '';
	
	$isTurnstileEnabled = (
		$captchaType == 'turnstile' &&
		!empty($siteKey) &&
		!empty($secretKey)
	);
	
	$errors ??= new ViewErrorBag;
	$errorBag = ($errors instanceof ViewErrorBag) ? $errors : new ViewErrorBag;
	$isInvalidClass = $errorBag->has($name) ? 'is-invalid' : '';
	
	$wrapper = \App\Helpers\Common\Html\HtmlAttr::append($wrapper, 'class', $isInvalidClass);
@endphp
@if ($isTurnstileEnabled)
	<div @include('helpers.forms.attributes.field-wrapper')>
		@include('helpers.forms.partials.label')
		
		@if ($isHorizontal)
			<div class="{{ $colField }}">
		@endif
				
				<div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-theme="{{ $theme }}" data-callback="onTurnstileSuccess"></div>
				<input type="hidden" name="{{ $name }}" id="{{ $id }}">
				
				@include('helpers.forms.partials.hint')
				@include('helpers.forms.partials.validation')
				
		@if ($isHorizontal)
			</div>
		@endif
	</div>
	@include('helpers.forms.partials.newline')
@endif

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_assets_styles")
	@if ($isTurnstileEnabled)
		<style>
			.is-invalid .cf-turnstile iframe {
				border: 1px solid var(--bs-danger)!important;
			}
			
			[data-bs-theme="dark"] .cf-turnstile,
			[data-theme="dark"] .cf-turnstile,
			html[theme="dark"] .cf-turnstile {
				overflow: hidden;
				width: 300px;
				height: 65px;
			}
			
			[data-bs-theme="dark"] .is-invalid .cf-turnstile,
			[data-theme="dark"] .is-invalid .cf-turnstile,
			html[theme="dark"] .is-invalid .cf-turnstile {
				width: 304px;
				height: 69px;
			}
		</style>
	@endif
@endpushonce

{{-- include field specific assets code --}}
@push("{$viewName}_helper_head_scripts")
	@if ($isTurnstileEnabled)
		<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
		<script type="text/javascript">
			var turnstileFieldId = '{{ $id }}';
			
			/**
			 * Turnstile callback function
			 * @param token
			 */
			function onTurnstileSuccess(token) {
				const cfTurnstileResponseEl = document.getElementById(turnstileFieldId);
				if (cfTurnstileResponseEl) {
					cfTurnstileResponseEl.value = token;
				}
			}
		</script>
	@endif
@endpush
