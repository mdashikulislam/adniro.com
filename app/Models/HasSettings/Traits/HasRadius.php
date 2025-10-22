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

namespace App\Models\HasSettings\Traits;

trait HasRadius
{
	/**
	 * Generate form fields for Bootstrap spacing configuration
	 *
	 * Creates repeatable form fields for configuring Bootstrap spacing properties
	 * with side, breakpoint, and size options. Supports margin, padding, and gap properties.
	 *
	 * @param array $fields Current array of form fields
	 * @param string|null $fieldName
	 * @param string|null $targetProperty
	 * @param array $wrapper
	 * @param string|null $tab
	 * @param string|null $fieldSeparator Position of field separators ('start', 'end', 'both')
	 * @return array Enhanced array of form fields with spacing configuration options
	 */
	protected static function appendRoundedFormFields(
		array   $fields = [],
		?string $fieldName = null,
		?string $targetProperty = 'rounded',
		array  $wrapper = [],
		?string $tab = null,
		?string $fieldSeparator = null
	): array
	{
		$roundedProperties = self::getRoundedFormattedProperties($targetProperty);
		$responsiveBreakpoints = self::getRoundedFormattedBreakpointOptions();
		$roundedSizes = self::getRoundedFormattedSizeOptions();
		
		if (empty($roundedProperties)) return $fields;
		
		$generatedFields = [];
		
		if ($fieldSeparator == 'start' || $fieldSeparator == 'both') {
			$generatedFields[] = self::createRadiusFormSeparatorField('start');
		}
		
		$breakpointCount = count($responsiveBreakpoints);
		$sizesCount = count($roundedSizes);
		
		foreach ($roundedProperties as $roundedProperty) {
			$repeatableFieldConfig = [];
			
			$propertyName = $roundedProperty['name'];
			$propertyLabel = $roundedProperty['label'];
			$propertyHint = trans('admin.bs_rounded_hint');
			$propertySides = $roundedProperty['formattedSides'];
			$sideCount = count($propertySides);
			
			$repeatableFieldConfig['name'] = !empty($fieldName) ? $fieldName : $propertyName;
			$repeatableFieldConfig['label'] = $propertyLabel;
			$repeatableFieldConfig['type'] = 'repeatable';
			
			$subFieldCol = !empty($responsiveBreakpoints) ? 'col-md-4' : 'col-md-6';
			
			$subFieldConfigs = [];
			
			// Side selection field
			$sideOptions = collect($propertySides)->prepend(trans('admin.select'), '')->toArray();
			$sideSubField = [];
			$sideSubField['name'] = 'side';
			$sideSubField['label'] = $propertyLabel;
			$sideSubField['type'] = 'select_from_array';
			$sideSubField['options'] = $sideOptions;
			$sideSubField['allows_null'] = false;
			$sideSubField['wrapper'] = ['class' => $subFieldCol];
			$subFieldConfigs[] = $sideSubField;
			
			// Breakpoint selection field
			if (!empty($responsiveBreakpoints)) {
				$breakpointOptions = collect($responsiveBreakpoints)->prepend(trans('admin.select'), '')->toArray();
				$breakpointSubField = [];
				$breakpointSubField['name'] = 'breakpoint';
				$breakpointSubField['label'] = 'Breakpoint';
				$breakpointSubField['type'] = 'select_from_array';
				$breakpointSubField['options'] = $breakpointOptions;
				$breakpointSubField['allows_null'] = false;
				$breakpointSubField['wrapper'] = ['class' => $subFieldCol];
				$subFieldConfigs[] = $breakpointSubField;
			}
			
			// Size selection field
			$sizeOptions = collect($roundedSizes)->prepend(trans('admin.select'), '')->toArray();
			$sizeSubField = [];
			$sizeSubField['name'] = 'size';
			$sizeSubField['label'] = 'Size';
			$sizeSubField['type'] = 'select_from_array';
			$sizeSubField['options'] = $sizeOptions;
			$sizeSubField['allows_null'] = false;
			$sizeSubField['wrapper'] = ['class' => $subFieldCol];
			$subFieldConfigs[] = $sizeSubField;
			
			$repeatableFieldConfig['subfields'] = $subFieldConfigs;
			
			// Calculate maximum possible combinations
			// $maxCombinations = $sideCount * $breakpointCount;
			// $maxCombinations = $sideCount * $sizesCount;
			$maxCombinations = 3;
			
			$repeatableFieldConfig['init_rows'] = 1;
			$repeatableFieldConfig['min_rows'] = 0;
			$repeatableFieldConfig['max_rows'] = $maxCombinations;
			$repeatableFieldConfig['reorder'] = false;
			$repeatableFieldConfig['hint'] = $propertyHint;
			$repeatableFieldConfig['wrapper'] = !empty($wrapper) ? $wrapper : ['class' => 'col-md-12'];
			$repeatableFieldConfig['tab'] = !empty($tab) ? $tab : null;
			
			$generatedFields[] = $repeatableFieldConfig;
		}
		
		if ($fieldSeparator == 'end' || $fieldSeparator == 'both') {
			$generatedFields[] = self::createRadiusFormSeparatorField('end');
		}
		
		return array_merge($fields, $generatedFields);
	}
	
	/**
	 * Build valid Bootstrap spacing CSS classes from configuration array
	 *
	 * Validates and converts spacing configuration into proper Bootstrap CSS classes
	 * following the format: {property}{side}-{breakpoint}-{size}.
	 *
	 * @param array $roundedConfig Array of spacing configurations with side, breakpoint, and size
	 * @return array Array of valid Bootstrap CSS classes
	 */
	protected static function buildRoundedClasses(array $roundedConfig, string $targetProperty = 'rounded'): array
	{
		$roundedProperties = self::getRoundedFormattedProperties($targetProperty);
		$currentProperty = current($roundedProperties);
		$validSideVariations = $currentProperty['formattedSides'] ?? [];
		
		$validSides = !empty($validSideVariations) ? array_keys($validSideVariations) : [];
		$validBreakpoints = array_keys(self::getRoundedFormattedBreakpointOptions());
		$validSizes = array_keys(self::getRoundedFormattedSizeOptions());
		
		$cssClasses = [];
		
		foreach ($roundedConfig as $roundedRule) {
			$side = $roundedRule['side'] ?? '';
			$breakpoint = $roundedRule['breakpoint'] ?? '';
			$size = $roundedRule['size'] ?? '';
			
			// Validate side (should be m, mt, mb, ml, mr, mx, my)
			if (!in_array($side, $validSides)) {
				continue;
			}
			
			// Validate breakpoint (should be sm, md, lg, xl, xxl or empty/blank)
			if (!empty($breakpoint)) {
				if (!in_array($breakpoint, $validBreakpoints)) {
					continue;
				}
			}
			
			// Validate size (should be 0-5 or auto)
			if (!in_array($size, $validSizes)) {
				continue;
			}
			
			// Build the Bootstrap class following: {property}{side}-{breakpoint}-{size}
			if (!empty($breakpoint)) {
				$bootstrapClass = $side . '-' . $breakpoint . '-' . $size;
			} else {
				$bootstrapClass = $side . '-' . $size;
			}
			
			$cssClasses[] = $bootstrapClass;
		}
		
		return $cssClasses;
	}
	
	/**
	 * Get default margin configuration
	 *
	 * Provides a sensible default margin configuration for Bootstrap spacing.
	 *
	 * @return array Default margin configuration for Bootstrap CSS class rounded-3
	 */
	protected static function getDefaultRadiusConfiguration(): array
	{
		return [
			[
				'radius'     => 'rounded',
				'breakpoint' => null,
				'size'       => '3',
			],
		];
	}
	
	// PRIVATE
	
	/**
	 * Retrieve and format Bootstrap spacing properties with side variations
	 *
	 * Builds a comprehensive list of spacing properties (margin, padding, gap) with
	 * their corresponding side variations (top, bottom, left, right, x-axis, y-axis).
	 *
	 * @return array Formatted properties with name, label, and available side variations
	 */
	private static function getRoundedFormattedProperties(?string $targetPropertyKey = 'rounded'): array
	{
		$roundedConfiguration = getCachedReferrerList('bootstrap/rounded');
		
		$availableProperties = $roundedConfiguration['properties'] ?? [];
		$availableSideVariations = $roundedConfiguration['sides'] ?? [];
		
		if (empty($availableProperties)) {
			return [];
		}
		
		// Filter to specific property if requested
		$propertiesToProcess = (
			$targetPropertyKey !== null
			&& array_key_exists($targetPropertyKey, $availableProperties)
		)
			? [$targetPropertyKey => $availableProperties[$targetPropertyKey]]
			: $availableProperties;
		
		$formattedProperties = [];
		
		foreach ($propertiesToProcess as $propertyKey => $propertyConfig) {
			$formattedProperties[$propertyKey] = [
				'name'           => $propertyConfig['name'],
				'label'          => $propertyConfig['label'],
				'formattedSides' => self::buildRoundedPropertySideVariations(
					$propertyKey,
					$propertyConfig['label'],
					$availableSideVariations
				),
			];
		}
		
		return $formattedProperties;
	}
	
	/**
	 * Build formatted side variations for a Bootstrap spacing property
	 *
	 * Combines property keys with side variations to create complete spacing class prefixes
	 * (e.g., 'rounded' + 'top' = 'rounded-top' for ...).
	 *
	 * @param string $propertyKey Base Bootstrap property key (rounded)
	 * @param string $propertyLabel Human-readable property name
	 * @param array $availableSides Available side variations with keys and labels
	 * @return array Combined property-side keys with descriptive labels
	 */
	private static function buildRoundedPropertySideVariations(
		string $propertyKey,
		string $propertyLabel,
		array  $availableSides
	): array
	{
		$formattedSideVariations = [];
		
		foreach ($availableSides as $sideKey => $sideLabel) {
			$combinedPropertyKey = ($sideKey === 'blank') ? $propertyKey : "{$propertyKey}-{$sideKey}";
			$formattedSideVariations[$combinedPropertyKey] = "{$propertyLabel} {$sideLabel}";
		}
		
		return $formattedSideVariations;
	}
	
	/**
	 * Get formatted Bootstrap responsive breakpoint options
	 *
	 * Retrieves and formats available Bootstrap breakpoints for responsive spacing.
	 *
	 * @return array Formatted breakpoint options with descriptive labels
	 */
	private static function getRoundedFormattedBreakpointOptions(): array
	{
		$roundedConfiguration = getCachedReferrerList('bootstrap/rounded');
		
		$responsiveBreakpoints = $roundedConfiguration['breakpoints'] ?? [];
		
		return collect($responsiveBreakpoints)
			->map(fn ($label, $key) => "$key - $label")
			->toArray();
	}
	
	/**
	 * Get formatted Bootstrap spacing size options
	 *
	 * Retrieves available Bootstrap spacing sizes with their corresponding values.
	 *
	 * @return array Formatted size options showing both key and value
	 */
	private static function getRoundedFormattedSizeOptions(): array
	{
		$roundedConfiguration = getCachedReferrerList('bootstrap/rounded');
		$availableSizes = $roundedConfiguration['sizes'] ?? [];
		
		return collect($availableSizes)
			->map(fn ($value, $key) => ($key == '0') ? $key : "$key ($value)")
			->toArray();
	}
	
	/**
	 * Generate HTML separator field for form sections
	 *
	 * Creates a horizontal rule separator to visually divide form sections.
	 *
	 * @param string $separatorName Unique identifier for the separator field
	 * @return array Form field configuration for HTML separator
	 */
	private static function createRadiusFormSeparatorField(string $separatorName): array
	{
		return [
			'name'  => "radius_separator_$separatorName",
			'type'  => 'custom_html',
			'value' => '<hr>',
		];
	}
}
