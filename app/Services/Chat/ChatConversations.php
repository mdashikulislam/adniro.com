<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatParticipant;
use App\Models\Chat\ChatThread;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/*
 * Chat conversations (adniro): one per listing and buyer, found or created, and a member's inbox.
 */
class ChatConversations
{
	public const INBOX_PAGE_SIZE = 20;
	public const FILTER_UNREAD = 'unread';

	/** Why this member cannot chat about this listing, or null when they can. */
	public static function cannotStartReason(Post $post, User $buyer): ?string
	{
		if (empty($post->user_id)) {
			return trans('chat.error_no_seller_account');
		}
		if ((int)$post->user_id === (int)$buyer->id) {
			return trans('chat.error_own_listing');
		}
		if (ChatAccess::isBlockedBetween((int)$buyer->id, (int)$post->user_id)) {
			return trans('chat.error_cannot_message');
		}

		return null;
	}

	public static function find(int $postId, int $buyerId): ?ChatThread
	{
		return ChatThread::query()->where('post_id', $postId)->where('buyer_id', $buyerId)->first();
	}

	/**
	 * The buyer's conversation about this listing, created on their first message. Two first messages at the
	 * same moment cannot make two conversations: the (listing, buyer) pair is unique in the database.
	 */
	public static function findOrCreate(Post $post, User $buyer): ChatThread
	{
		$existing = self::findIncludingDeleted((int)$post->id, (int)$buyer->id);
		if ($existing !== null) {
			return $existing;
		}

		try {
			return DB::transaction(function () use ($post, $buyer) {
				$thread = ChatThread::query()->create([
					'post_id'  => $post->id,
					'buyer_id' => $buyer->id,
					'subject'  => $post->title,
				]);
				foreach ([(int)$buyer->id, (int)$post->user_id] as $userId) {
					ChatParticipant::query()->create(['thread_id' => $thread->id, 'user_id' => $userId]);
				}

				return $thread->load('participants');
			});
		} catch (UniqueConstraintViolationException $e) {
			// Created by another request a moment ago
			return self::findIncludingDeleted((int)$post->id, (int)$buyer->id) ?? throw $e;
		}
	}

	/** The pair's conversation, brought back if it had been deleted: there is only ever one per listing and buyer. */
	private static function findIncludingDeleted(int $postId, int $buyerId): ?ChatThread
	{
		$thread = ChatThread::withTrashed()->where('post_id', $postId)->where('buyer_id', $buyerId)->first();
		if ($thread === null) {
			return null;
		}
		if ($thread->trashed()) {
			$thread->restore();
		}

		return $thread->load('participants');
	}

	/**
	 * A page of the member's inbox, newest conversation first. Conversations without a message are not listed.
	 *
	 * @return array{threads: array<int, array>, next_cursor: ?string}
	 */
	public static function inbox(int $userId, ?string $filter = null, ?string $cursor = null): array
	{
		$page = ChatThread::query()
			->whereNotNull('last_message_id')
			->whereHas('participants', function ($participants) use ($userId, $filter) {
				$participants->where('user_id', $userId);
				if ($filter === self::FILTER_UNREAD) {
					$participants->where('unread_count', '>', 0);
				}
			})
			->with(ChatPresenter::THREAD_RELATIONS)
			->orderByDesc('last_message_at')
			->orderByDesc('id')
			->cursorPaginate(self::INBOX_PAGE_SIZE, ['*'], 'cursor', $cursor);

		return [
			'threads'     => ChatPresenter::threads($page->items(), $userId),
			'next_cursor' => $page->nextCursor()?->encode(),
		];
	}
}
