<?php

namespace App\Services\Ai;

/*
 * The outcome of one AI request (adniro:ai).
 *
 * The client never throws for provider problems. Callers check $status:
 * moderation sends anything but "ok" to human review, quick post falls back to the normal form.
 */

final class AiResult
{
	public const OK = 'ok';
	public const REFUSAL = 'refusal';       // The provider declined or its safety filter blocked the request
	public const TRUNCATED = 'truncated';   // The answer hit the token limit
	public const INVALID = 'invalid';       // The answer did not match the schema
	public const ERROR = 'error';           // Network, key, rate limit or provider error
	public const CAPPED = 'capped';         // Daily budget or call limit reached: no call was made
	public const DISABLED = 'disabled';     // AI is off, or no usable model is assigned: no call was made
	
	public function __construct(
		public readonly string $status,
		public readonly ?array $data = null,
		public readonly ?string $errorType = null,
		public readonly ?string $errorMessage = null,
		public readonly bool $retryable = false,
		public readonly int $inputTokens = 0,
		public readonly int $outputTokens = 0,
		public readonly int $cacheReadTokens = 0,
		public readonly int $cacheWriteTokens = 0,
		public readonly ?float $costUsd = null,
		public readonly int $latencyMs = 0,
		public readonly ?string $provider = null,
		public readonly ?string $model = null,
		public readonly ?int $aiModelId = null,
		public readonly bool $wasFallback = false,
		public readonly ?int $usageLogId = null,
	) {}
	
	public function ok(): bool
	{
		return $this->status === self::OK;
	}
	
	/**
	 * Did the request reach a provider? (Used for the daily call count.)
	 */
	/** Error types that stop a call before anything is sent. */
	public const LOCAL_ERRORS = ['missing_api_key'];
	
	public function reachedProvider(): bool
	{
		return !in_array($this->status, [self::CAPPED, self::DISABLED], true)
			&& !in_array($this->errorType, self::LOCAL_ERRORS, true);
	}
	
	public static function disabled(string $reason): self
	{
		return new self(status: self::DISABLED, errorType: $reason);
	}
	
	public static function capped(string $reason): self
	{
		return new self(status: self::CAPPED, errorType: $reason);
	}
}
