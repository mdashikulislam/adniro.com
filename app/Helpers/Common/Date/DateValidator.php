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

namespace App\Helpers\Common\Date;

use Illuminate\Support\Carbon;
use Throwable;

/**
 * Validates date-related inputs
 */
class DateValidator
{
	/**
	 * Validate if a string represents a valid date
	 *
	 * @param string|null $value
	 * @return bool
	 */
	public static function isValid(?string $value): bool
	{
		if (empty($value)) return false;
		
		try {
			$date = Carbon::parse($value);
		} catch (Throwable $e) {
			return false;
		}
		
		return true;
	}
}
