<?php

namespace App\Services\Ai\Moderation;

/*
 * The outcome of one moderation answer after the fixed rules were applied (adniro:ai).
 */

final class MappedVerdict
{
	public function __construct(
		public readonly string $status,              // A ModerationStatus value
		public readonly ?string $verdict = null,     // What the model said: allow, reject, needs_human
		public readonly array $categories = [],
		public readonly ?string $confidence = null,
		public readonly ?string $userReason = null,
		public readonly ?string $internalReason = null,
		public readonly ?string $rule = null,        // Which rule decided, for admins and tests
	)
	{
	}
	
	public function isApproved(): bool
	{
		return $this->status === ModerationStatus::APPROVED;
	}
}
