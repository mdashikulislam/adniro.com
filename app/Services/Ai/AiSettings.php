<?php

namespace App\Services\Ai;

use App\Models\Setting;
use App\Models\Setting\AiSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * Reads Admin > Settings > AI from the database (adniro:ai).
 *
 * config('settings.ai.*') is loaded once at boot, so a long-running queue worker would keep old
 * values. This class reads the row once per request or queued job instead: AiServiceProvider calls
 * flush() between jobs and when the settings are saved.
 *
 * When the row cannot be read, the last copy that could is used and readFailed() says so; callers
 * that would publish or release listings stop instead. Without any copy, the AI is off and
 * listings wait for an admin.
 */

class AiSettings
{
	public const LAST_GOOD_CACHE_KEY = 'ai:settings:last_good';
	
	/** Used only when the database cannot be read and no earlier copy exists. */
	private const SAFE_DEFAULTS = ['enabled' => '0', 'moderation_when_unavailable' => 'review'];
	
	private ?array $values = null;
	
	private bool $readFailed = false;
	
	private const FEATURE_KEYS = [
		'moderation'      => ['moderation_model_id', 'moderation_fallback_model_id'],
		'listing_assistant' => ['listing_assistant_model_id', 'listing_assistant_fallback_model_id'],
		'listing_writer'  => ['writing_help_model_id', null],
		'transcription'   => ['transcription_model_id', null],
	];
	
	public function all(): array
	{
		return $this->values ??= $this->load();
	}
	
	/** Read the settings again on the next call. */
	public function flush(): void
	{
		$this->values = null;
		$this->readFailed = false;
	}
	
	/** True when the settings row could not be read and an older copy (or safe defaults) is in use. */
	public function readFailed(): bool
	{
		$this->all();
		
		return $this->readFailed;
	}
	
	private function load(): array
	{
		try {
			$values = Setting::query()->where('name', 'ai')->first()?->field_values;
			$values = is_array($values) ? $values : AiSetting::getFieldValues([], null);
		} catch (Throwable $e) {
			$this->readFailed = true;
			Log::error('AI settings could not be read; using the last known copy.', ['error' => $e->getMessage()]);
			
			return $this->lastGood() ?? array_merge(AiSetting::getFieldValues([], null), self::SAFE_DEFAULTS);
		}
		
		$this->readFailed = false;
		$this->rememberLastGood($values);
		
		return $values;
	}
	
	private function lastGood(): ?array
	{
		try {
			$values = Cache::get(self::LAST_GOOD_CACHE_KEY);
			
			return is_array($values) ? $values : null;
		} catch (Throwable $e) {
			Log::error('The last known AI settings could not be read from the cache.', ['error' => $e->getMessage()]);
			
			return null;
		}
	}
	
	private function rememberLastGood(array $values): void
	{
		try {
			Cache::forever(self::LAST_GOOD_CACHE_KEY, $values);
		} catch (Throwable $e) {
			Log::warning('The AI settings could not be kept as the last known copy.', ['error' => $e->getMessage()]);
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
		
		// Writing help works without setup: its own model first (if any), then the listing
		// assistant's, so it keeps working when its own model is switched off or fails its test
		if ($feature === 'listing_writer') {
			return array_values(array_unique(array_merge($ids, $this->modelIdsFor('listing_assistant'))));
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
	public function listingAssistantEnabled(): bool
	{
		return ($this->all()['listing_assistant_enabled'] ?? '0') == '1';
	}
	
	public function listingAssistantGuests(): bool
	{
		return ($this->all()['listing_assistant_guests'] ?? '0') == '1';
	}
	
	/**
	 * The AI post limit for members or guests: [number per period, period].
	 * Period: day, week, month, or none (no limit).
	 *
	 * @return array{0: int, 1: string}
	 */
	public function listingAssistantLimit(string $who): array
	{
		$values = $this->all();
		$who = $who === 'guest' ? 'guest' : 'member';
		$default = $who === 'guest' ? 5 : 20;
		
		$limit = max(0, (int)($values['listing_assistant_' . $who . '_limit'] ?? $default));
		$period = (string)($values['listing_assistant_' . $who . '_period'] ?? 'day');
		
		return [$limit, in_array($period, ['day', 'week', 'month', 'none'], true) ? $period : 'day'];
	}
	
	/**
	 * AI writing help next to the listing form's Title and Description (members only).
	 */
	public function writingHelpEnabled(): bool
	{
		return ($this->all()['writing_help_enabled'] ?? '0') == '1';
	}
	
	/**
	 * Writing help per member: [number per period, period]. Period: day, week, month, or none.
	 *
	 * @return array{0: int, 1: string}
	 */
	public function writingHelpLimit(): array
	{
		$values = $this->all();
		$limit = max(0, (int)($values['writing_help_member_limit'] ?? 30));
		$period = (string)($values['writing_help_period'] ?? 'day');
		
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
