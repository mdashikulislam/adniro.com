<?php

namespace App\Services\QuickPost;

use Carbon\CarbonInterface;

/*
 * Where a person stands against the AI post limit (adniro:ai).
 */

final class LimitStatus
{
	public function __construct(
		public readonly ?int $limit,               // null: no limit
		public readonly int $used,
		public readonly string $period,            // day, week, month, none
		public readonly ?CarbonInterface $resetsAt, // null when there is no limit
	)
	{
	}
	
	public function unlimited(): bool
	{
		return is_null($this->limit);
	}
	
	public function remaining(): ?int
	{
		return $this->unlimited() ? null : max(0, $this->limit - $this->used);
	}
	
	public function reached(): bool
	{
		return !$this->unlimited() && $this->remaining() <= 0;
	}
	
	public function toArray(): array
	{
		return [
			'limit'     => $this->limit,
			'used'      => $this->used,
			'remaining' => $this->remaining(),
			'period'    => $this->period,
			'resets_at' => $this->resetsAt?->toIso8601String(),
		];
	}
}
