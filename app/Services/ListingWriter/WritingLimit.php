<?php

namespace App\Services\ListingWriter;

use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiSettings;
use App\Services\ListingAssistant\AiPostLimit;
use App\Services\ListingAssistant\LimitStatus;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

/*
 * The writing-help allowance per member (adniro:ai), separate from the AI post limit and cheaper.
 *
 * One use is one successful AI answer, read from ai_usage_logs, so a changed limit applies at
 * once. Periods follow the calendar like the AI post limit. A member whose AI post limit the
 * admin set to 0 gets no writing help either.
 */

class WritingLimit
{
	public function __construct(
		private readonly AiSettings $settings,
		private readonly AiPostLimit $periods,
	)
	{
	}

	public function status(User $user): LimitStatus
	{
		[$limit, $period] = $this->settings->writingHelpLimit();

		if (!is_null($user->ai_post_limit) && (int)$user->ai_post_limit === 0) {
			[, $end] = $this->periods->periodBounds(AiPostLimit::OVERRIDE_PERIOD);

			return new LimitStatus(0, 0, AiPostLimit::OVERRIDE_PERIOD, $end);
		}
		if ($period === 'none') {
			return new LimitStatus(null, 0, 'none', null);
		}

		[$start, $end] = $this->periods->periodBounds($period);
		$used = AiUsageLog::query()
			->where('feature', WritingHelpService::FEATURE)
			->where('user_id', $user->getKey())
			->where('status', AiResult::OK)
			->where('created_at', '>=', $start)
			->count();

		return new LimitStatus($limit, $used, $period, $end);
	}

	/**
	 * A short lock per member, so two parallel requests cannot both pass the limit. It outlives
	 * the AI client's worst case: the primary model times out, then the fallback does.
	 */
	public function lock(User $user): Lock
	{
		return Cache::lock('ai:listing_writer_lock:user:' . $user->getKey(), (int)config('ai_features.listing_writer.timeout', 20) * 2 + 10);
	}
}
