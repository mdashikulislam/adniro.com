<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatThread;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

/*
 * The shape of chat data sent to the browser (adniro): one place, so pages, data routes and live events
 * all say the same thing. Only what the chat shows is included: never an email address or a phone number.
 */
class ChatPresenter
{
	private const SNIPPET_LENGTH = 90;

	/** Relations a conversation needs before it is presented. */
	public const THREAD_RELATIONS = ['participants.user', 'post.picture', 'lastMessage'];

	/** @param iterable<ChatThread> $threads */
	public static function threads(iterable $threads, int $viewerId): array
	{
		$blocks = ChatAccess::blocksOf($viewerId);
		$out = [];
		foreach ($threads as $thread) {
			$out[] = self::thread($thread, $viewerId, $blocks);
		}

		return $out;
	}

	/** @param array{blocked: array<int, int>, blockers: array<int, int>}|null $blocks from ChatAccess::blocksOf() */
	public static function thread(ChatThread $thread, int $viewerId, ?array $blocks = null): array
	{
		$blocks ??= ChatAccess::blocksOf($viewerId);
		$me = $thread->participantFor($viewerId);
		$other = $thread->otherParticipant($viewerId);
		$otherId = (int)($other->user_id ?? 0);
		$last = $thread->lastMessage;

		return [
			'id'            => (int)$thread->id,
			'url'           => route('chat.show', ['thread' => $thread->id]),
			'other'         => self::person($other?->user, $otherId),
			'post'          => self::post($thread->post),
			'last'          => $last === null ? null : [
				'id'   => (int)$last->id,
				'mine' => (int)$last->user_id === $viewerId,
				'type' => (string)$last->type,
				'text' => Str::limit(self::oneLine((string)$last->body), self::SNIPPET_LENGTH),
				'at'   => $last->created_at?->toIso8601String(),
			],
			'unread'        => (int)($me->unread_count ?? 0),
			'read_up_to'    => (int)($other->last_read_message_id ?? 0),
			'blocked_by_me' => in_array($otherId, $blocks['blocked'], true),
			'can_send'      => $otherId > 0 && $other?->user !== null
				&& !in_array($otherId, $blocks['blocked'], true)
				&& !in_array($otherId, $blocks['blockers'], true),
		];
	}

	/**
	 * Several messages, with the listings they are about loaded in one query.
	 *
	 * @param \Illuminate\Support\Collection<int, ChatMessage> $messages
	 */
	public static function messages($messages): array
	{
		$messages->loadMissing(['post.picture', 'replyTo']);

		return $messages->map(fn (ChatMessage $message) => self::message($message))->values()->all();
	}

	public static function message(ChatMessage $message): array
	{
		$isImage = $message->isImage() && !empty($message->file_path);

		return [
			'id'          => (int)$message->id,
			'thread_id'   => (int)$message->thread_id,
			'user_id'     => (int)$message->user_id,
			'type'        => $isImage ? ChatMessage::TYPE_IMAGE : ChatMessage::TYPE_TEXT,
			'body'        => (string)$message->body,
			'image'       => !$isImage ? null : [
				'url'   => route('chat.file', ['message' => $message->id]),
				'thumb' => route('chat.file', ['message' => $message->id, 'size' => 'thumb']),
			],
			'post'        => empty($message->post_id) ? null : self::post($message->post),
			'reply_to'    => empty($message->reply_to_id) ? null : self::quote($message->replyTo),
			'client_uuid' => $message->client_uuid,
			'at'          => $message->created_at?->toIso8601String(),
			'caution'     => ChatSafety::needsCaution($message->body),
		];
	}

	/** The short quote of a message shown inside a reply to it. */
	public static function quote(?ChatMessage $message): ?array
	{
		if ($message === null) {
			return null;
		}

		return [
			'id'      => (int)$message->id,
			'user_id' => (int)$message->user_id,
			'type'    => $message->isImage() ? ChatMessage::TYPE_IMAGE : ChatMessage::TYPE_TEXT,
			'text'    => Str::limit(self::oneLine((string)$message->body), self::SNIPPET_LENGTH),
		];
	}

	public static function person(?User $user, int $fallbackId = 0): array
	{
		if ($user === null) {
			return ['id' => $fallbackId, 'name' => trans('chat.deleted_member'), 'photo' => null];
		}

		return [
			'id'    => (int)$user->id,
			'name'  => (string)$user->name,
			'photo' => empty($user->photo_path) ? null : (string)$user->photo_url,
		];
	}

	/** The listing card. A listing that is gone, archived or not public is shown as no longer available. */
	public static function post(?Post $post): ?array
	{
		if ($post === null) {
			return null;
		}

		$isAvailable = empty($post->archived_at) && empty($post->deleted_at) && isVerifiedPost($post);

		return [
			'id'        => (int)$post->id,
			'title'     => (string)$post->title,
			'price'     => trim(html_entity_decode(strip_tags((string)$post->price_formatted))),
			'photo'     => data_get($post, 'picture.file_url_small'),
			'url'       => $isAvailable ? urlGen()->post($post) : null,
			'available' => $isAvailable,
		];
	}

	private static function oneLine(string $text): string
	{
		return trim((string)preg_replace('/\s+/u', ' ', $text));
	}
}
