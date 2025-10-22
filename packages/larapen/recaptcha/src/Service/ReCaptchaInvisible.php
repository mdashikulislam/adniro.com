<?php

namespace Larapen\ReCaptcha\Service;

class ReCaptchaInvisible extends ReCaptcha
{
	/**
	 * ReCaptchaInvisible constructor.
	 *
	 * @param string $siteKey
	 * @param string $secretKey
	 * @param string|null $lang
	 * @param string|null $theme
	 */
	public function __construct(string $siteKey, string $secretKey, ?string $lang, ?string $theme = null)
	{
		parent::__construct($siteKey, $secretKey, 'invisible', $lang, $theme);
	}
	
	/**
	 * Write HTML <button> tag in your HTML code
	 * Insert before </form> tag
	 *
	 * @param string|null $buttonInnerHTML
	 *
	 * @return string
	 */
	public function htmlFormButton(?string $buttonInnerHTML = 'Submit'): string
	{
		$btn = '<button class="g-recaptcha" data-sitekey="' . $this->siteKey . '" data-theme="' . $this->theme . '" data-callback="laraReCaptcha">';
		$btn .= !empty($buttonInnerHTML) ? $buttonInnerHTML : 'Submit';
		$btn .= '</button>';
		
		return ($this->version == 'invisible') ? $btn : '';
	}
}
