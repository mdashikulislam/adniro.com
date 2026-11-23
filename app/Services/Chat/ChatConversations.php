<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatParticipant;
use App\Models\Chat\ChatThread;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/*
 * Chat conversations (adniro): one per two people, found or created, and a member's inbox.
 */
class ChatConversations
{
	public const INBOX_PAGE_SIZE = 20;
	public const FILTER_UNREAD = 'unread';

	/** Why this member cannot chat about this listing, or null when they can. */
	public static function cannotStartReason(Post $post, User $buyer): ?string
	{
		if (!ChatSettings::isEnabled()) {
			return trans('chat.error_paused');
		}
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

	/** The conversation between two members, if they have one. */
	public static function between(int $userId, int $otherUserId): ?ChatThread
	{
		[$low, $high] = self::pair($userId, $otherUserId);

		return ChatThread::query()->where('user_low_id', $low)->where('user_high_id', $high)->first();
	}

	/**
	 * The conversation between the buyer and the listing's seller, created on their first message. There is one
	 * per two people, whatever the listing: the pair is unique in the database, so two first messages at the
	 * same moment cannot make two.
	 */
	public static function findOrCreate(Post $post, User $buyer): ChatThread
	{
		[$low, $high] = self::pair((int)$buyer->id, (int)$post->user_id);

		$existing = self::findIncludingDeleted($low, $high);
		if ($existing !== null) {
			return $existing;
		}

		try {
			return DB::transaction(function () use ($post, $buyer, $low, $high) {
				$thread = ChatThread::query()->create([
					'post_id'      => $post->id,
					'buyer_id'     => $buyer->id,
					'user_low_id'  => $low,
					'user_high_id' => $high,
					'subject'      => $post->title,
				]);
				foreach ([$low, $high] as $userId) {
					ChatParticipant::query()->create(['thread_id' => $thread->id, 'user_id' => $userId]);
				}

				return $thread->load('participants');
			});
		} catch (UniqueConstraintViolationException $e) {
			// Created by another request a moment ago
			return self::findIncludingDeleted($low, $high) ?? throw $e;
		}
	}

	/** The pair's conversation, brought back if it had been deleted: there is only ever one per two people. */
	private static function findIncludingDeleted(int $low, int $high): ?ChatThread
	{
		$thread = ChatThread::withTrashed()->where('user_low_id', $low)->where('user_high_id', $high)->first();
		if ($thread === null) {
			return null;
		}
		if ($thread->trashed()) {
			$thread->restore();
		}

		return $thread->load('participants');
	}

	/** @return array{0: int, 1: int} the two member ids, lower first */
	private static function pair(int $userId, int $otherUserId): array
	{
		return [min($userId, $otherUserId), max($userId, $otherUserId)];
	}

	/**
	 * A page of the member's inbox, newest conversation first. Conversations without a message, and the ones
	 * the member removed from their list, are not listed.
	 *
	 * @return array{threads: array<int, array>, next_cursor: ?string}
	 */
	public static function inbox(int $userId, ?string $filter = null, ?string $cursor = null): array
	{
		$page = ChatThread::query()
			->whereNotNull('last_message_id')
			->whereHas('participants', function ($participants) use ($userId, $filter) {
				$participants->where('user_id', $userId)->visible();
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
