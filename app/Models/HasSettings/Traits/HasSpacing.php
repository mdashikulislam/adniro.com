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

trait HasSpacing
{
	/**
	 * Generate form fields for Bootstrap spacing configuration
	 *
	 * Creates repeatable form fields for configuring Bootstrap spacing properties
	 * with side, breakpoint, and size options. Supports margin, padding, and gap properties.
	 *
	 * @param array $fields Current array of form fields
	 * @param string|null $fieldName
	 * @param string|null $targetProperty Specific property to generate fields for (e.g., 'm', 'p', 'g')
	 * @param array $wrapper
	 * @param string|null $tab
	 * @param string|null $fieldSeparator Position of field separators ('start', 'end', 'both')
	 * @param bool $preventHeaderOverlap
	 * @return array Enhanced array of form fields with spacing configuration options
	 */
	protected static function appendSpacingFormFields(
		array   $fields = [],
		?string $fieldName = null,
		?string $targetProperty = 'm',
		array   $wrapper = [],
		?string $tab = null,
		?string $fieldSeparator = null,
		bool    $preventHeaderOverlap = true
	): array
	{
		$spacingProperties = self::getSpacingFormattedProperties($targetProperty);
		$responsiveBreakpoints = self::getSpacingFormattedBreakpointOptions();
		$spacingSizes = self::getSpacingFormattedSizeOptions();
		
		if (empty($spacingProperties)) return $fields;
		
		$generatedFields = [];
		
		if ($fieldSeparator == 'start' || $fieldSeparator == 'both') {
			$generatedFields[] = self::createSpacingFormSeparatorField('start', $tab);
		}
		
		$breakpointCount = count($responsiveBreakpoints);
		foreach ($spacingProperties as $spacingProperty) {
			$repeatableFieldConfig = [];
			
			$propertyName = $spacingProperty['name'];
			$propertyLabel = $spacingProperty['label'];
			$propertyHint = ($targetProperty == 'm') ? trans('admin.bs_margin_spacing_hint') : trans('admin.bs_padding_spacing_hint');
			$propertySides = $spacingProperty['formattedSides'];
			$sideCount = count($propertySides);
			
			$repeatableFieldConfig['name'] = !empty($fieldName) ? $fieldName : $propertyName;
			$repeatableFieldConfig['label'] = $propertyLabel;
			$repeatableFieldConfig['type'] = 'repeatable';
			
			$subFieldConfigs = [];
			
			// Side selection field
			$sideOptions = collect($propertySides)->prepend(trans('admin.select'), '')->toArray();
			$sideSubField = [];
			$sideSubField['name'] = 'side';
			$sideSubField['label'] = "{$propertyLabel} Side";
			$sideSubField['type'] = 'select_from_array';
			$sideSubField['options'] = $sideOptions;
			$sideSubField['allows_null'] = false;
			$sideSubField['wrapper'] = ['class' => 'col-md-4'];
			$subFieldConfigs[] = $sideSubField;
			
			// Breakpoint selection field
			$breakpointOptions = collect($responsiveBreakpoints)->prepend(trans('admin.select'), '')->toArray();
			$breakpointSubField = [];
			$breakpointSubField['name'] = 'breakpoint';
			$breakpointSubField['label'] = 'Breakpoint';
			$breakpointSubField['type'] = 'select_from_array';
			$breakpointSubField['options'] = $breakpointOptions;
			$breakpointSubField['allows_null'] = false;
			$breakpointSubField['wrapper'] = ['class' => 'col-md-4'];
			$subFieldConfigs[] = $breakpointSubField;
			
			// Size selection field
			$sizeOptions = collect($spacingSizes)->prepend(trans('admin.select'), '')->toArray();
			$sizeSubField = [];
			$sizeSubField['name'] = 'size';
			$sizeSubField['label'] = 'Size';
			$sizeSubField['type'] = 'select_from_array';
			$sizeSubField['options'] = $sizeOptions;
			$sizeSubField['allows_null'] = false;
			$sizeSubField['wrapper'] = ['class' => 'col-md-4'];
			$subFieldConfigs[] = $sizeSubField;
			
			$repeatableFieldConfig['subfields'] = $subFieldConfigs;
			
			// Calculate maximum possible combinations
			$maxCombinations = $sideCount * $breakpointCount;
			
			$repeatableFieldConfig['init_rows'] = 1;
			$repeatableFieldConfig['min_rows'] = 0;
			$repeatableFieldConfig['max_rows'] = $maxCombinations;
			$repeatableFieldConfig['reorder'] = false;
			$repeatableFieldConfig['hint'] = $propertyHint;
			$repeatableFieldConfig['wrapper'] = !empty($wrapper) ? $wrapper : ['class' => 'col-md-12'];
			$repeatableFieldConfig['tab'] = !empty($tab) ? $tab : null;
			
			$generatedFields[] = $repeatableFieldConfig;
		}
		
		if ($targetProperty == 'm' && $preventHeaderOverlap) {
			$generatedFields[] = self::createFormPreventHeaderOverlapField($tab);
		}
		
		if ($fieldSeparator == 'end' || $fieldSeparator == 'both') {
			$generatedFields[] = self::createSpacingFormSeparatorField('end', $tab);
		}
		
		return array_merge($fields, $generatedFields);
	}
	
	/**
	 * Build valid Bootstrap spacing CSS classes from configuration array
	 *
	 * Validates and converts spacing configuration into proper Bootstrap CSS classes
	 * following the format: {property}{side}-{breakpoint}-{size}.
	 *
	 * @param array $spacingConfig Array of spacing configurations with side, breakpoint, and size
	 * @param string $targetProperty Bootstrap property to validate against (m, p, g)
	 * @return array Array of valid Bootstrap CSS classes
	 */
	protected static function buildSpacingClasses(array $spacingConfig, string $targetProperty = 'm'): array
	{
		$spacingProperties = self::getSpacingFormattedProperties($targetProperty);
		$currentProperty = current($spacingProperties);
		$validSideVariations = $currentProperty['formattedSides'] ?? [];
		
		$validSides = !empty($validSideVariations) ? array_keys($validSideVariations) : [];
		$validBreakpoints = array_keys(self::getSpacingFormattedBreakpointOptions());
		$validSizes = array_keys(self::getSpacingFormattedSizeOptions());
		
		$cssClasses = [];
		
		foreach ($spacingConfig as $spacingRule) {
			$side = $spacingRule['side'] ?? '';
			$breakpoint = $spacingRule['breakpoint'] ?? '';
			$size = $spacingRule['size'] ?? '';
			
			// Validate side (should be m, mt, mb, ml, mr, mx, my)
			if (!in_array($side, $validSides)) {
				continue;
			}
			
			// Validate breakpoint (should be sm, md, lg, xl, xxl or empty/blank)
			if (!empty($breakpoint) && !in_array($breakpoint, $validBreakpoints)) {
				continue;
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
	 * @return array Default margin configuration for Bootstrap CSS class mb-4
	 */
	protected static function getDefaultMarginConfiguration(): array
	{
		return [
			[
				'side'       => 'mb',
				'breakpoint' => null,
				'size'       => '4',
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
	 * @param string|null $targetPropertyKey Optional filter to return only a specific property
	 * @return array Formatted properties with name, label, and available side variations
	 */
	private static function getSpacingFormattedProperties(?string $targetPropertyKey = 'm'): array
	{
		$spacingConfiguration = getCachedReferrerList('bootstrap/spacing');
		
		$availableProperties = $spacingConfiguration['properties'] ?? [];
		$availableSideVariations = $spacingConfiguration['sides'] ?? [];
		
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
				'formattedSides' => self::buildSpacingPropertySideVariations(
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
	 * (e.g., 'm' + 't' = 'mt' for margin-top).
	 *
	 * @param string $propertyKey Base Bootstrap property key (m, p, g)
	 * @param string $propertyLabel Human-readable property name
	 * @param array $availableSides Available side variations with keys and labels
	 * @return array Combined property-side keys with descriptive labels
	 */
	private static function buildSpacingPropertySideVariations(
		string $propertyKey,
		string $propertyLabel,
		array  $availableSides
	): array
	{
		$formattedSideVariations = [];
		
		foreach ($availableSides as $sideKey => $sideLabel) {
			$combinedPropertyKey = ($sideKey === 'blank') ? $propertyKey : "{$propertyKey}{$sideKey}";
			$formattedSideVariations[$combinedPropertyKey] = "{$combinedPropertyKey} - {$propertyLabel} {$sideLabel}";
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
	private static function getSpacingFormattedBreakpointOptions(): array
	{
		$spacingConfiguration = getCachedReferrerList('bootstrap/spacing');
		
		$responsiveBreakpoints = $spacingConfiguration['breakpoints'] ?? [];
		
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
	private static function getSpacingFormattedSizeOptions(): array
	{
		$spacingConfiguration = getCachedReferrerList('bootstrap/spacing');
		$availableSizes = $spacingConfiguration['sizes'] ?? [];
		
		return collect($availableSizes)
			->map(fn ($value, $key) => "$key ($value)")
			->toArray();
	}
	
	/**
	 * Generate HTML separator field for form sections
	 *
	 * Creates a horizontal rule separator to visually divide form sections.
	 *
	 * @param string $separatorName Unique identifier for the separator field
	 * @param string|null $tab
	 * @return array Form field configuration for HTML separator
	 */
	private static function createSpacingFormSeparatorField(string $separatorName, ?string $tab = null): array
	{
		return [
			'name'  => "spacing_separator_$separatorName",
			'type'  => 'custom_html',
			'value' => '<hr>',
			'tab'   => !empty($tab) ? $tab : null,
		];
	}
	
	private static function createFormPreventHeaderOverlapField(?string $tab = null): array
	{
		return [
			'name'  => 'prevent_header_overlap',
			'label' => trans('admin.prevent_header_overlap_label'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.prevent_header_overlap_hint'),
			'tab'   => !empty($tab) ? $tab : null,
		];
	}
}
