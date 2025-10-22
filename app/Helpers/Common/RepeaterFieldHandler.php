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

namespace App\Helpers\Common;

use Illuminate\Http\Request;

/*
 * Laravel service for handling jQuery Repeater field values
 * https://github.com/DubFriend/jquery.repeater
 *
 * This service transforms jQuery Repeater data structures into clean arrays
 * suitable for database storage and model relationships.
 */

class RepeaterFieldHandler
{
	/**
	 * Transform jQuery Repeater field data according to structure rules
	 *
	 * Rules:
	 * - Single element arrays: extract value only (remove key)
	 * - Two element arrays with same keys: transform to key-value pairs
	 * - Arrays with 3+ elements: keep as-is
	 * - Mixed arrays (different structures): no transformation
	 *
	 * @param array $repeaterData
	 * @return array
	 */
	public function transformRepeaterData(array $repeaterData): array
	{
		if (empty($repeaterData)) {
			return [];
		}
		
		$collection = collect($repeaterData);
		
		// Check if array is mixed (contains different element counts)
		$elementCounts = $collection->map(function ($item) {
			return is_array($item) ? count($item) : 0;
		})->unique();
		
		// If mixed element counts, return original array
		if ($elementCounts->count() > 1) {
			return $repeaterData;
		}
		
		$firstCount = $elementCounts->first();
		
		// Only process arrays with 1 or 2 elements
		if (!in_array($firstCount, [1, 2])) {
			return $repeaterData;
		}
		
		// Check if all subarrays have the same keys
		$firstKeys = collect(array_keys($repeaterData[0]))->sort()->values();
		$allHaveSameKeys = $collection->every(function ($item) use ($firstKeys) {
			if (!is_array($item)) return false;
			$itemKeys = collect(array_keys($item))->sort()->values();
			return $firstKeys->toArray() === $itemKeys->toArray();
		});
		
		// If keys are not uniform, return original array
		if (!$allHaveSameKeys) {
			return $repeaterData;
		}
		
		// Apply transformations
		$result = collect();
		
		if ($firstCount == 1) {
			// Single element: remove key, get value as element
			$collection->each(function ($item) use ($result) {
				$result->push(collect($item)->first());
			});
			
		} else if ($firstCount == 2) {
			// Two elements: use first value as key, second value as value
			$collection->each(function ($item) use ($result) {
				$values = collect($item)->values();
				$result->put($values->first(), $values->last());
			});
		}
		
		return $result->toArray();
	}
	
	/**
	 * Extract repeater data from Laravel Request
	 *
	 * @param Request $request
	 * @param string $fieldName The data-repeater-list name
	 * @return array
	 */
	public function extractFromRequest(Request $request, string $fieldName): array
	{
		$repeaterData = $request->input($fieldName, []);
		
		if (empty($repeaterData)) {
			return [];
		}
		
		return $this->transformRepeaterData($repeaterData);
	}
	
	/**
	 * Prepare data for populating repeater fields (reverse transformation)
	 *
	 * @param array $data
	 * @param string|null $singleKey For single-element transformation
	 * @param array $doubleKeys For two-element transformation ['key_field', 'value_field']
	 * @return array
	 */
	public function prepareForRepeater(array $data, ?string $singleKey = null, array $doubleKeys = []): array
	{
		if (empty($data)) {
			return [];
		}
		
		$collection = collect($data);
		
		// If data is already in repeater format (indexed arrays with sub-arrays)
		if ($collection->every(fn ($item) => is_array($item))) {
			return $data;
		}
		
		// Transform simple array to single-key repeater format
		if ($singleKey && $collection->every(fn ($item) => !is_array($item))) {
			return $collection->map(fn ($item) => [$singleKey => $item])->toArray();
		}
		
		// Transform associative array to two-key repeater format
		if (count($doubleKeys) === 2 && $collection->every(fn ($item, $key) => !is_numeric($key))) {
			[$keyField, $valueField] = $doubleKeys;
			
			return $collection->map(fn ($value, $key) => [
				$keyField   => $key,
				$valueField => $value,
			])->values()->toArray();
		}
		
		return $data;
	}
	
	/**
	 * Validate repeater data structure
	 *
	 * @param array $data
	 * @param array $requiredFields
	 * @param int|null $minItems
	 * @param int|null $maxItems
	 * @return array Validation errors
	 */
	public function validateRepeaterData(
		array $data,
		array $requiredFields = [],
		?int  $minItems = null,
		?int  $maxItems = null
	): array
	{
		$errors = [];
		
		if ($minItems && count($data) < $minItems) {
			$errors[] = "Minimum {$minItems} items required, got " . count($data);
		}
		
		if ($maxItems && count($data) > $maxItems) {
			$errors[] = "Maximum {$maxItems} items allowed, got " . count($data);
		}
		
		if (!empty($requiredFields)) {
			foreach ($data as $index => $item) {
				if (!is_array($item)) continue;
				
				foreach ($requiredFields as $field) {
					if (!array_key_exists($field, $item) || empty($item[$field])) {
						$errors[] = "Item {$index}: Required field '{$field}' is missing or empty";
					}
				}
			}
		}
		
		return $errors;
	}
	
	/**
	 * Clean repeater data by removing empty items
	 *
	 * @param array $data
	 * @param array $fieldsToCheck Fields to check for emptiness
	 * @return array
	 */
	public function cleanRepeaterData(array $data, array $fieldsToCheck = []): array
	{
		return collect($data)->filter(function ($item) use ($fieldsToCheck) {
			if (!is_array($item)) {
				return !empty($item);
			}
			
			if (empty($fieldsToCheck)) {
				// Remove if all values are empty
				return collect($item)->filter(fn ($value) => !empty($value))->isNotEmpty();
			}
			
			// Remove if specified fields are empty
			foreach ($fieldsToCheck as $field) {
				if (!empty($item[$field] ?? null)) {
					return true;
				}
			}
			
			return false;
		})->values()->toArray();
	}
}
