<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\SecretRedactor;
use PHPUnit\Framework\TestCase;

class SecretRedactorTest extends TestCase
{
	/**
	 * @dataProvider secrets
	 */
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
		return [
			'anthropic key'   => ['Invalid key sk-ant-api03-AbCdEf123456789xyz', 'AbCdEf123456789xyz'],
			'google key'      => ['403 for AIzaSyA1b2C3d4E5f6G7h8I9j0KlMnOpQrStUvW', 'SyA1b2C3d4E5f6G7h8I9j0KlMnOpQrStUvW'],
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
