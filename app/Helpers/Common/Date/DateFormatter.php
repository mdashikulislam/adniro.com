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

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SavedSearchController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Web\Front\Account\AccountBaseController;
use App\Http\Controllers\Web\Front\HomeController;
use App\Http\Controllers\Web\Front\Post\Show\ShowController;
use App\Http\Controllers\Web\Front\Search\SearchController;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Handles date formatting operations
 */
class DateFormatter
{
	/**
	 * Format Carbon instance using locale-specific formatting
	 * (i.e. using the current locale)
	 *
	 * You can set the current locale using setlocale() https://www.php.net/manual/en/function.setlocale.php
	 *
	 * @param $value
	 * @param string $dateType
	 * @return string
	 */
	public static function format($value, string $dateType = 'date'): string
	{
		if ($value instanceof Carbon) {
			$dateFormat = self::getAppDateFormat($dateType);
			
			try {
				if (DateFormatConverter::isIsoFormat($dateFormat)) {
					$value = $value->isoFormat($dateFormat);
				} else {
					$value = $value->translatedFormat($dateFormat);
				}
			} catch (Throwable $e) {
			}
		}
		
		return castToString($value);
	}
	
	/**
	 * Generate human-readable relative time with optional popover for exact timestamp
	 *
	 * @param $value
	 * @return string
	 */
	public static function fromNow($value): string
	{
		if (!$value instanceof Carbon) {
			return castToString($value);
		}
		
		$formattedDate = self::format($value, 'datetime');
		
		// From Now Parameters
		$modifier = config('settings.app.date_from_now_modifier', 'DIFF_RELATIVE_TO_NOW');
		$syntax = defined('\Carbon\CarbonInterface::' . $modifier)
			? constant('\Carbon\CarbonInterface::' . $modifier)
			: CarbonInterface::DIFF_RELATIVE_TO_NOW;
		$short = (config('settings.app.date_from_now_short', '0') == '1');
		
		if (doesRequestIsFromWebClient() || isFromAdminPanel()) {
			$popover = ' data-bs-container="body"';
			$popover .= ' data-bs-toggle="popover"';
			$popover .= ' data-bs-trigger="hover"';
			$popover .= ' data-bs-placement="bottom"';
			$popover .= ' data-bs-content="' . $formattedDate . '"';
			
			if (config('lang.direction') == 'rtl') {
				$popover = ' data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $formattedDate . '"';
			}
			
			$out = '<span style="cursor: help;"' . $popover . '>';
			$out .= $value->fromNow($syntax, $short);
			$out .= '</span>';
			
			$value = $out;
		} else {
			$value = $value->fromNow($syntax, $short);
		}
		
		return castToString($value);
	}
	
	/**
	 * Context-aware date formatting based on current route and settings
	 *
	 * @param $value
	 * @param bool $force
	 * @return string
	 */
	public static function customFromNow($value, bool $force = false): string
	{
		$isFromPostsList = (
			config('settings.listings_list.date_from_now')
			&& (
				(
					isFromApi()
					&& (
						(
							routeActionHas(PostController::class . '@index')
							&& !request()->filled('belongLoggedUser')
						)
						|| routeActionHas(SectionController::class)
						|| routeActionHas(SavedSearchController::class)
					)
				)
				|| (
					!isFromApi()
					&& (
						routeActionHas(getClassNamespaceName(SearchController::class))
						|| routeActionHas(HomeController::class)
						|| routeActionHas(getClassNamespaceName(AccountBaseController::class))
					)
				)
			)
		);
		
		$isFromPostDetails = (
			config('settings.listing_page.date_from_now')
			&& (
				(isFromApi() && (routeActionHas(PostController::class . '@show')))
				|| (!isFromApi() && (routeActionHas(ShowController::class)))
			)
		);
		
		if ($force) {
			return self::fromNow($value);
		}
		if ($isFromPostsList) {
			return self::fromNow($value);
		} else if ($isFromPostDetails) {
			return self::fromNow($value);
		} else {
			if (!$value instanceof Carbon) {
				return castToString($value);
			}
			
			return self::format($value, 'datetime');
		}
	}
	
	/**
	 * Get the appropriate date format based on context and configuration
	 *
	 * @param string $dateType
	 * @return string
	 */
	private static function getAppDateFormat(string $dateType = 'date'): string
	{
		$adminDateFormat = ($dateType == 'datetime')
			? config('settings.app.datetime_format', config('larapen.core.datetimeFormat.default'))
			: config('settings.app.date_format', config('larapen.core.dateFormat.default'));
		
		$langFrontDateFormat = ($dateType == 'datetime') ? config('lang.datetime_format') : config('lang.date_format');
		$frontDateFormat = !empty($langFrontDateFormat) ? $langFrontDateFormat : $adminDateFormat;
		
		$countryFrontDateFormat = ($dateType == 'datetime') ? config('country.datetime_format') : config('country.date_format');
		$frontDateFormat = !empty($countryFrontDateFormat) ? $countryFrontDateFormat : $frontDateFormat;
		
		$dateFormat = isAdminPanel() ? $adminDateFormat : $frontDateFormat;
		
		if (empty($dateFormat)) {
			$dateFormat = ($dateType == 'datetime') ? config('larapen.core.datetimeFormat.default') : config('larapen.core.dateFormat.default');
		}
		
		// For stats short dates
		if ($dateType == 'stats') {
			$dateFormat = !config('settings.app.php_specific_date_format') ? 'MMM DD' : '%b %d';
		}
		
		// For backup dates
		if ($dateType == 'backup') {
			$dateFormat = !config('settings.app.php_specific_date_format') ? 'DD MMMM YYYY, HH:mm' : '%d %B %Y, %H:%M';
		}
		
		if (str_contains($dateFormat, '%')) {
			$dateFormat = DateFormatConverter::strftimeToDateFormat($dateFormat);
		}
		
		if (!is_string($dateFormat)) {
			$dateFormat = !config('settings.app.php_specific_date_format')
				? (($dateType == 'datetime') ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD')
				: (($dateType == 'datetime') ? '%Y-%m-%d %H:%M' : '%Y-%m-%d');
		}
		
		return $dateFormat;
	}
}
