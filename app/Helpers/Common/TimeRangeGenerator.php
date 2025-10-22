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

class TimeRangeGenerator
{
	private const UNITS = [
		'second' => 1,
		'minute' => 60,
		'hour'   => 3600,
		'day'    => 86400,
		'week'   => 604800,
		'month'  => 2592000,  // 30 days approximation
		'year'   => 31536000, // 365 days approximation
	];
	
	private const MAX_COUNTS = [
		'second' => 59,
		'minute' => 59,
		'hour'   => 23,
		'day'    => 6,
		'week'   => 3,
		'month'  => 11,
		'year'   => 5,
	];
	
	/**
	 * @param string $startUnit
	 * @param string $endUnit
	 * @param array $limits
	 * @param bool $includeSmallerUnits
	 * @param string|null $keyUnit Unit to convert keys to (null keeps keys in startUnit)
	 * @return array
	 */
	public static function generateRange(
		string  $startUnit = 'second',
		string  $endUnit = 'year',
		array   $limits = [],
		bool    $includeSmallerUnits = true,
		?string $keyUnit = null
	): array
	{
		// Validate units
		if (!array_key_exists($startUnit, self::UNITS) || !array_key_exists($endUnit, self::UNITS)) {
			throw new \InvalidArgumentException('Invalid unit specified');
		}
		
		if ($keyUnit !== null && !array_key_exists($keyUnit, self::UNITS)) {
			throw new \InvalidArgumentException('Invalid key unit specified');
		}
		
		// Ensure start is before end
		if (self::UNITS[$startUnit] > self::UNITS[$endUnit]) {
			throw new \InvalidArgumentException('Start unit must be smaller than end unit');
		}
		
		$range = [];
		$unitsToProcess = array_keys(self::UNITS);
		$startIndex = array_search($startUnit, $unitsToProcess);
		$endIndex = array_search($endUnit, $unitsToProcess);
		$startUnitMultiplier = self::UNITS[$startUnit];
		$keyUnitMultiplier = $keyUnit ? self::UNITS[$keyUnit] : $startUnitMultiplier;
		
		for ($i = $startIndex; $i <= $endIndex; $i++) {
			$unit = $unitsToProcess[$i];
			$maxCount = $limits[$unit] ?? self::MAX_COUNTS[$unit];
			$maxCount = min($maxCount, self::MAX_COUNTS[$unit]);
			
			// Calculate how many startUnits this unit represents
			$unitInStartUnits = self::UNITS[$unit] / $startUnitMultiplier;
			
			for ($count = 1; $count <= $maxCount; $count++) {
				$startUnitValue = $count * $unitInStartUnits;
				$seconds = $startUnitValue * $startUnitMultiplier;
				
				// Convert key to specified unit if requested, otherwise keep in startUnit
				$key = $keyUnit ? floor($seconds / $keyUnitMultiplier) : $startUnitValue;
				
				$range[$key] = self::formatTime($seconds, $includeSmallerUnits);
			}
		}
		
		return $range;
	}
	
	/**
	 * @param int $seconds
	 * @param bool $includeSmallerUnits
	 * @return string
	 */
	private static function formatTime(int $seconds, bool $includeSmallerUnits): string
	{
		return Num::shortTime($seconds, 'seconds', $includeSmallerUnits);
	}
}

/*
 * Default: seconds to 5 years
 * TimeRangeGenerator::generateRange();
 *
 * Custom examples:
 * From minutes to months with custom limits
 * TimeRangeGenerator::generateRange(
 *     startUnit: 'minute',
 *     endUnit: 'month',
 *     limits: [
 *         'minute' => 30,
 *         'hour' => 12,
 *         'day' => 5,
 *         'week' => 2,
 *         'month' => 6
 *     ],
 *     keyUnit: 'second'
 * );
 *
 * Hours to years, without smaller units
 * TimeRangeGenerator::generateRange(
 *     startUnit: 'hour',
 *     endUnit: 'year',
 *     includeSmallerUnits: false
 * );
 */
