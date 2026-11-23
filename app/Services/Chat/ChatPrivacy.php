<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatPreference;
use App\Models\Chat\ChatThread;
use App\Models\Chat\UserBlock;
use App\Models\User;

/*
 * What a member lets others see in the chat (adniro), and the people they blocked.
 *
 * Both choices work both ways, so nobody watches without being seen:
 * - "Online" off: the member is not shown online or typing, and does not see it of others. They are simply
 *   not let into a conversation's presence channel (ChatAccess::canSeePresence).
 * - "Seen" off: nobody learns what the member has read, and the member does not learn what others have read.
 *   How far each person has read is still recorded (unread counts need it); it is only not told.
 */
class ChatPrivacy
{
	private const BLOCKED_LIST_MAX = 100;

	/** @return array{online: bool, seen: bool} */
	public static function of(int $userId): array
	{
		$preference = ChatPreference::query()->where('user_id', $userId)->first();

		return [
			'online' => (bool)($preference?->shows_online ?? true),
			'seen'   => (bool)($preference?->shows_seen ?? true),
		];
	}

	/** @return array{online: bool, seen: bool} */
	public static function save(int $userId, bool $showsOnline, bool $showsSeen): array
	{
		ChatPreference::query()->updateOrCreate(['user_id' => $userId], ['shows_online' => $showsOnline, 'shows_seen' => $showsSeen]);

		return self::of($userId);
	}

	public static function showsOnline(int $userId): bool
	{
		return !ChatPreference::query()->where('user_id', $userId)->where('shows_online', false)->exists();
	}

	/**
	 * Of these members, the ones who turned "seen" off: one query for a whole list of conversations.
	 *
	 * @param array<int, int> $userIds
	 * @return array<int, int>
	 */
	public static function hidingSeen(array $userIds): array
	{
		$userIds = array_values(array_unique(array_filter($userIds)));
		if ($userIds === []) {
			return [];
		}

		return ChatPreference::query()
			->whereIn('user_id', $userIds)
			->where('shows_seen', false)
			->pluck('user_id')
			->map(fn ($id) => (int)$id)
			->all();
	}

	public static function isSeenShared(int $userId, int $otherUserId): bool
	{
		return self::hidingSeen([$userId, $otherUserId]) === [];
	}

	/**
	 * How far the other person has read, as this member may know it: nothing when either of them turned
	 * "seen" off.
	 *
	 * @param array<int, int>|null $hidingSeen from hidingSeen(), when a list of conversations is presented
	 */
	public static function readUpTo(ChatThread $thread, int $viewerId, ?array $hidingSeen = null): int
	{
		$other = $thread->otherParticipant($viewerId);
		$otherId = (int)($other->user_id ?? 0);
		$hidingSeen ??= self::hidingSeen([$viewerId, $otherId]);

		return in_array($viewerId, $hidingSeen, true) || in_array($otherId, $hidingSeen, true)
			? 0
			: (int)($other->last_read_message_id ?? 0);
	}

	/**
	 * The people this member blocked, latest first, to unblock them from one place.
	 *
	 * @return array<int, array{id: int, name: string, photo: ?string}>
	 */
	public static function blockedPeople(int $userId): array
	{
		$blockedIds = UserBlock::query()->where('blocker_id', $userId)->orderByDesc('id')->limit(self::BLOCKED_LIST_MAX)->pluck('blocked_id')->map(fn ($id) => (int)$id);
		$users = User::query()->withoutGlobalScopes()->whereIn('id', $blockedIds)->get()->keyBy('id');

		return $blockedIds->map(fn (int $id) => ChatPresenter::person($users->get($id), $id))->values()->all();
	}
}
