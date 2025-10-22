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

namespace App\Models\Section;

use App\Helpers\Common\JsonUtils;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Models\HasSettings\Presets\Section\SearchFormPreset;
use App\Models\HasSettings\Traits\HasAnimation;
use App\Models\HasSettings\Traits\HasCssClass;

abstract class BaseSection
{
	use HasCssClass, HasAnimation;
	
	protected static ?Panel $xPanel = null;
	
	/**
	 * Initializes and configures the given Panel instance for the section.
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
	 * Retrieves the default preset configuration for the given section class.
	 *
	 * Dynamically resolves the corresponding Preset class based on the section's FQN
	 * and invokes its defaultPreset() method if available.
	 *
	 * @param string $sectionClassFQN
	 * @return array
	 */
	protected static function getDefaultPreset(string $sectionClassFQN): array
	{
		$sectionClassName = class_basename($sectionClassFQN);
		$presetClassName = str($sectionClassName)->replaceEnd('Section', 'Preset')->toString();
		
		$searchFormPresetClassFQN = SearchFormPreset::class;
		
		$presetClassNamespace = getClassNamespaceName($searchFormPresetClassFQN);
		$presetClassNamespace = str($presetClassNamespace)->wrap('\\')->toString();
		$class = $presetClassNamespace . $presetClassName;
		
		if (!class_exists($class)) {
			return [];
		}
		
		$method = 'defaultPreset';
		
		if (!method_exists($class, $method)) {
			return [];
		}
		
		return $class::{$method}();
	}
	
	/**
	 * Applies a specific preset configuration to the given section.
	 *
	 * Determines the matching Preset class from the section's FQN, retrieves
	 * the preset name from the request input, and invokes the corresponding
	 * preset method to modify or enrich the given values.
	 *
	 * @param string $sectionClassFQN
	 * @param array $value
	 * @return array
	 */
	protected static function applyPreset(string $sectionClassFQN, array $value = []): array
	{
		$sectionClassName = class_basename($sectionClassFQN);
		$presetClassName = str($sectionClassName)->replaceEnd('Section', 'Preset')->toString();
		
		$searchFormPresetClassFQN = SearchFormPreset::class;
		
		$presetClassNamespace = getClassNamespaceName($searchFormPresetClassFQN);
		$presetClassNamespace = str($presetClassNamespace)->wrap('\\')->toString();
		$class = $presetClassNamespace . $presetClassName;
		
		if (!class_exists($class)) {
			return $value;
		}
		
		$presetInput = request()->input('preset');
		$presetInput = JsonUtils::jsonToArray($presetInput);
		
		$presetName = $presetInput['section'] ?? null;
		
		if (empty($presetName) || !is_string($presetName)) {
			return $value;
		}
		
		$method = $presetName;
		
		if (!method_exists($class, $method)) {
			return $value;
		}
		
		return $class::{$method}($value);
	}
}
