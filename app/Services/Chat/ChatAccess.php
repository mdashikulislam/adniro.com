<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatThread;
use App\Models\Chat\UserBlock;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/*
 * Who may see or write in a conversation (adniro): only its two participants, and nobody across a block.
 * Every chat page, data route, file and live channel goes through here.
 */
class ChatAccess
{
	/** The conversation, for one of its participants. Anyone else gets "not found", as if it did not exist. */
	public static function threadFor(int $threadId, int $userId): ChatThread
	{
		$thread = ChatThread::query()
			->whereKey($threadId)
			->forUser($userId)
			->with('participants')
			->first();

		if ($thread === null) {
			throw (new ModelNotFoundException())->setModel(ChatThread::class, [$threadId]);
		}

		return $thread;
	}

	public static function isParticipant(int $threadId, int $userId): bool
	{
		return ChatThread::query()->whereKey($threadId)->forUser($userId)->exists();
	}

	/** Online status and typing are shown to the two participants, and to neither once one has blocked the other. */
	public static function canSeePresence(int $threadId, int $userId): bool
	{
		$thread = ChatThread::query()->whereKey($threadId)->forUser($userId)->with('participants')->first();
		$otherId = (int)($thread?->otherParticipant($userId)?->user_id ?? 0);

		return $thread !== null && $otherId > 0 && !self::isBlockedBetween($userId, $otherId);
	}

	public static function hasBlocked(int $blockerId, int $blockedId): bool
	{
		return UserBlock::query()->where('blocker_id', $blockerId)->where('blocked_id', $blockedId)->exists();
	}

	/**
	 * Everyone this member has blocked, and everyone who has blocked them: two small queries, so a list of
	 * conversations is not checked one by one.
	 *
	 * @return array{blocked: array<int, int>, blockers: array<int, int>}
	 */
	public static function blocksOf(int $userId): array
	{
		return [
			'blocked'  => UserBlock::query()->where('blocker_id', $userId)->pluck('blocked_id')->map(fn ($id) => (int)$id)->all(),
			'blockers' => UserBlock::query()->where('blocked_id', $userId)->pluck('blocker_id')->map(fn ($id) => (int)$id)->all(),
		];
	}

	/** A block stops messages both ways. */
	public static function isBlockedBetween(int $userId, int $otherUserId): bool
	{
		return UserBlock::query()
			->where(fn ($query) => $query->where('blocker_id', $userId)->where('blocked_id', $otherUserId))
			->orWhere(fn ($query) => $query->where('blocker_id', $otherUserId)->where('blocked_id', $userId))
			->exists();
	}
}
