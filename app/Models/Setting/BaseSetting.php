<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Models\Setting;

use App\Helpers\Common\JsonUtils;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Models\HasSettings\Presets\Setting\HeaderPreset;
use App\Models\HasSettings\Traits\HasCssClass;

abstract class BaseSetting
{
	use HasCssClass;
	
	protected static ?Panel $xPanel = null;
	
	/**
	 * Initializes and configures the given Panel instance for the setting.
	 *
	 * Enables tab features on the Panel, assigns it to the static property,
	 * and returns the configured instance.
	 *
	 * @param \App\Http\Controllers\Web\Admin\Panel\Library\Panel $xPanel
	 * @return \App\Http\Controllers\Web\Admin\Panel\Library\Panel
	 */
	public static function setup(Panel $xPanel): Panel
	{
		$xPanel->enableTabs();
		$xPanel->enableVerticalTabs();
		self::$xPanel = $xPanel;
		
		return $xPanel;
	}
	
	/**
	 * Retrieves the tabs type from the internal Panel object.
	 * i.e. Get the tabs type from the current Panel instance.
	 *
	 * @return string|null The tabs type string, or null if the Panel object is unset/null.
	 */
	public static function getPanelTabsType(): ?string
	{
		// Uses the nullsafe operator (?->) introduced in PHP 8.0.
		// This safely calls getTabsType() if self::$xPanel is an object,
		// otherwise it returns null immediately, handling null/unset and type checks implicitly.
		return self::$xPanel?->getTabsType();
	}
	
	/**
	 * Applies a specific preset configuration to the given setting.
	 *
	 * Determines the matching Preset class from the setting's FQN, retrieves
	 * the preset name from the request input, and invokes the corresponding
	 * preset method to modify or enrich the given values.
	 *
	 * @param string $settingClassFQN
	 * @param array $value
	 * @return array
	 */
	protected static function applyPreset(string $settingClassFQN, array $value = []): array
	{
		$settingClassName = class_basename($settingClassFQN);
		$presetClassName = str($settingClassName)->replaceEnd('Setting', 'Preset')->toString();
		
		$headerPresetClassFQN = HeaderPreset::class;
		
		$presetClassNamespace = getClassNamespaceName($headerPresetClassFQN);
		$presetClassNamespace = str($presetClassNamespace)->wrap('\\')->toString();
		$class = $presetClassNamespace . $presetClassName;
		
		if (!class_exists($class)) {
			return $value;
		}
		
		$presetInput = request()->input('preset');
		$presetInput = JsonUtils::jsonToArray($presetInput);
		
		$presetName = $presetInput['setting'] ?? null;
		
		if (empty($presetName)) {
			return $value;
		}
		
		$method = null;
		if (is_string($presetName)) {
			$method = $presetName;
		}
		
		if (is_array($presetName)) {
			foreach ($presetName as $key => $item) {
				$key = str($key)->ucfirst()->append('Setting')->toString();
				if ($key == $settingClassName) {
					$method = $item;
					break;
				}
			}
		}
		
		if (empty($method) || !is_string($method)) {
			return $value;
		}
		
		if (!method_exists($class, $method)) {
			return $value;
		}
		
		return $class::{$method}($value);
	}
}
