@php
	$captchaType = config('settings.security.captcha');
	$isCaptchaEnabled = !empty($captchaType);
	
	// Get all variables available in the current view, then
	// Filter out Laravel's internal variables if needed
	$allVars = get_defined_vars();
	$bladeInternalVars = ['__data', '__path', '__env', 'app', 'errors'];
	$passedParams = array_diff_key($allVars, array_flip($bladeInternalVars));
	
	// Verify the field label
	$label = $passedParams['label'] ?? '';
	$label = (is_string($label) && !empty($label)) ? $label : null;
	$passedParams['label'] = $label;
	if (!empty($label)) {
		$passedParams['required'] = true;
	}
@endphp
@if ($isCaptchaEnabled)
	@if ($captchaType == 'recaptcha')
		@include('helpers.forms.fields.recaptcha', $passedParams)
	@endif
	@if (in_array($captchaType, ['default', 'math', 'flat', 'mini', 'inverse', 'custom']))
		@include('helpers.forms.fields.simple-captcha', $passedParams)
	@endif
@endif
