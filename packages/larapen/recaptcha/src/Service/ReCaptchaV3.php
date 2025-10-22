<?php

namespace Larapen\ReCaptcha\Service;

class ReCaptchaV3 extends ReCaptcha
{
	/**
	 * ReCaptchaV3 constructor.
	 *
	 * @param string $siteKey
	 * @param string $secretKey
	 * @param string|null $lang
	 * @param string|null $theme
	 */
	public function __construct(string $siteKey, string $secretKey, ?string $lang, ?string $theme = null)
	{
		parent::__construct($siteKey, $secretKey, 'v3', $lang, $theme);
	}
}
