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

trait HasCssClass
{
	use HasSpacing, HasRadius;
	
	/**
	 * Generate the global CSS classes from configuration data
	 *
	 * Converts stored:
	 * - margin/padding configuration
	 * - and visibility settings
	 * into valid Bootstrap CSS classes for frontend rendering.
	 *
	 * @param array $data e.g. Configuration data containing margin and visibility settings
	 * @param array|string|null $targetProperties Bootstrap property key (['field_name' => 'm'] or m for margin, p for padding, g for gap)
	 * @return array Array of Bootstrap CSS classes
	 */
	public static function buildCssClasses(array $data, array|string|null $targetProperties = 'm'): array
	{
		$cssClasses = [];
		
		// Process dynamic properties configuration
		if (!empty($targetProperties)) {
			// Margin classes (e.g. mb-4 or mb-lg-4 or mb-md-4)
			if (is_string($targetProperties)) {
				// Hard coded field name
				$targetProperty = $targetProperties;
				
				if (in_array($targetProperty, ['m', 'p'])) {
					$fieldName = ($targetProperty == 'm') ? 'margins' : 'paddings';
					if (array_key_exists($fieldName, $data)) {
						$fieldData = $data[$fieldName] ?? [];
						$cssClasses = array_merge($cssClasses, self::buildSpacingClasses($fieldData, $targetProperty));
					}
				}
				
				if ($targetProperty == 'rounded') {
					$fieldName = 'rounded';
					if (array_key_exists($fieldName, $data)) {
						$fieldData = $data[$fieldName] ?? [];
						$cssClasses = array_merge($cssClasses, self::buildRoundedClasses($fieldData));
					}
				}
			} else {
				// Dynamic field name
				if (is_array($targetProperties)) {
					foreach ($targetProperties as $fieldName => $targetProperty) {
						if (is_int($fieldName)) {
							continue;
						}
						
						if (array_key_exists($fieldName, $data)) {
							$fieldData = $data[$fieldName] ?? [];
							
							if (in_array($targetProperty, ['m', 'p'])) {
								$cssClasses = array_merge($cssClasses, self::buildSpacingClasses($fieldData, $targetProperty));
							}
							
							if ($targetProperty == 'rounded') {
								$cssClasses = array_merge($cssClasses, self::buildRoundedClasses($fieldData));
							}
						}
					}
				}
			}
			
			$cssClasses = collect($cssClasses)->unique()->toArray();
		}
		
		// Non-dynamic properties
		// ---
		// Prevent Header Overlap
		if (array_key_exists('prevent_header_overlap', $data)) {
			$preventHeaderOverlap = $data['prevent_header_overlap'] ?? '0';
			if ($preventHeaderOverlap == '1') {
				$cssClasses[] = 'prevent-header-overlap';
			}
		}
		
		// Handle responsive visibility
		if (array_key_exists('hide_on_mobile', $data)) {
			$hiddenOnMobile = $data['hide_on_mobile'] ?? '0';
			if ($hiddenOnMobile === '1') {
				$cssClasses[] = 'd-none d-md-block';
			}
		}
		
		return $cssClasses;
	}
	
	/**
	 * @param $value
	 * @param array|string|null $targetProperties
	 * @return string
	 */
	public static function buildCssClassesAsString($value, array|string|null $targetProperties = 'm'): string
	{
		$cssClasses = self::buildCssClasses($value, $targetProperties);
		
		return implode(' ', $cssClasses);
	}
}
