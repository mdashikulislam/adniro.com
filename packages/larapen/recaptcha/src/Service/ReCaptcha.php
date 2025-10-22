<?php

namespace Larapen\ReCaptcha\Service;

use Illuminate\Support\Arr;

class ReCaptcha
{
	/**
	 * The Site key
	 * please visit https://developers.google.com/recaptcha/docs/start
	 * @var string
	 */
	protected $siteKey;
	
	/**
	 * The Secret key
	 * please visit https://developers.google.com/recaptcha/docs/start
	 * @var string
	 */
	protected $secretKey;
	
	/**
	 * The chosen ReCAPTCHA version
	 * please visit https://developers.google.com/recaptcha/docs/start
	 * @var string
	 */
	protected $version;
	
	/**
	 * The Language Code
	 */
	protected $lang = null;
	
	/**
	 * Optional. The color theme of the widget
	 * please visit https://developers.google.com/recaptcha/docs/display
	 * Can be: 'dark' or 'light'. Default: light
	 * @var string
	 */
	protected $theme = null;
	
	/**
	 * Whether is true the ReCAPTCHA is inactive
	 * @var boolean
	 */
	protected $skipByIp = false;
	
	/**
	 * The API request URI
	 */
	protected $apiUrl = 'https://www.google.com/recaptcha/api/siteverify';
	
	/**
	 * ReCaptchaBuilder constructor.
	 *
	 * @param string $siteKey
	 * @param string $secretKey
	 * @param string $version
	 * @param string|null $lang
	 * @param string|null $theme
	 */
	public function __construct(string $siteKey, string $secretKey, string $version = 'v2', ?string $lang = null, ?string $theme = null)
	{
		$this->setSiteKey($siteKey);
		$this->setSecretKey($secretKey);
		$this->setVersion($version);
		$this->setLanguage($lang);
		$this->setTheme($theme);
		$this->setSkipByIp($this->skipByIp());
	}
	
	/**
	 * @param string $siteKey
	 * @return ReCaptcha
	 */
	public function setSiteKey(string $siteKey): ReCaptcha
	{
		$this->siteKey = $siteKey;
		
		return $this;
	}
	
	/**
	 * @param string $secretKey
	 * @return ReCaptcha
	 */
	public function setSecretKey(string $secretKey): ReCaptcha
	{
		$this->secretKey = $secretKey;
		
		return $this;
	}
	
	/**
	 * @param string|null $lang
	 * @return ReCaptcha
	 */
	public function setLanguage(?string $lang): ReCaptcha
	{
		$this->lang = $lang ?? 'en';
		
		return $this;
	}
	
	/**
	 * @param string|null $theme
	 * @return ReCaptcha
	 */
	public function setTheme(?string $theme): ReCaptcha
	{
		$defaultTheme = 'light';
		$theme ??= $defaultTheme;
		
		if (!empty($theme)) {
			$this->theme = in_array($theme, ['dark', 'light']) ? $theme : $defaultTheme;
		}
		
		return $this;
	}
	
	/**
	 * @param string $version
	 * @return ReCaptcha
	 */
	public function setVersion(string $version): ReCaptcha
	{
		$this->version = $version;
		
		return $this;
	}
	
	/**
	 * @param bool $skipByIp
	 * @return ReCaptcha
	 */
	public function setSkipByIp(bool $skipByIp): ReCaptcha
	{
		$this->skipByIp = $skipByIp;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getApiUrl(): string
	{
		return $this->apiUrl;
	}
	
	/**
	 * @return string
	 */
	public function getSecretKey(): string
	{
		return $this->secretKey;
	}
	
	/**
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}
	
	/**
	 * @return array
	 */
	public function getIpWhitelist(): array
	{
		$whitelist = config('recaptcha.skip_ip', []);
		
		if (is_string($whitelist)) {
			$whitelist = explode(',', $whitelist);
		}
		
		return is_array($whitelist) ? $whitelist : [];
	}
	
	/**
	 * Checks whether the user IP address is among IPs "to be skipped"
	 *
	 * @return bool
	 */
	public function skipByIp(): bool
	{
		return in_array(request()->ip(), $this->getIpWhitelist());
	}
	
	/**
	 * Write script HTML tag in you HTML code
	 * Insert before </head> tag
	 *
	 * @param string|null $formId
	 * @param array $configuration
	 * @return string
	 * @throws \Exception
	 */
	public function apiJsScriptTag(?string $formId = '', array $configuration = []): string
	{
		if ($this->skipByIp) return '';
		
		// Get language code
		$this->lang = Arr::get($configuration, 'lang', config('app.locale', 'en'));
		$this->lang = getLangTag($this->lang, false);
		
		switch ($this->version) {
			case 'v3':
				$langParam = !empty($this->lang) ? '&hl=' . $this->lang : '';
				$html = "<script src=\"https://www.google.com/recaptcha/api.js?render={$this->siteKey}{$langParam}\"></script>";
				break;
			default:
				$langParam = !empty($this->lang) ? '?hl=' . $this->lang : '';
				$html = "<script src=\"https://www.google.com/recaptcha/api.js{$langParam}\" async defer></script>";
		}
		
		if ($this->version == 'invisible') {
			if (!$formId) {
				throw new \Exception("formId required", 1);
			}
			$html .= '<script>
			function laraReCaptcha(token) {
				document.getElementById("' . $formId . '").submit();
			}
			</script>';
		}
		
		if ($this->version == 'v3') {
			$action = Arr::get($configuration, 'action', 'homepage');
			// $fieldId = Arr::get($configuration, 'field_id', 'gRecaptchaResponse');
			$callbackThenFnName = Arr::get($configuration, 'callbackThenFnName', '');
			
			$jsThenCallback = !empty($callbackThenFnName) ? "{$callbackThenFnName}(token)" : '';
			
			// Fixing invalid action name in recaptcha v3
			$action = str_replace(['-', '.'], '', $action);
			
			$html .= "<script>
			var csrfToken = document.head.querySelector('meta[name=\"csrf-token\"]');
			grecaptcha.ready(function() {
				grecaptcha.execute('{$this->siteKey}', {action: '{$action}'}).then(function(token) {
					{$jsThenCallback}
				});
			});
			</script>";
		}
		
		return $html;
	}
	
	/**
	 * @param array|null $configuration
	 * @return string
	 * @throws \Exception
	 */
	public function apiV3JsScriptTag(?array $configuration = []): string
	{
		return $this->apiJsScriptTag('', $configuration);
	}
}
