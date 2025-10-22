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

use Illuminate\Pagination\LengthAwarePaginator;

class PaginationHelper
{
	/**
	 * @param \Illuminate\Pagination\LengthAwarePaginator $paginated
	 * @param array $options
	 * @return \Illuminate\Pagination\LengthAwarePaginator
	 */
	public static function adjustSides(LengthAwarePaginator $paginated, array $options = []): LengthAwarePaginator
	{
		$total = $paginated->total();
		$onEachSide = self::calculateOnEachSide($total, $options);
		
		$paginated->onEachSide($onEachSide);
		
		return $paginated;
	}
	
	/**
	 * @param int $total
	 * @param array $options
	 * @return int|mixed|string
	 */
	private static function calculateOnEachSide(int $total, array $options = []): mixed
	{
		$thresholds = array_merge([
			'xs' => 1000,
			'sm' => 5000,
			'md' => 10000,
			'lg' => 100000,
		], $options['thresholds'] ?? []);
		
		$defaultSideValue = 3;
		$sideValues = array_merge([
			'xs' => 1,
			'sm' => 2,
			'md' => $defaultSideValue,
			'lg' => $defaultSideValue,
			'xl' => $defaultSideValue,
		], $options['sides'] ?? []);
		
		if ($total <= $thresholds['xs']) {
			return $sideValues['xs'];
		} else if ($total <= $thresholds['sm']) {
			return $sideValues['sm'];
		} else if ($total <= $thresholds['md']) {
			return $sideValues['md'];
		} else if ($total <= $thresholds['lg']) {
			return $sideValues['lg'];
		} else {
			return $sideValues['xl'];
		}
	}
}
