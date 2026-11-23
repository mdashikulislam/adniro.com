<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatParticipant;
use App\Models\Chat\ChatThread;
use App\Models\Post;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/*
 * Chat messages (adniro): sending (once per tap), reading a conversation's history, and marking it read.
 * Callers have already checked that the member is in the conversation (ChatAccess).
 */
class ChatMessages
{
	public const PAGE_SIZE = 30;
	public const BODY_MAX = 2000;
	private const CATCH_UP_MAX = 100;

	/**
	 * Saves a text or a photo from one participant and pushes it to both. With the same client_uuid it
	 * returns the message already saved, so a double tap or a retry never sends twice.
	 * $about is the listing the message was sent from ("Chat with seller"): it is shown on the message.
	 * $replyTo is the earlier message of this conversation it answers: a short quote of it is shown.
	 */
	public static function send(
		ChatThread $thread,
		int $senderId,
		?string $body,
		?UploadedFile $photo = null,
		?string $clientUuid = null,
		?Post $about = null,
		?ChatMessage $replyTo = null,
	): ChatMessage {
		$existing = self::findByClientUuid((int)$thread->id, $clientUuid);
		if ($existing !== null) {
			return $existing;
		}

		$photoPath = $photo !== null ? ChatPhotos::store($photo, (int)$thread->id) : null;

		try {
			$message = DB::transaction(fn () => self::save($thread, $senderId, $body, $photoPath, $clientUuid, $about, $replyTo));
		} catch (UniqueConstraintViolationException) {
			ChatPhotos::delete($photoPath);

			return self::findByClientUuid((int)$thread->id, $clientUuid);
		}

		ChatUnread::forget(...$thread->participants->pluck('user_id')->map(fn ($id) => (int)$id)->all());
		ChatBroadcaster::messageSent($thread, $message);

		return $message;
	}

	/**
	 * A page of history, oldest first: the latest messages, or the ones before a given message.
	 *
	 * @return array{messages: array<int, array>, has_more: bool}
	 */
	public static function history(ChatThread $thread, ?int $beforeId = null): array
	{
		$rows = ChatMessage::query()
			->where('thread_id', $thread->id)
			->when($beforeId !== null, fn ($query) => $query->where('id', '<', $beforeId))
			->orderByDesc('id')
			->limit(self::PAGE_SIZE + 1)
			->get();

		$hasMore = $rows->count() > self::PAGE_SIZE;

		return [
			'messages' => ChatPresenter::messages($rows->take(self::PAGE_SIZE)->reverse()->values()),
			'has_more' => $hasMore,
		];
	}

	/** Messages newer than the one the browser already has (the fallback check when the live connection is down). */
	public static function after(ChatThread $thread, int $afterId): array
	{
		return ChatMessage::query()
			->where('thread_id', $thread->id)
			->where('id', '>', $afterId)
			->orderBy('id')
			->limit(self::CATCH_UP_MAX)
			->get()
			->pipe(fn ($rows) => ChatPresenter::messages($rows));
	}

	/**
	 * Marks the conversation read for this member up to a message. "Read" only moves forward.
	 * Returns the message it is now read up to.
	 */
	public static function markRead(ChatThread $thread, int $userId, int $messageId): int
	{
		$upTo = min($messageId, (int)$thread->last_message_id);

		// The row is locked while it is recounted, so a message arriving at the same moment is added after
		// the recount, never lost under it
		[$readUpTo, $changed] = DB::transaction(function () use ($thread, $userId, $upTo) {
			$participant = ChatParticipant::query()
				->where('thread_id', $thread->id)
				->where('user_id', $userId)
				->lockForUpdate()
				->firstOrFail();

			$alreadyReadUpTo = (int)$participant->last_read_message_id;
			if ($upTo <= $alreadyReadUpTo && (int)$participant->unread_count === 0) {
				return [$alreadyReadUpTo, false];
			}
			$readUpTo = max($upTo, $alreadyReadUpTo);

			$stillUnread = ChatMessage::query()
				->where('thread_id', $thread->id)
				->where('id', '>', $readUpTo)
				->where('user_id', '!=', $userId)
				->count();

			$participant->update(['last_read_message_id' => $readUpTo, 'unread_count' => $stillUnread, 'last_read' => now()]);

			return [$readUpTo, true];
		});

		if ($changed) {
			ChatUnread::forget($userId);
			ChatBroadcaster::messagesRead($thread, $userId, $readUpTo);
		}

		return $readUpTo;
	}

	private static function save(
		ChatThread $thread,
		int $senderId,
		?string $body,
		?string $photoPath,
		?string $clientUuid,
		?Post $about,
		?ChatMessage $replyTo,
	): ChatMessage {
		$message = ChatMessage::query()->create([
			'thread_id'   => $thread->id,
			'post_id'     => $about?->id,
			// Only a message of this same conversation can be quoted
			'reply_to_id' => $replyTo !== null && (int)$replyTo->thread_id === (int)$thread->id ? $replyTo->id : null,
			'user_id'     => $senderId,
			'type'        => $photoPath !== null ? ChatMessage::TYPE_IMAGE : ChatMessage::TYPE_TEXT,
			'body'        => $body,
			'file_path'   => $photoPath,
			'client_uuid' => $clientUuid,
		]);

		// A message sent from a listing makes that listing the one the conversation is about
		$thread->update(array_merge(
			['last_message_id' => $message->id, 'last_message_at' => $message->created_at],
			$about === null ? [] : ['post_id' => $about->id, 'subject' => $about->title],
		));

		// Everyone else has one more to read. The sender's own read state is not touched: sending is not
		// reading, and they may not have seen the other person's latest messages yet.
		ChatParticipant::query()
			->where('thread_id', $thread->id)
			->where('user_id', '!=', $senderId)
			->update(['unread_count' => DB::raw('unread_count + 1'), 'deleted_at' => null]);
		ChatParticipant::query()
			->where('thread_id', $thread->id)
			->where('user_id', $senderId)
			->update(['deleted_at' => null]);

		return $message;
	}

	private static function findByClientUuid(int $threadId, ?string $clientUuid): ?ChatMessage
	{
		if (empty($clientUuid)) {
			return null;
		}

		return ChatMessage::query()->where('thread_id', $threadId)->where('client_uuid', $clientUuid)->first();
	}
}
