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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NestedSetSeeder
{
	/**
	 * @param string $tableName
	 * @param array $entries
	 * @param int $startRgt
	 * @return int
	 */
	public static function insertEntries(string $tableName, array $entries, int $startRgt = 1): int
	{
		$currentRgt = $startRgt;
		
		foreach ($entries as $entry) {
			$currentRgt = self::insertEntry($tableName, $entry, null, 0, $currentRgt);
		}
		
		// Return the next available position
		return $currentRgt;
	}
	
	/**
	 * @param string $tableName
	 * @param array $entry
	 * @param int|null $parentId
	 * @param int $depth
	 * @param int $leftValue
	 * @return int
	 */
	private static function insertEntry(string $tableName, array $entry, ?int $parentId, int $depth, int $leftValue): int
	{
		$children = $entry['children'] ?? [];
		
		// Free memory immediately
		if (array_key_exists('children', $entry)) {
			unset($entry['children']);
		}
		
		// Calculate space needed for all children
		$childrenSpace = self::calculateChildrenSpace($children);
		
		// Set entry values
		$entry['parent_id'] = $parentId;
		$entry['depth'] = $depth;
		$entry['lft'] = $leftValue;
		$entry['rgt'] = $leftValue + $childrenSpace + 1;
		
		// Check optional columns
		if (!Schema::hasColumn($tableName, 'parent_id')) {
			unset($entry['parent_id']);
		}
		if (!Schema::hasColumn($tableName, 'depth')) {
			unset($entry['depth']);
		}
		
		// Convert array values to JSON strings for database insertion
		$entry = prepareArraysForDatabase($entry);
		
		// Insert current entry
		$entryId = DB::table($tableName)->insertGetId($entry);
		
		// Insert children
		$childLeft = $leftValue + 1;
		foreach ($children as $child) {
			$childLeft = self::insertEntry($tableName, $child, $entryId, $depth + 1, $childLeft);
		}
		
		return $leftValue + $childrenSpace + 2;
	}
	
	/**
	 * @param array $children
	 * @return int
	 */
	private static function calculateChildrenSpace(array $children): int
	{
		if (empty($children)) {
			return 0;
		}
		
		$totalSpace = 0;
		
		foreach ($children as $child) {
			// Each child needs 2 positions (left and right)
			$childSpace = 2;
			
			// Add space needed for this child's children recursively
			$grandchildrenSpace = self::calculateChildrenSpace($child['children'] ?? []);
			$childSpace += $grandchildrenSpace;
			
			$totalSpace += $childSpace;
		}
		
		return $totalSpace;
	}
	
	/**
	 * Get the next available position from existing data
	 *
	 * @param string $tableName
	 * @return int
	 */
	public static function getNextRgtValue(string $tableName): int
	{
		$maxRgt = DB::table($tableName)->max('rgt');
		
		return $maxRgt ? $maxRgt + 1 : 1;
	}
}
