<?php

namespace Larapen\ReCaptcha\Service;

class ReCaptchaV2 extends ReCaptcha
{
	/**
	 * ReCaptchaV2 constructor.
	 *
	 * @param string $siteKey
	 * @param string $secretKey
	 * @param string|null $lang
	 * @param string|null $theme
	 */
	public function __construct(string $siteKey, string $secretKey, ?string $lang, ?string $theme = null)
	{
		parent::__construct($siteKey, $secretKey, 'v2', $lang, $theme);
	}
	
	/**
	 * Write ReCAPTCHA HTML tag in your FORM
	 * Insert before </form> tag
	 *
	 * @return string
	 */
	public function htmlFormSnippet(): string
	{
		$out = '<div class="g-recaptcha" data-sitekey="' . $this->siteKey . '"></div>';
		
		return ($this->version == 'v2') ? $out : '';
	}
}
