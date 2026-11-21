<?php

namespace Tests\Unit\Ai;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Services\Ai\SecretRedactor;
use PHPUnit\Framework\TestCase;

class SecretRedactorTest extends TestCase
{
	#[DataProvider('secrets')]
	public function test_secrets_are_removed_from_error_messages(string $message, string $secret): void
	{
		// Act
		$clean = SecretRedactor::redact($message);
		
		// Assert
		$this->assertStringNotContainsString($secret, $clean);
		$this->assertStringContainsString('[redacted]', $clean);
	}
	
	public static function secrets(): array
	{
		// Built at run time so the repository holds nothing shaped like a real key (secret scanners flag those)
		$anthropicKey = 'sk-' . 'ant-' . str_repeat('x', 24);
		$googleKey = 'AI' . 'za' . str_repeat('x', 35);

		return [
			'anthropic key'   => ['Invalid key ' . $anthropicKey, substr($anthropicKey, 7)],
			'google key'      => ['403 for ' . $googleKey, substr($googleKey, 4)],
			'key in a url'    => ['GET https://api.example.com/v1/models?key=abc123secretvalue&x=1', 'abc123secretvalue'],
			'api_key in body' => ['{"api_key":"plainsecret999"}', 'plainsecret999'],
			'bearer header'   => ['Authorization: Bearer eyJhbGciOi.payload.sig', 'eyJhbGciOi.payload.sig'],
			'basic header'    => ['Authorization: Basic dXNlcjpwYXNz', 'dXNlcjpwYXNz'],
			'x-api-key'       => ['x-api-key: 0123456789abcdef', '0123456789abcdef'],
		];
	}
	
	public function test_ordinary_text_is_kept_and_shortened(): void
	{
		// Act
		$clean = SecretRedactor::redact(str_repeat('Model overloaded. ', 60));
		
		// Assert
		$this->assertStringStartsWith('Model overloaded.', $clean);
		$this->assertLessThanOrEqual(SecretRedactor::MAX_LENGTH + 3, mb_strlen($clean));
	}
}
