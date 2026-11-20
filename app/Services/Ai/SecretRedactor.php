<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

/*
 * Removes API keys and credentials from error messages before they are stored or shown to staff
 * (adniro:ai). Provider SDKs sometimes echo the request URL, headers or body in their errors.
 */

final class SecretRedactor
{
	public const MAX_LENGTH = 480;
	
	private const PATTERNS = [
		'/\b(sk|pk|key|xai|gsk)[-_][A-Za-z0-9_\-]{8,}/i'                     => '[redacted]',
		'/\bAIza[0-9A-Za-z_\-]{20,}/'                                       => '[redacted]',
		'/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/=\-]+/i'                       => '$1 [redacted]',
		'/\b(x-api-key|x-goog-api-key|api-key)\s*:\s*\S+/i'                  => '$1: [redacted]',
		'/([?&](?:key|api_key|apikey|access_token|token)=)[^&\s"\']+/i'     => '$1[redacted]',
		'/("(?:api_key|apiKey|key|token|access_token|secret)"\s*:\s*")[^"]*"/' => '$1[redacted]"',
	];
	
	public static function redact(string $message): string
	{
		foreach (self::PATTERNS as $pattern => $replacement) {
			$message = preg_replace($pattern, $replacement, $message) ?? $message;
		}
		
		return Str::limit(trim($message), self::MAX_LENGTH);
	}
}
