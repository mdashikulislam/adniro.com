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

/*
 * For some methods of this class,
 * the system locale need to be set in the 'AppServiceProvider'
 * by calling this method: systemLocale()->setLocale($locale);
 */

use Throwable;

class Num
{
	/**
	 * Converts a number into a short version, eg: 1000 -> 1K
	 *
	 * Large number abbreviations:
	 * https://idlechampions.fandom.com/wiki/Large_number_abbreviations
	 *
	 * Note - PHP cannot handle large number like:
	 * Sextillion, Septillion, Octillion, Nonillion, Decillion, etc.
	 *
	 * @param float|int|string|null $value
	 * @param int $precision
	 * @param bool $roundThousands
	 * @return float|int|string
	 */
	public static function short(float|int|string|null $value, int $precision = 1, bool $roundThousands = true): float|int|string
	{
		if (empty($value)) return '0';
		if (!is_numeric($value)) return (string)$value;
		
		$value = (float)$value;
		$gap = 1000;
		$roundGap = $roundThousands ? 150 : 0;
		
		$multipliers = [
			['multiplier' => 1, 'suffix' => ''],           // Unit
			['multiplier' => $gap, 'suffix' => 'K'],       // Thousand
			['multiplier' => $gap ** 2, 'suffix' => 'M'],  // Million
			['multiplier' => $gap ** 3, 'suffix' => 'B'],  // Billion
			['multiplier' => $gap ** 4, 'suffix' => 'T'],  // Trillion
			['multiplier' => $gap ** 5, 'suffix' => 'q'],  // Quadrillion
			['multiplier' => $gap ** 6, 'suffix' => 'Q'],  // Quintillion
			['multiplier' => $gap ** 7, 'suffix' => 's'],  // Sextillion
			['multiplier' => $gap ** 8, 'suffix' => 'S'],  // Septillion
			['multiplier' => $gap ** 9, 'suffix' => 'o'],  // Octillion
			['multiplier' => $gap ** 10, 'suffix' => 'n'], // Nonillion
			['multiplier' => $gap ** 11, 'suffix' => 'd'], // Decillion
		];
		
		foreach ($multipliers as $index => $item) {
			$multiplier = $item['multiplier'];
			$suffix = $item['suffix'];
			
			$nextMultiplier = $multipliers[$index + 1]['multiplier'] ?? INF;
			
			if ($value >= $multiplier - $roundGap && $value < $nextMultiplier - $roundGap) {
				$shortenedValue = $value / $multiplier;
				// $shortenedValue = round($shortenedValue, $precision);
				$shortenedValue = self::localeFormat($shortenedValue, $precision);
				
				return $shortenedValue . $suffix;
			}
		}
		
		// Fallback for very large numbers
		return (string)$value;
	}
	
	/**
	 * Format/Short Time
	 *
	 * Usage:
	 * Num::shortTime(163, 'minutes');        // Output: "2 hours and 43 minutes"
	 * Num::shortTime(3661);                  // Output: "1 hour and 1 second"
	 * Num::shortTime(2592001, 'seconds');    // Output: "1 month and 1 second"
	 *
	 * @param int $time
	 * @param string $base
	 * @param bool $includeSmallerUnits
	 * @return string
	 */
	public static function shortTime(int $time, string $base = 'seconds', bool $includeSmallerUnits = true): string
	{
		$units = [
			'year'   => 31536000, // Approximation: 365 days per year
			'month'  => 2592000, // Approximation: 30 days per month
			'week'   => 604800,
			'day'    => 86400,
			'hour'   => 3600,
			'minute' => 60,
			'second' => 1,
		];
		
		// Convert the input time to seconds based on the base unit
		$timeInSeconds = match ($base) {
			'minutes' => $time * $units['minute'],
			'hours' => $time * $units['hour'],
			'days' => $time * $units['day'],
			'weeks' => $time * $units['week'],
			'months' => $time * $units['month'],
			'years' => $time * $units['year'],
			default => $time, // 'seconds' by default
		};
		
		$result = [];
		$previousUnitIncluded = false;
		
		foreach ($units as $unitName => $unitSeconds) {
			$unitValue = floor($timeInSeconds / $unitSeconds);
			
			if ($unitValue > 0) {
				$localizedUnit = trans_choice("global.$unitName", getPlural($unitValue));
				$result[] = "$unitValue $localizedUnit";
				$timeInSeconds %= $unitSeconds;
				
				if (!$includeSmallerUnits && $previousUnitIncluded) {
					break;
				}
				
				$previousUnitIncluded = true;
			}
		}
		
		return collect($result)->join(', ', t('_and_'));
	}
	
	/**
	 * @param float|int|string|null $value
	 * @param int $decimals
	 * @param bool $removeZeroAsDecimal
	 * @return float|int|string|null
	 */
	public static function localeFormat(
		float|int|string|null $value,
		int                   $decimals = 2,
		bool                  $removeZeroAsDecimal = true
	): float|int|string|null
	{
		// Convert string to numeric
		$value = self::getFloatRawFormat($value);
		
		if (!is_numeric($value)) return null;
		
		// Set locale for LC_NUMERIC (This is reset below)
		systemLocale()->setLocale(app()->getLocale(), LC_NUMERIC);
		
		// Get numeric formatting information & format '$value'
		$localeInfo = localeconv();
		$decPoint = $localeInfo['decimal_point'] ?? '.';
		$thousandsSep = $localeInfo['thousands_sep'] ?? ',';
		$value = number_format($value, $decimals, $decPoint, $thousandsSep);
		
		if ($removeZeroAsDecimal) {
			$value = self::removeZeroAsDecimal($value, $decimals, $decPoint);
		}
		
		systemLocale()->resetLcNumeric();
		
		return $value;
	}
	
	/**
	 * Transform the given number to display it using the Currency format settings
	 * NOTE: Transform non-numeric value
	 *
	 * @param float|int|string|null $value
	 * @param int|null $decimals
	 * @param string|null $decPoint
	 * @param string|null $thousandsSep
	 * @param bool $removeZeroAsDecimal
	 * @return float|int|string|null
	 */
	public static function format(
		float|int|string|null $value,
		int                   $decimals = null,
		string                $decPoint = null,
		string                $thousandsSep = null,
		bool                  $removeZeroAsDecimal = true
	): float|int|string|null
	{
		// Convert string to numeric
		$value = self::getFloatRawFormat($value);
		
		if (!is_numeric($value)) return null;
		
		$defaultCurrency = config('selectedCurrency', config('currency'));
		if (is_null($decimals)) {
			$decimals = (int)data_get($defaultCurrency, 'decimal_places', 2);
		}
		if (is_null($decPoint)) {
			$decPoint = data_get($defaultCurrency, 'decimal_separator', '.');
		}
		if (is_null($thousandsSep)) {
			$thousandsSep = data_get($defaultCurrency, 'thousand_separator', ',');
		}
		
		// Currency format - Ex: USD 100,234.56 | EUR 100 234,56
		$value = number_format($value, $decimals, $decPoint, $thousandsSep);
		
		if ($removeZeroAsDecimal) {
			$value = self::removeZeroAsDecimal($value, $decimals, $decPoint);
		}
		
		return $value;
	}
	
	/**
	 * Format a number before insert it in MySQL database
	 * NOTE: The DB column need to be decimal (or float)
	 *
	 * @param float|int|string|null $value
	 * @param string $decPoint
	 * @param bool $canSaveZero
	 * @return float|int|string|null
	 */
	public static function formatForDb(
		float|int|string|null $value,
		string                $decPoint = '.',
		bool                  $canSaveZero = true
	): float|int|string|null
	{
		$value = strval($value);
		$value = preg_replace('/^[0\s]+(.+)$/', '$1', $value);  // 0123 => 123 | 00 123 => 123
		$value = preg_replace('/^[.]+/', '0.', $value);         // .123 => 0.123
		
		if ($canSaveZero) {
			$value = ($value == 0 && strlen(trim($value)) > 0) ? 0 : $value;
			if ($value === 0) {
				return $value;
			} else {
				if (empty($value)) {
					return $value;
				}
			}
		}
		
		if ($decPoint == '.') {
			// For string ending by '.000' like 'XX.000',
			// Replace the '.000' by ',000' like 'XX,000' before removing the thousands separator
			$value = preg_replace('/\.\s?(0{3}+)$/', ',$1', $value);
			
			// Remove eventual thousands separator
			$value = str_replace(',', '', $value);
		}
		if ($decPoint == ',') {
			// Remove eventual thousands separator
			$value = str_replace('.', '', $value);
			
			// Always save in DB decimals with dot (.) instead of comma (,)
			$value = str_replace(',', '.', $value);
		}
		
		// Skip only numeric and dot characters
		$value = preg_replace('/[^\d.]/', '', $value);
		
		// Use the first dot as decimal point (All the next dots will be ignored)
		$tmp = explode('.', $value);
		if (!empty($tmp)) {
			$value = $tmp[0] . (isset($tmp[1]) ? '.' . $tmp[1] : '');
		}
		
		if (empty($value)) {
			return null;
		}
		
		return $value;
	}
	
	/**
	 * Get Float Raw Format
	 *
	 * @param float|int|string|null $value
	 * @return float|int|string|null
	 */
	public static function getFloatRawFormat(float|int|string|null $value): float|int|string|null
	{
		if (is_numeric($value)) return $value;
		if (!is_string($value)) return null;
		
		$value = trim($value);
		$value = strtr($value, [' ' => '']);
		$value = preg_replace('/ +/', '', $value);
		$value = str_replace(',', '.', $value);
		$value = preg_replace('/[^\d.]/', '', $value);
		
		if (empty($value)) return null;
		
		return castToString($value);
	}
	
	/**
	 * @param float|int|string|null $value
	 * @param array|null $itemCurrency
	 * @return string
	 */
	public static function money(float|int|string|null $value, ?array $itemCurrency = []): string
	{
		$value = self::applyCurrencyRate($value, $itemCurrency);
		
		if (config('settings.other.decimals_superscript')) {
			return static::moneySuperscript($value);
		}
		
		$currency = !empty($itemCurrency) ? $itemCurrency : config('selectedCurrency', config('currency'));
		
		$decimals = (int)data_get($currency, 'decimal_places', 2);
		$decPoint = data_get($currency, 'decimal_separator', '.');
		$thousandsSep = data_get($currency, 'thousand_separator', ',');
		
		$value = self::format($value, $decimals, $decPoint, $thousandsSep);
		
		// In line current
		if (data_get($currency, 'in_left') == 1) {
			$value = data_get($currency, 'symbol') . $value;
		} else {
			$value = $value . ' ' . data_get($currency, 'symbol');
		}
		
		return castToString($value);
	}
	
	/**
	 * @param float|int|string|null $value
	 * @param array|null $itemCurrency
	 * @return string
	 */
	public static function moneySuperscript(float|int|string|null $value, ?array $itemCurrency = []): string
	{
		$value = self::format($value);
		$currency = !empty($itemCurrency) ? $itemCurrency : config('selectedCurrency', config('currency'));
		
		$decPoint = data_get($currency, 'decimal_separator', '.');
		$tmp = explode($decPoint, $value);
		
		$integer = $tmp[0] ?? $value;
		$decimal = $tmp[1] ?? '00';
		$currencySymbol = data_get($currency, 'symbol');
		
		$value = (data_get($currency, 'in_left') == 1)
			? $currencySymbol . $integer . '<sup>' . $decimal . '</sup>'
			: $integer . '<sup>' . $currencySymbol . $decimal . '</sup>';
		
		return castToString($value);
	}
	
	/**
	 * Remove decimal value if it's null
	 *
	 * Note:
	 * Remove unnecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
	 * Intentionally does not affect partials, eg "1.50" -> "1.50"
	 *
	 * @param float|int|string|null $value
	 * @param int|null $decimals
	 * @param string|null $decPoint
	 * @return float|int|string|null
	 */
	public static function removeZeroAsDecimal(
		float|int|string|null $value,
		?int                  $decimals = null,
		?string               $decPoint = null
	): float|int|string|null
	{
		if ((int)$decimals <= 0) return $value;
		
		$decPoint ??= '.';
		$defaultDecimal = str_pad('', (int)$decimals, '0');
		
		return str_replace($decPoint . $defaultDecimal, '', strval($value));
	}
	
	/**
	 * @param float|int|string|null $value
	 * @param array|null $itemCurrency
	 * @return float|int|string|null
	 */
	public static function applyCurrencyRate(float|int|string|null $value, ?array $itemCurrency = []): float|int|string|null
	{
		if (!is_numeric($value)) return $value;
		
		// Get the selected currency data
		$currency = !empty($itemCurrency) ? $itemCurrency : config('selectedCurrency', config('currency'));
		
		// Get the currency rate
		$currencyRate = self::getCurrencyRate($currency);
		
		// Apply the currency rate
		try {
			$value = $value * $currencyRate;
		} catch (Throwable $e) {
			// Debug
		}
		
		return $value;
	}
	
	/**
	 * Get the currency rate
	 *
	 * @param array|null $currency
	 * @return float|int
	 */
	public static function getCurrencyRate(?array $currency = []): float|int
	{
		$defaultRate = 1;
		
		if (empty($currency)) return $defaultRate;
		
		$rate = data_get($currency, 'rate', $defaultRate);
		$rate = castToString($rate, $defaultRate);
		if (!is_numeric($rate)) {
			$rate = str_contains($rate, '.') ? floatval($rate) : intval($rate);
		}
		
		if (!is_numeric($rate)) return $defaultRate;
		
		return $rate;
	}
	
	/**
	 * Clean Float Value
	 * Fixed: MySQL don't accept the comma format number
	 *
	 * This function takes the last comma or dot (if any) to make a clean float,
	 * ignoring thousands separator, currency or any other letter.
	 *
	 * Example:
	 * $num = '1.999,369€';
	 * var_dump(Num::toFloat($num)); // float(1999.369)
	 * $otherNum = '126,564,789.33 m²';
	 * var_dump(Num::toFloat($otherNum)); // float(126564789.33)
	 *
	 * @param float|int|string|null $value
	 * @return float|int
	 */
	public static function toFloat(float|int|string|null $value): float|int
	{
		$value = strval($value);
		
		// Check negative numbers
		$isNegative = false;
		if (str_starts_with(trim($value), '-')) {
			$isNegative = true;
		}
		
		$dotPos = strrpos($value, '.');
		$commaPos = strrpos($value, ',');
		
		$dotPos = is_numeric($dotPos) ? $dotPos : 0;
		$commaPos = is_numeric($commaPos) ? $commaPos : 0;
		
		$isDotAfterComma = ($dotPos > $commaPos);
		$isCommaAfterDot = ($commaPos > $dotPos);
		
		$sepPos = $isDotAfterComma ? $dotPos : ($isCommaAfterDot ? $commaPos : 0);
		
		if ($sepPos == 0) {
			$value = preg_replace('/\D/', '', $value);
			if ($isNegative) {
				$value = '-' . $value;
			}
			
			return floatval($value);
		}
		
		$integer = preg_replace('/\D/', '', substr($value, 0, $sepPos));
		$decimal = preg_replace('/\D/', '', substr($value, $sepPos + 1, strlen($value)));
		$decimal = rtrim($decimal, '0');
		
		if (intval($decimal) == 0) {
			$value = $integer;
			if ($isNegative) {
				$value = '-' . $value;
			}
			
			return intval($value);
		}
		
		$value = $integer . '.' . $decimal;
		if ($isNegative) {
			$value = '-' . $value;
		}
		
		return floatval($value);
	}
	
	/**
	 * Convert the given number to its file size equivalent.
	 *
	 * @param float|int $size
	 * @return string
	 */
	public static function fileSize(float|int $size): string
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		
		for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
			$size /= 1024;
		}
		
		return round($size, 2) . ' ' . $units[$i];
	}
	
	/**
	 * @param float|int $value
	 * @param float|int $min
	 * @return float|int
	 */
	public static function clampMin(float|int $value, float|int $min = PHP_INT_MIN): float|int
	{
		return max($value, $min);
	}
	
	/**
	 * @param float|int $value
	 * @param float|int $max
	 * @return float|int
	 */
	public static function clampMax(float|int $value, float|int $max = PHP_INT_MAX): float|int
	{
		return min($value, $max);
	}
}
