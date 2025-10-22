<?php

use Larapen\ReCaptcha\Facades\ReCaptcha;

if (!function_exists('recaptcha')) {
	/**
	 * @return Larapen\ReCaptcha\Service\ReCaptcha
	 */
	function recaptcha()
	{
		return app('recaptcha');
	}
}

/**
 * Call ReCaptcha::apiJsScriptTag()
 * Write script HTML tag in you HTML code
 * Insert before </head> tag
 *
 * @param $formId - required if you are using invisible ReCaptcha
 */
if (!function_exists('recaptchaApiJsScriptTag')) {
	/**
	 * @param string|null $formId
	 * @param array $configuration
	 * @return string
	 */
	function recaptchaApiJsScriptTag(?string $formId = '', array $configuration = []): string
	{
		return ReCaptcha::apiJsScriptTag($formId, $configuration);
	}
}

/**
 * Call ReCaptcha::apiJsScriptTag()
 * Write script HTML tag in you HTML code
 * Insert before </head> tag
 *
 * @param $formId - required if you are using invisible ReCaptcha
 */
if (!function_exists('recaptchaApiV3JsScriptTag')) {
	/**
	 * @param array $configuration
	 *
	 * @return string
	 */
	function recaptchaApiV3JsScriptTag(array $configuration = []): string
	{
		return ReCaptcha::apiV3JsScriptTag($configuration);
	}
}

/**
 * Call ReCaptcha::htmlFormButton()
 * Write HTML <button> tag in your HTML code
 * Insert before </form> tag
 *
 * Warning! Using only with ReCAPTCHA INVISIBLE
 *
 * @param $buttonInnerHTML - What you want to write on the submit button
 */
if (!function_exists('recaptchaHtmlFormButton')) {
	/**
	 * @param null|string $buttonInnerHTML
	 *
	 * @return string
	 */
	function recaptchaHtmlFormButton(?string $buttonInnerHTML = 'Submit'): string
	{
		return ReCaptcha::htmlFormButton($buttonInnerHTML);
	}
}

/**
 * Call ReCaptcha::htmlFormSnippet()
 * Write ReCAPTCHA HTML tag in your FORM
 * Insert before </form> tag
 *
 * Warning! Using only with ReCAPTCHA v2
 */
if (!function_exists('recaptchaHtmlFormSnippet')) {
	/**
	 * @return string
	 */
	function recaptchaHtmlFormSnippet(): string
	{
		return ReCaptcha::htmlFormSnippet();
	}
}
