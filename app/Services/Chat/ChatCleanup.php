<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatParticipant;
use App\Models\Chat\ChatThread;
use Illuminate\Support\Facades\DB;

/*
 * What happens to chat conversations when a listing or a member is deleted (adniro).
 *
 * A conversation belongs to two people, not to a listing: deleting a listing never deletes what they said.
 * Deleting a member removes that member's messages (the site's rule for all of a member's content); the other
 * person keeps their side, counted again so no unread badge is left behind.
 */
class ChatCleanup
{
	/** The listing is gone: its conversations and messages stay and only stop pointing at it. */
	public static function listingDeleted(int $postId): void
	{
		// The conversation keeps the listing's title ("subject"), shown as no longer available
		DB::table((new ChatThread())->getTable())->where('post_id', $postId)->update(['post_id' => null]);
		DB::table((new ChatMessage())->getTable())->where('post_id', $postId)->update(['post_id' => null]);
	}

	/**
	 * The conversations a member is in. Asked for before the account is deleted, while the rows still say so.
	 *
	 * @return array<int, int>
	 */
	public static function threadIdsOf(int $userId): array
	{
		return DB::table((new ChatParticipant())->getTable())
			->where('user_id', $userId)
			->pluck('thread_id')
			->map(fn ($id) => (int)$id)
			->unique()
			->values()
			->all();
	}

	/**
	 * The member's account and messages have been deleted: what is left of their conversations is counted
	 * again for the other person (last message, unread count, badge).
	 *
	 * @param array<int, int> $threadIds from threadIdsOf()
	 */
	public static function memberDeleted(int $userId, array $threadIds): void
	{
		// Rows the member had hidden are not covered by the site's own clean-up
		DB::table((new ChatParticipant())->getTable())->where('user_id', $userId)->delete();
		ChatUnread::forget($userId);

		ChatThread::withTrashed()
			->whereKey($threadIds)
			->with('participants')
			->get()
			->each(fn (ChatThread $thread) => self::recount($thread));
	}

	private static function recount(ChatThread $thread): void
	{
		$lastMessage = ChatMessage::query()->where('thread_id', $thread->id)->orderByDesc('id')->first();

		// Without a message left, the conversation drops out of the inbox
		$thread->update(['last_message_id' => $lastMessage?->id, 'last_message_at' => $lastMessage?->created_at]);

		foreach ($thread->participants as $participant) {
			$unread = ChatMessage::query()
				->where('thread_id', $thread->id)
				->where('user_id', '!=', $participant->user_id)
				->where('id', '>', (int)$participant->last_read_message_id)
				->count();

			$participant->update(['unread_count' => $unread]);
			ChatUnread::forget((int)$participant->user_id);
		}
	}
}
