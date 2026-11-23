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
	public const CATCH_UP_MAX = 100;
	private const UNSENT_NEWS_HOURS = 24;

	/**
	 * Saves a text or a photo from one participant and pushes it to both. With the same client_uuid it
	 * returns the message already saved, so a double tap or a retry never sends twice.
	 * $about is the listing the message was sent from ("Chat with seller"): it is shown on the message.
	 * $replyTo is the earlier message of this conversation it answers: a short quote of it is shown.
	 * $special holds the extra columns of a message that is not plain text or a photo: an offer or its answer
	 * (ChatOffers), or an away message sent for a member (ChatTools).
	 */
	public static function send(
		ChatThread $thread,
		int $senderId,
		?string $body,
		?UploadedFile $photo = null,
		?string $clientUuid = null,
		?Post $about = null,
		?ChatMessage $replyTo = null,
		array $special = [],
	): ChatMessage {
		$existing = self::findByClientUuid((int)$thread->id, $clientUuid);
		if ($existing !== null) {
			return $existing;
		}

		$photoPath = $photo !== null ? ChatPhotos::store($photo, (int)$thread->id) : null;

		try {
			$message = DB::transaction(fn () => self::save($thread, $senderId, $body, $photoPath, $clientUuid, $about, $replyTo, $special));
		} catch (UniqueConstraintViolationException) {
			ChatPhotos::delete($photoPath);

			return self::findByClientUuid((int)$thread->id, $clientUuid);
		}

		ChatUnread::forget(...$thread->participants->pluck('user_id')->map(fn ($id) => (int)$id)->all());
		ChatBroadcaster::messageSent($thread, $message);
		ChatTools::answerWhileAway($thread, $message);

		return $message;
	}

	/**
	 * A page of history for one participant, oldest first: the latest messages, or the ones before a given
	 * message. What the member deleted from their own list (hideFor) is left out.
	 *
	 * @return array{messages: array<int, array>, has_more: bool}
	 */
	public static function history(ChatThread $thread, int $viewerId, ?int $beforeId = null): array
	{
		$clearedUpTo = self::clearedUpTo($thread, $viewerId);
		$rows = ChatMessage::query()
			->where('thread_id', $thread->id)
			->where('id', '>', $clearedUpTo)
			->when($beforeId !== null, fn ($query) => $query->where('id', '<', $beforeId))
			->orderByDesc('id')
			->limit(self::PAGE_SIZE + 1)
			->get();

		$hasMore = $rows->count() > self::PAGE_SIZE;

		return [
			'messages' => ChatPresenter::messages($rows->take(self::PAGE_SIZE)->reverse()->values(), $clearedUpTo),
			'has_more' => $hasMore,
		];
	}

	/**
	 * Messages newer than the one the browser already has (after a reconnect, and the fallback check while the
	 * live connection is down), oldest first. "has_more" tells the browser to ask again for the rest.
	 *
	 * @return array{messages: array<int, array>, has_more: bool, unsent: array<int, int>}
	 */
	public static function after(ChatThread $thread, int $viewerId, int $afterId): array
	{
		$clearedUpTo = self::clearedUpTo($thread, $viewerId);
		$rows = ChatMessage::query()
			->where('thread_id', $thread->id)
			->where('id', '>', max($afterId, $clearedUpTo))
			->orderBy('id')
			->limit(self::CATCH_UP_MAX + 1)
			->get();

		return [
			'messages' => ChatPresenter::messages($rows->take(self::CATCH_UP_MAX)->values(), $clearedUpTo),
			'has_more' => $rows->count() > self::CATCH_UP_MAX,
			'unsent'   => self::recentlyUnsent($thread),
		];
	}

	/**
	 * Messages taken back lately. Without a live connection this is how a browser learns that a message it
	 * already shows has become "Message deleted".
	 *
	 * @return array<int, int>
	 */
	private static function recentlyUnsent(ChatThread $thread): array
	{
		return ChatMessage::query()
			->where('thread_id', $thread->id)
			->where('unsent_at', '>=', now()->subHours(self::UNSENT_NEWS_HOURS))
			->orderByDesc('id')
			->limit(self::CATCH_UP_MAX)
			->pluck('id')
			->map(fn ($id) => (int)$id)
			->all();
	}

	/**
	 * The sender takes a message back. Both people then see "Message deleted" in its place, at once. The row
	 * and its text stay in the database: a reported conversation can still be read by the admins, so taking a
	 * message back never removes evidence. The caller has checked that it is the sender's and still in time.
	 *
	 * An offer is only taken back while it is open: if the other person answers it at the same moment, the
	 * answer stands and the message comes back as it is, not unsent.
	 */
	public static function unsend(ChatThread $thread, ChatMessage $message): ChatMessage
	{
		if ($message->isUnsent()) {
			return $message;
		}

		$isTakenBack = DB::transaction(function () use ($thread, $message) {
			$changed = ChatMessage::query()
				->whereKey($message->id)
				->whereNull('unsent_at')
				->when($message->isOffer(), fn ($query) => $query->where('offer_status', ChatMessage::OFFER_PENDING))
				->update(['unsent_at' => now()]);
			if ($changed === 0) {
				return false;
			}

			// Not read yet (and not deleted with the conversation): it no longer counts as unread
			ChatParticipant::query()
				->where('thread_id', $thread->id)
				->where('user_id', '!=', $message->user_id)
				->where('unread_count', '>', 0)
				->where(fn ($query) => $query->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $message->id))
				->where(fn ($query) => $query->whereNull('cleared_message_id')->orWhere('cleared_message_id', '<', $message->id))
				->decrement('unread_count');

			return true;
		});

		$message->refresh();
		if (!$isTakenBack) {
			return $message;
		}

		ChatUnread::forget(...$thread->participants->pluck('user_id')->map(fn ($id) => (int)$id)->all());
		ChatBroadcaster::messageUnsent($thread->load('participants'), $message);

		return $message;
	}

	/** Whether the sender may still take this message back. */
	public static function canUnsend(ChatMessage $message, int $userId): bool
	{
		$minutes = ChatSettings::unsendMinutes();

		return (int)$message->user_id === $userId
			&& !$message->isUnsent()
			// An offer can be withdrawn until it is answered; an answer stands
			&& !$message->isOfferAnswer()
			&& (!$message->isOffer() || $message->offer_status === ChatMessage::OFFER_PENDING)
			&& $minutes > 0
			&& $message->created_at !== null
			&& $message->created_at->gte(now()->subMinutes($minutes));
	}

	/**
	 * The member deletes the conversation from their own list. The other person keeps everything. For this
	 * member it leaves the list and the badge, and what was said so far is not shown to them again, even when
	 * a new message brings the conversation back.
	 */
	public static function hideFor(ChatThread $thread, int $userId): void
	{
		ChatParticipant::query()
			->where('thread_id', $thread->id)
			->where('user_id', $userId)
			->update(['deleted_at' => now(), 'cleared_message_id' => $thread->last_message_id, 'unread_count' => 0]);

		ChatUnread::forget($userId);
	}

	/** The last message this member deleted with the conversation: nothing up to it is shown to them. */
	public static function clearedUpTo(ChatThread $thread, int $userId): int
	{
		return (int)($thread->participantFor($userId)?->cleared_message_id ?? 0);
	}

	/**
	 * Marks the conversation read for this member up to a message. "Read" only moves forward.
	 * Returns the message it is now read up to, as the other person sees it.
	 *
	 * Across a block nobody learns what the other has read: the member's own unread count is cleared, but
	 * "seen" stays where it was, so a blocked person's messages never turn "seen" when the chat is opened.
	 * A member who turned "seen" off (ChatPrivacy) is recorded as usual and only not told about.
	 */
	public static function markRead(ChatThread $thread, int $userId, int $messageId): int
	{
		$upTo = min($messageId, (int)$thread->last_message_id);
		$otherId = (int)($thread->otherParticipant($userId)?->user_id ?? 0);
		$isSeenHidden = $otherId > 0 && ChatAccess::isBlockedBetween($userId, $otherId);

		// The row is locked while it is recounted, so a message arriving at the same moment is added after
		// the recount, never lost under it
		[$readUpTo, $changed] = DB::transaction(function () use ($thread, $userId, $upTo, $isSeenHidden) {
			$participant = ChatParticipant::query()
				->where('thread_id', $thread->id)
				->where('user_id', $userId)
				->lockForUpdate()
				->firstOrFail();

			$alreadyReadUpTo = (int)$participant->last_read_message_id;
			$hasUnread = (int)$participant->unread_count > 0;
			if ($isSeenHidden) {
				// No message can arrive across a block, so everything there is has now been looked at
				$participant->update(['unread_count' => 0]);

				return [$alreadyReadUpTo, $hasUnread];
			}
			if ($upTo <= $alreadyReadUpTo && !$hasUnread) {
				return [$alreadyReadUpTo, false];
			}
			$readUpTo = max($upTo, $alreadyReadUpTo);

			// What the member deleted with the conversation is never counted again
			$stillUnread = ChatMessage::query()
				->where('thread_id', $thread->id)
				->where('id', '>', max($readUpTo, (int)$participant->cleared_message_id))
				->where('user_id', '!=', $userId)
				->count();

			$participant->update(['last_read_message_id' => $readUpTo, 'unread_count' => $stillUnread, 'last_read' => now()]);

			return [$readUpTo, true];
		});

		if ($changed) {
			ChatUnread::forget($userId);
			// With "seen" turned off by either of them, only the reader's own tabs are told
			$onlyToReader = $isSeenHidden || ($otherId > 0 && !ChatPrivacy::isSeenShared($userId, $otherId));
			ChatBroadcaster::messagesRead($thread, $userId, $readUpTo, $onlyToReader);
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
		array $special,
	): ChatMessage {
		$message = ChatMessage::query()->create($special + [
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
		// A conversation that was deleted or archived comes back to both lists with a new message.
		ChatParticipant::query()
			->where('thread_id', $thread->id)
			->where('user_id', '!=', $senderId)
			->update(['unread_count' => DB::raw('unread_count + 1'), 'deleted_at' => null, 'archived_at' => null]);
		ChatParticipant::query()
			->where('thread_id', $thread->id)
			->where('user_id', $senderId)
			->update(['deleted_at' => null, 'archived_at' => null]);

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
