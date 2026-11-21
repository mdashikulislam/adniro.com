<?php

namespace App\Services\ListingAssistant;

use App\Models\PostDraft;
use App\Models\User;
use App\Services\Ai\AiSettings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

/*
 * The admin-set AI post limit (adniro:ai).
 *
 * Members are counted by account, guests by IP address. One use is one successful draft;
 * failed calls and the first two re-drafts of a draft are not counted. Periods follow the
 * calendar in the site's timezone: a day resets at midnight, a week on Monday, a month on the 1st.
 * The count is read from post_drafts, so a changed limit or period applies at once.
 * created_at is stored in the app's timezone, so the period bounds are compared as they are,
 * without converting them to UTC.
 */

class AiPostLimit
{
	/** Period of a per-user limit when the site itself has no limit. */
	public const OVERRIDE_PERIOD = 'day';
	
	public function __construct(private readonly AiSettings $settings)
	{
	}
	
	public function status(?User $user, ?string $ip): LimitStatus
	{
		[$limit, $period] = $this->settings->listingAssistantLimit($user ? 'member' : 'guest');
		
		if ($user && !is_null($user->ai_post_limit)) {
			$limit = (int)$user->ai_post_limit;
			// A per-user limit on a site without one counts per day
			$period = ($period === 'none') ? self::OVERRIDE_PERIOD : $period;
		}
		
		if ($period === 'none') {
			return new LimitStatus(null, 0, 'none', null);
		}
		
		[$start, $end] = $this->periodBounds($period);
		
		$query = PostDraft::query()->where('counted', true)->where('created_at', '>=', $start);
		
		// An admin reset: only drafts made after it count
		if ($user && !empty($user->ai_post_reset_at)) {
			$resetAt = CarbonImmutable::parse($user->ai_post_reset_at);
			if ($resetAt->greaterThanOrEqualTo($start)) {
				$query->where('created_at', '>', $resetAt);
			}
		}
		if ($user) {
			$query->where('user_id', $user->getKey());
		} else {
			$query->whereNull('user_id')->where('ip', (string)$ip);
		}
		
		return new LimitStatus($limit, $query->count(), $period, $end);
	}
	
	/**
	 * All guests together: a forged X-Forwarded-For header gives a new per-IP limit, so guest drafts
	 * also stop at a site-wide number per day. 0 means no cap.
	 */
	public function guestCapReached(): bool
	{
		$cap = (int)config('ai_features.listing_assistant.guest_daily_cap', 200);
		if ($cap <= 0) {
			return false;
		}
		
		[$start] = $this->periodBounds('day');
		
		return PostDraft::query()->where('counted', true)->whereNull('user_id')->where('created_at', '>=', $start)->count() >= $cap;
	}
	
	/**
	 * A short lock per person, so two parallel requests cannot both pass the limit.
	 */
	public function lock(?User $user, ?string $ip): Lock
	{
		$key = $user ? 'user:' . $user->getKey() : 'ip:' . $ip;
		
		return Cache::lock('ai:listing_assistant_lock:' . $key, (int)config('ai_features.listing_assistant.timeout', 25) + 10);
	}
	
	/**
	 * Start and end of the current calendar period, in the site's timezone.
	 *
	 * @return array{0: CarbonImmutable, 1: CarbonImmutable}
	 */
	public function periodBounds(string $period): array
	{
		$now = CarbonImmutable::now((string)config('app.timezone', 'UTC'));
		
		return match ($period) {
			'week'  => [$now->startOfWeek(CarbonImmutable::MONDAY), $now->startOfWeek(CarbonImmutable::MONDAY)->addWeek()],
			'month' => [$now->startOfMonth(), $now->startOfMonth()->addMonth()],
			default => [$now->startOfDay(), $now->startOfDay()->addDay()],
		};
	}
}
