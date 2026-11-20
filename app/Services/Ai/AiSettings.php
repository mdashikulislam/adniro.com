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
	 * The mode chosen in Settings > AI > Moderation: off, shadow or enforce.
	 */
	public function moderationModeSetting(): string
	{
		$mode = (string)($this->all()['moderation_mode'] ?? 'off');
		
		return in_array($mode, ['off', 'shadow', 'enforce'], true) ? $mode : 'off';
	}
	
	/**
	 * The mode in effect. off: no check. shadow: every listing is checked and logged, nothing is hidden.
	 * enforce: decisions are applied. Enforce falls back to shadow until every assigned
	 * moderation model has passed "php artisan ai:moderation-eval".
	 */
	public function moderationMode(): string
	{
		$mode = $this->moderationModeSetting();
		
		if ($mode === 'enforce' && !$this->moderationModelsPassedEval()) {
			return 'shadow';
		}
		
		return $mode;
	}
	
	public function moderationModelsPassedEval(): bool
	{
		$ids = $this->modelIdsFor('moderation');
		if (empty($ids)) {
			return false;
		}
		
		return \App\Models\AiModel::query()
			->whereIn('id', $ids)
			->whereNotNull('moderation_eval_passed_at')
			->count() === count(array_unique($ids));
	}
	
	/**
	 * When the AI cannot be used (over budget, or no usable model): approve the listing,
	 * or hold it for an admin.
	 */
	public function quickpostEnabled(): bool
	{
		return ($this->all()['quickpost_enabled'] ?? '0') == '1';
	}
	
	/**
	 * Review the draft and publish from one screen (photos shown with the form),
	 * instead of continuing to the photos step.
	 */
	public function quickpostOneScreen(): bool
	{
		return ($this->all()['quickpost_one_screen'] ?? '1') == '1';
	}
	
	public function quickpostGuests(): bool
	{
		return ($this->all()['quickpost_guests'] ?? '0') == '1';
	}
	
	/**
	 * The AI post limit for members or guests: [number per period, period].
	 * Period: day, week, month, or none (no limit).
	 *
	 * @return array{0: int, 1: string}
	 */
	public function quickpostLimit(string $who): array
	{
		$values = $this->all();
		$who = $who === 'guest' ? 'guest' : 'member';
		$default = $who === 'guest' ? 5 : 20;
		
		$limit = max(0, (int)($values['quickpost_' . $who . '_limit'] ?? $default));
		$period = (string)($values['quickpost_' . $who . '_period'] ?? 'day');
		
		return [$limit, in_array($period, ['day', 'week', 'month', 'none'], true) ? $period : 'day'];
	}
	
	public function moderationAlertAdmins(): bool
	{
		return ($this->all()['moderation_alert_admins'] ?? '1') == '1';
	}
	
	public function moderationWhenUnavailable(): string
	{
		return ($this->all()['moderation_when_unavailable'] ?? 'approve') === 'review' ? 'review' : 'approve';
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
