<?php

namespace App\Services\Ai;

use App\Models\Setting;
use App\Models\Setting\AiSetting;
use Throwable;

/*
 * Reads Admin > Settings > AI fresh from the database (adniro:ai).
 *
 * config('settings.ai.*') is loaded once at boot, so a long-running queue worker
 * would keep old values. This class avoids that at the cost of one small query.
 */

class AiSettings
{
	private const FEATURE_KEYS = [
		'moderation'      => ['moderation_model_id', 'moderation_fallback_model_id'],
		'quickpost_draft' => ['quickpost_model_id', 'quickpost_fallback_model_id'],
		'transcription'   => ['transcription_model_id', null],
	];
	
	public function all(): array
	{
		try {
			$setting = Setting::query()->where('name', 'ai')->first();
			$values = $setting?->field_values;
			
			return is_array($values) ? $values : AiSetting::getFieldValues([], null);
		} catch (Throwable $e) {
			return AiSetting::getFieldValues([], null);
		}
	}
	
	public function enabled(): bool
	{
		return ($this->all()['enabled'] ?? '0') == '1';
	}
	
	/**
	 * The AI model IDs assigned to a feature: primary first, then the fallback.
	 *
	 * @return int[]
	 */
	public function modelIdsFor(string $feature): array
	{
		$keys = self::FEATURE_KEYS[$feature] ?? [null, null];
		$values = $this->all();
		
		$ids = [];
		foreach ($keys as $key) {
			if (empty($key)) {
				continue;
			}
			$id = (int)($values[$key] ?? 0);
			if ($id > 0 && !in_array($id, $ids, true)) {
				$ids[] = $id;
			}
		}
		
		return $ids;
	}
	
	public function dailyBudgetUsd(): float
	{
		return max(0, (float)($this->all()['daily_budget_usd'] ?? 0));
	}
	
	public function dailyCallLimit(): int
	{
		return max(0, (int)($this->all()['daily_call_limit'] ?? 0));
	}
	
	/**
	 * off: no check. shadow: every listing is checked and logged, nothing is hidden.
	 */
	public function moderationMode(): string
	{
		$mode = (string)($this->all()['moderation_mode'] ?? 'off');
		
		return in_array($mode, ['off', 'shadow'], true) ? $mode : 'off';
	}
	
	/** Checks per user (or IP for guests) per day. 0 means no limit. */
	public function moderationUserDailyLimit(): int
	{
		return max(0, (int)($this->all()['moderation_user_daily_limit'] ?? 30));
	}
	
	/** Checks per listing per day. 0 means no limit. */
	public function moderationPostDailyLimit(): int
	{
		return max(0, (int)($this->all()['moderation_post_daily_limit'] ?? 10));
	}
}
