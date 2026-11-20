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
 * The last 20% is kept for moderation: running out of budget on drafts must not leave
 * new listings unchecked.
 */

class AiBudget
{
	public const DAILY_BUDGET = 'daily_budget';
	public const DAILY_CALL_LIMIT = 'daily_call_limit';
	
	/** Feature that may use the whole budget. The others stop earlier, so listings can still be checked. */
	public const RESERVED_FOR = 'moderation';
	
	/** Share of the daily budget and call limit open to features other than moderation. */
	public const OTHER_FEATURES_SHARE = 0.8;
	
	public function __construct(private readonly AiSettings $settings) {}
	
	/**
	 * @param string|null $feature The calling feature; null checks against the whole budget
	 * @return string|null Null when a call is allowed, otherwise the reason it is not
	 */
	public function check(?string $feature = null): ?string
	{
		$share = (is_null($feature) || $feature === self::RESERVED_FOR) ? 1.0 : self::OTHER_FEATURES_SHARE;
		
		$budget = $this->settings->dailyBudgetUsd();
		if ($budget > 0 && $this->spentTodayMicro() >= (int)round($budget * $share * 1_000_000)) {
			return self::DAILY_BUDGET;
		}
		
		$limit = $this->settings->dailyCallLimit();
		if ($limit > 0 && $this->callsToday() >= (int)floor($limit * $share)) {
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
				->whereNotIn('status', [AiResult::CAPPED, AiResult::DISABLED])
				->where(fn ($q) => $q->whereNull('error_type')->orWhereNotIn('error_type', AiResult::LOCAL_ERRORS));
			
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
