<?php

namespace App\Services\Chat;

use App\Events\Chat\ChatEvent;
use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatThread;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * Pushes chat changes to the people in a conversation (adniro), each on their own private channel, so every
 * open tab of theirs updates: the conversation, the inbox and the unread badge.
 *
 * A message is saved before it is pushed. If the live server cannot be reached the push is dropped and
 * logged; the browser's fallback check picks the message up.
 */
class ChatBroadcaster
{
	public static function messageSent(ChatThread $thread, ChatMessage $message): void
	{
		$thread->load(ChatPresenter::THREAD_RELATIONS);

		foreach ($thread->participants as $participant) {
			$userId = (int)$participant->user_id;
			self::push(new ChatEvent(ChatEvent::MESSAGE, $userId, [
				// As this person may see it: no quote of something they deleted with the conversation
				'message'      => ChatPresenter::message($message, (int)$participant->cleared_message_id),
				'thread'       => ChatPresenter::thread($thread, $userId),
				'unread_total' => ChatUnread::total($userId),
			]));
		}
	}

	/** The message's place is taken by "Message deleted" for both people, and in the inbox line if it was the last. */
	public static function messageUnsent(ChatThread $thread, ChatMessage $message): void
	{
		$thread->load(ChatPresenter::THREAD_RELATIONS);

		foreach ($thread->participants as $participant) {
			$userId = (int)$participant->user_id;
			self::push(new ChatEvent(ChatEvent::UNSENT, $userId, [
				'message'      => ChatPresenter::message($message),
				'thread'       => ChatPresenter::thread($thread, $userId),
				'unread_total' => ChatUnread::total($userId),
			]));
		}
	}

	/** $onlyToReader: across a block, only the reader's own tabs are told (their unread badge), not the other person. */
	public static function messagesRead(ChatThread $thread, int $readerId, int $upToMessageId, bool $onlyToReader = false): void
	{
		foreach ($thread->participants as $participant) {
			$userId = (int)$participant->user_id;
			if ($onlyToReader && $userId !== $readerId) {
				continue;
			}
			self::push(new ChatEvent(ChatEvent::READ, $userId, [
				'thread_id'    => (int)$thread->id,
				'reader_id'    => $readerId,
				'message_id'   => $upToMessageId,
				'unread_total' => ChatUnread::total($userId),
			]));
		}
	}

	private static function push(ChatEvent $event): void
	{
		try {
			broadcast($event);
		} catch (Throwable $e) {
			Log::warning('Chat: live push failed', ['event' => $event->name, 'user' => $event->userId, 'error' => $e->getMessage()]);
		}
	}
}
