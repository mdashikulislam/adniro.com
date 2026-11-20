<?php

namespace App\Services\Ai;

/*
 * Removes links, email addresses and phone numbers from AI output shown to people (adniro:ai).
 * A phone number needs at least 9 digits, so dates, year ranges, sizes and mileages stay.
 */

final class ContactStripper
{
	public const MIN_PHONE_DIGITS = 9;
	
	public static function strip(string $text): string
	{
		$text = preg_replace('~\b(?:https?://|www\.)\S+~iu', '', $text) ?? '';
		$text = preg_replace('~[\w.+-]+@[\w-]+(?:\.[\w-]+)+~u', '', $text) ?? '';
		
		return preg_replace_callback(
			'~\+?\d[\d \t().-]{6,}\d~u',
			fn (array $m) => self::isPhoneNumber($m[0]) ? '' : $m[0],
			$text
		) ?? '';
	}
	
	private static function isPhoneNumber(string $candidate): bool
	{
		if (preg_match_all('/\d/', $candidate) < self::MIN_PHONE_DIGITS) {
			return false;
		}
		
		// Two dates in a row ("2024-01-01 2024-12-31") have enough digits but are not a number to call
		return !preg_match('~^\d{4}-\d{2}-\d{2}\b|^\d{1,2}[./]\d{1,2}[./]\d{2,4}\b~', trim($candidate));
	}
}
