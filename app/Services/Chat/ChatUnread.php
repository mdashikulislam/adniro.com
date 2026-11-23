<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatParticipant;
use Illuminate\Support\Facades\Cache;

/*
 * A member's total of unread chat messages, for the badge on every page (adniro). The counts are stored per
 * conversation, so this is one small indexed sum; it is cached and dropped whenever a count changes.
 */
class ChatUnread
{
	private const CACHE_MINUTES = 10;
	private const BADGE_MAX = 99;

	public static function total(int $userId): int
	{
		return (int)Cache::remember(self::key($userId), now()->addMinutes(self::CACHE_MINUTES), function () use ($userId) {
			return (int)ChatParticipant::query()
				->where('user_id', $userId)
				->where('unread_count', '>', 0)
				->whereHas('thread')
				->sum('unread_count');
		});
	}

	/** The unread badge for the signed-in member: hidden at zero, "99+" above 99, kept live by chat-live.js. */
	public static function badgeHtml(string $class): string
	{
		$total = auth()->check() ? self::total((int)auth()->id()) : 0;
		$label = $total > self::BADGE_MAX ? self::BADGE_MAX . '+' : (string)$total;

		return '<span class="' . e($class) . '" data-adn-chat-badge' . ($total === 0 ? ' hidden' : '') . '>' . $label . '</span>';
	}

	public static function forget(int ...$userIds): void
	{
		foreach ($userIds as $userId) {
			Cache::forget(self::key($userId));
		}
	}

	private static function key(int $userId): string
	{
		return 'chat.unread.' . $userId;
	}
}
