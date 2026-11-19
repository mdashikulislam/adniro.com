<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Cache;
use Throwable;

/*
 * Site-wide daily ceiling for all AI features (adniro:ai).
 *
 * Two counters per calendar day in the app's timezone: spend in micro-dollars and calls.
 * They live in the cache and are rebuilt from ai_usage_logs when missing.
 */

class AiBudget
{
	public const DAILY_BUDGET = 'daily_budget';
	public const DAILY_CALL_LIMIT = 'daily_call_limit';
	
	public function __construct(private readonly AiSettings $settings) {}
	
	/**
	 * @return string|null Null when a call is allowed, otherwise the reason it is not
	 */
	public function check(): ?string
	{
		$budget = $this->settings->dailyBudgetUsd();
		if ($budget > 0 && $this->spentTodayMicro() >= (int)round($budget * 1_000_000)) {
			return self::DAILY_BUDGET;
		}
		
		$limit = $this->settings->dailyCallLimit();
		if ($limit > 0 && $this->callsToday() >= $limit) {
			return self::DAILY_CALL_LIMIT;
		}
		
		return null;
	}
	
	public function record(?float $costUsd): void
	{
		try {
			$this->ensureCounters();
			Cache::increment($this->callsKey());
			if (!empty($costUsd)) {
				Cache::increment($this->spendKey(), (int)round($costUsd * 1_000_000));
			}
		} catch (Throwable $e) {
			// The usage log stays the source of truth; counters are rebuilt from it.
		}
	}
	
	public function spentTodayMicro(): int
	{
		$this->ensureCounters();
		
		return (int)Cache::get($this->spendKey(), 0);
	}
	
	public function callsToday(): int
	{
		$this->ensureCounters();
		
		return (int)Cache::get($this->callsKey(), 0);
	}
	
	private function ensureCounters(): void
	{
		$ttl = now()->endOfDay()->addHours(2);
		
		if (!Cache::has($this->callsKey()) || !Cache::has($this->spendKey())) {
			$today = AiUsageLog::query()
				->where('created_at', '>=', now()->startOfDay())
				->whereNotIn('status', [AiResult::CAPPED, AiResult::DISABLED]);
			
			Cache::add($this->callsKey(), (clone $today)->count(), $ttl);
			Cache::add($this->spendKey(), (int)round((float)(clone $today)->sum('cost_usd') * 1_000_000), $ttl);
		}
	}
	
	private function callsKey(): string
	{
		return 'ai:calls:' . now()->format('Y-m-d');
	}
	
	private function spendKey(): string
	{
		return 'ai:spend_micro:' . now()->format('Y-m-d');
	}
}
