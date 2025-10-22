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

namespace App\Observers\Traits;

trait CategoryTrait
{
	/**
	 * Fix required columns
	 *
	 * @param $category
	 * @return mixed
	 */
	protected function fixRequiredColumns($category): mixed
	{
		// The 'type' column is a not nullable enum, so required
		if (isset($category->type) && empty($category->type)) {
			if (!empty($category->parent)) {
				if (!empty($category->parent->type)) {
					$category->type = $category->parent->type;
				}
			}
			if (empty($category->type)) {
				$category->type = 'classified';
			}
		}
		
		return $category;
	}
}
