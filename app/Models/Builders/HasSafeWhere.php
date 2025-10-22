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

namespace App\Models\Builders;

trait HasSafeWhere
{
	/**
	 * Validate if the where clause parameters are valid
	 * Prevents "Illegal operator and value combination" errors
	 */
	protected function isValidWhereClause($column, $operator = null, $value = null): bool
	{
		// Handle closure case
		if ($column instanceof \Closure) {
			return true;
		}
		
		$argCount = func_num_args();
		
		// Handle 2-parameter case: where($column, $value)
		if ($argCount == 2 && $operator !== null) {
			return true; // This is valid: where('column', 'value')
		}
		
		// If operator is null, but we have 3 parameters, it's invalid
		if ($operator === null && $argCount == 3) {
			return false;
		}
		
		// Normalize operator to lowercase for comparison
		$normalizedOperator = strtolower(trim($operator ?? ''));
		
		// Skip if value is empty for comparison operators
		if (
			in_array($normalizedOperator, ['<', '>', '<=', '>='])
			&& ($value === '' || $value === null)
		) {
			return false;
		}
		
		// Skip if value is empty for LIKE operators
		if (
			in_array($normalizedOperator, ['like', 'not like'])
			&& ($value === '' || $value === null)
		) {
			return false;
		}
		
		// Handle IN operators - need non-empty array
		if (
			in_array($normalizedOperator, ['in', 'not in'])
			&& (!is_array($value) || empty($value))
		) {
			return false;
		}
		
		// Handle BETWEEN operators - need array with 2 values
		if (
			in_array($normalizedOperator, ['between', 'not between'])
			&& (!is_array($value) || count($value) !== 2)
		) {
			return false;
		}
		
		// For equality operators, empty values are generally acceptable
		if (in_array($normalizedOperator, ['=', '!=', '<>', 'is', 'is not'])) {
			return true;
		}
		
		// If we get here, it's likely a valid combination
		return true;
	}
	
	/**
	 * Add a safe where clause that validates input before execution
	 * Prevents "Illegal operator and value combination" errors
	 */
	public function safeWhere($column, $operator = null, $value = null, $boolean = 'and'): static
	{
		// Validate input before proceeding - prevent "Illegal operator and value combination" error
		if (!$this->isValidWhereClause($column, $operator, $value)) {
			return $this; // Skip invalid where clauses
		}
		
		return $this->where($column, $operator, $value, $boolean);
	}
	
	/**
	 * Add a safe or where clause
	 */
	public function orSafeWhere($column, $operator = null, $value = null): static
	{
		// Validate input before proceeding
		if (!$this->isValidWhereClause($column, $operator, $value)) {
			return $this; // Skip invalid where clauses
		}
		
		return $this->orWhere($column, $operator, $value);
	}
}
