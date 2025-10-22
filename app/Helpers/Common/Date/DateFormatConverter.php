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

/**
 * Handles date format conversion between different syntaxes
 */
class DateFormatConverter
{
	/**
	 * Determine if a date format uses ISO formatting syntax
	 *
	 * @param $format
	 * @return bool
	 */
	public static function isIsoFormat($format): bool
	{
		$isIsoFormat = false;
		
		$splitChars = preg_split('#([ \-/.,:;])#', $format);
		$splitChars = array_filter($splitChars);
		
		if (!empty($splitChars)) {
			foreach ($splitChars as $char) {
				if (in_array($char, self::diffBetweenIsoAndDateTimeFormats())) {
					$isIsoFormat = true;
					break;
				}
			}
		}
		
		return $isIsoFormat;
	}
	
	/**
	 * Convert strftime format to standard date format
	 * Equivalent to `date_format_to( $format, 'date' )`
	 *
	 * @param string $strfFormat A `strftime()` date/time format
	 * @return bool|string
	 */
	public static function strftimeToDateFormat(string $strfFormat): bool|string
	{
		return self::dateFormatTo($strfFormat, 'date');
	}
	
	/**
	 * Convert standard date format to strftime format
	 * Equivalent to `convert_datetime_format_to( $format, 'strf' )`
	 *
	 * @param string $dateFormat A `date()` date/time format
	 * @return bool|string
	 */
	public static function dateToStrftimeFormat(string $dateFormat): bool|string
	{
		return self::dateFormatTo($dateFormat, 'strf');
	}
	
	/**
	 * Convert date/time format between `date()` and `strftime()` syntaxes
	 *
	 * Timezone conversion is done for Unix. Windows users must exchange %z and %Z.
	 *
	 * Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r
	 * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
	 *
	 * @param string $format The format to parse.
	 * @param string $syntax The format's syntax. Either 'strf' for `strtime()` or 'date' for `date()`.
	 * @return bool|string Returns a string formatted according $syntax using the given $format or `false`.
	 * @link http://php.net/manual/en/function.strftime.php#96424
	 *
	 * @example Convert `%A, %B %e, %Y, %l:%M %P` to `l, F j, Y, g:i a`, and vice versa for "Saturday, March 10, 2001, 5:16 pm"
	 */
	private static function dateFormatTo(string $format, string $syntax): bool|string
	{
		// http://php.net/manual/en/function.strftime.php
		$strfSyntax = [
			// Day - no strf eq : S (created one called %O)
			'%O', '%d', '%a', '%e', '%A', '%u', '%w', '%j',
			// Week - no date eq : %U, %W
			'%V',
			// Month - no strf eq : n, t
			'%B', '%m', '%b', '%-m',
			// Year - no strf eq : L; no date eq : %C, %g
			'%G', '%Y', '%y',
			// Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
			'%P', '%p', '%l', '%I', '%H', '%M', '%S',
			// Timezone - no strf eq : e, I, P, Z
			'%z', '%Z',
			// Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
			'%s',
		];
		
		// http://php.net/manual/en/function.date.php
		$dateSyntax = [
			'S', 'd', 'D', 'j', 'l', 'N', 'w', 'z',
			'W',
			'F', 'm', 'M', 'n',
			'o', 'Y', 'y',
			'a', 'A', 'g', 'h', 'H', 'i', 's',
			'O', 'T',
			'U',
		];
		
		switch ($syntax) {
			case 'date':
				$from = $strfSyntax;
				$to = $dateSyntax;
				break;
			
			case 'strf':
				$from = $dateSyntax;
				$to = $strfSyntax;
				break;
			
			default:
				return false;
		}
		
		$pattern = array_map(
			function ($s) {
				return '/(?<!\\\\|\%)' . $s . '/';
			},
			$from
		);
		
		$format = preg_replace($pattern, $to, $format);
		
		return castToString($format);
	}
	
	/**
	 * Get format tokens that differ between ISO and DateTime formats
	 *
	 * @return array Array of format tokens unique to ISO format
	 */
	private static function diffBetweenIsoAndDateTimeFormats(): array
	{
		return array_diff(self::isoFormatReplacement(), self::dateTimeFormatReplacement());
	}
	
	/**
	 * Get ISO date format replacement tokens
	 *
	 * @return array Array of ISO format tokens
	 * @link https://carbon.nesbot.com/docs/#api-localization
	 */
	private static function isoFormatReplacement(): array
	{
		return [
			'OD', 'OM', 'OY', 'OH', 'Oh', 'Om', 'Os', 'D', 'DD', 'Do',
			'd', 'dd', 'ddd', 'dddd', 'DDD', 'DDDD', 'DDDo', 'e', 'E',
			'H', 'HH', 'h', 'hh', 'k', 'kk', 'm', 'mm', 'a', 'A', 's', 'ss', 'S', 'SS', 'SSS', 'SSSS', 'SSSSS', 'SSSSSS', 'SSSSSSS', 'SSSSSSSS', 'SSSSSSSSS',
			'M', 'MM', 'MMM', 'MMMM', 'Mo', 'Q', 'Qo',
			'G', 'GG', 'GGG', 'GGGG', 'GGGGG', 'g', 'gg', 'ggg', 'gggg', 'ggggg', 'W', 'WW', 'Wo', 'w', 'ww', 'wo',
			'x', 'X',
			'Y', 'YY', 'YYYY', 'YYYYY',
			'z', 'zz', 'Z', 'ZZ',
			// Macro-formats
			'LT', 'LTS', 'L', 'l', 'LL', 'll', 'LLL', 'lll', 'LLLL', 'llll',
		];
	}
	
	/**
	 * Get standard DateTime format replacement tokens
	 *
	 * @return array Array of DateTime format tokens
	 * @link https://www.php.net/manual/en/datetime.format.php
	 */
	private static function dateTimeFormatReplacement(): array
	{
		return [
			// Day
			'd', 'D', 'j', 'l', 'N', 'S', 'w', 'z',
			// Week
			'W',
			// Month
			'F', 'm', 'M', 'n', 't',
			// Year
			'L', 'o', 'Y', 'y',
			// Time
			'a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u', 'v',
			// Timezone
			'e', 'I', 'O', 'P', 'p', 'T', 'Z',
			// Full Date/Time
			'c', 'r', 'U',
		];
	}
	
	/**
	 * Get strftime format replacement tokens
	 *
	 * @return array Array of strftime format tokens
	 * @link https://www.php.net/manual/en/function.strftime.php
	 */
	private static function strftimeFormatReplacement(): array
	{
		return [
			// Day
			'%a', '%A', '%d', '%e', '%j', '%u', '%w',
			// Week
			'%U', '%V', '%W',
			// Month
			'%b', '%B', '%h', '%m',
			// Year
			'%C', '%g', '%G', '%y', '%Y',
			// Time
			'%H', '%k', '%I', '%l', '%M', '%p', '%P', '%r', '%R', '%S', '%T', '%X', '%z', '%Z',
			// Time and Date Stamps
			'%c', '%D', '%F', '%s', '%x',
			// Miscellaneous
			'%n', '%t', '%%',
		];
	}
}
