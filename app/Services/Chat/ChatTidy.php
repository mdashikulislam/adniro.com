<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatParticipant;
use App\Models\Chat\ChatThread;

/*
 * What a member does with a conversation in their own list (adniro): archive it, mute it, mark it unread.
 * None of it is seen by the other person, and none of it changes what they see.
 *
 * - Archived: out of the list, under "Archived". The next message brings it back (ChatMessages).
 * - Muted: no sound, no notification, no pop-up and no email for it; its unread count still shows.
 * - Marked unread: counted as one unread message until the member opens it again. The other person's "seen"
 *   stays as it was.
 */
class ChatTidy
{
	/** Muting has no end date: far enough ahead to mean "until I turn it back on" (the column is a timestamp). */
	private const MUTED_UNTIL = '2037-12-31 00:00:00';

	public static function archive(ChatThread $thread, int $userId, bool $isArchived): void
	{
		self::mine($thread, $userId)->update(['archived_at' => $isArchived ? now() : null]);
	}

	public static function mute(ChatThread $thread, int $userId, bool $isMuted): void
	{
		self::mine($thread, $userId)->update(['muted_until' => $isMuted ? self::MUTED_UNTIL : null]);
	}

	/**
	 * Only a conversation that is read and still shows something to this member. The unread-messages email
	 * is not sent for it: the member knows about these messages.
	 */
	public static function markUnread(ChatThread $thread, int $userId): void
	{
		$marked = self::mine($thread, $userId)
			->where('unread_count', 0)
			->where(function ($query) use ($thread) {
				$query->whereNull('cleared_message_id')->orWhere('cleared_message_id', '<', (int)$thread->last_message_id);
			})
			->update(['unread_count' => 1, 'digest_sent_at' => now()]);

		if ($marked > 0) {
			ChatUnread::forget($userId);
		}
	}

	private static function mine(ChatThread $thread, int $userId)
	{
		return ChatParticipant::query()->where('thread_id', $thread->id)->where('user_id', $userId);
	}
}
