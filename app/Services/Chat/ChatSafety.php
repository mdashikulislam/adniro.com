<?php

namespace App\Services\Chat;

/*
 * A gentle scam hint (adniro): messages that carry a link, an email address, a phone number or wording about
 * paying outside the site get a "be careful" line under them for the reader. Nothing is blocked.
 */
class ChatSafety
{
	private const LINK = '~(https?://|www\.)\S+|\b[a-z0-9-]+\.(com|net|org|io|co|me|ly|xyz|info|biz|app|link|site|online|shop)\b~iu';
	private const EMAIL = '~[\w.+-]+@[\w-]+\.[\w.-]+~u';
	private const PHONE = '~(?:\+|00)?\d[\d\s().-]{7,}\d~u';
	private const PAY_OUTSIDE = '~\b(western union|moneygram|gift ?cards?|crypto|bitcoin|usdt|wire transfer|bank transfer|deposit|advance payment|pay(ment)? (first|upfront|in advance)|verification code|otp|whatsapp|telegram|paypal friends)\b~iu';

	/** What the sender is asked about before sending (chat-guard.js): contact details, or paying outside the site. */
	private const ASK_BEFORE_SENDING = ['contact' => [self::EMAIL, self::PHONE], 'pay' => [self::PAY_OUTSIDE]];
	private const DELIMITER = '~';

	public static function needsCaution(?string $body): bool
	{
		$body = (string)$body;
		if ($body === '') {
			return false;
		}

		foreach ([self::LINK, self::EMAIL, self::PHONE, self::PAY_OUTSIDE] as $pattern) {
			if (preg_match($pattern, $body) === 1) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The same patterns for the browser, which asks before a risky message is sent. One list, so the question
	 * and the reader's "be careful" line cannot drift apart.
	 *
	 * @return array<int, array{kind: string, source: string, flags: string}>
	 */
	public static function browserPatterns(): array
	{
		$out = [];
		foreach (self::ASK_BEFORE_SENDING as $kind => $patterns) {
			foreach ($patterns as $pattern) {
				$end = strrpos($pattern, self::DELIMITER);
				$out[] = ['kind' => $kind, 'source' => substr($pattern, 1, $end - 1), 'flags' => substr($pattern, $end + 1)];
			}
		}

		return $out;
	}
}
