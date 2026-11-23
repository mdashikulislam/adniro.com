<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatThread;
use App\Models\Post;
use App\Models\User;
use App\Services\SellerService;
use Illuminate\Support\Str;

/*
 * The shape of chat data sent to the browser (adniro): one place, so pages, data routes and live events
 * all say the same thing. Only what the chat shows is included: never an email address or a phone number.
 */
class ChatPresenter
{
	private const SNIPPET_LENGTH = 90;
	private const TYPE_UNSENT = 'unsent';

	/** Relations a conversation needs before it is presented. */
	public const THREAD_RELATIONS = ['participants.user', 'post.picture', 'post.category', 'post.currency', 'lastMessage'];

	/** @param iterable<ChatThread> $threads */
	public static function threads(iterable $threads, int $viewerId): array
	{
		// Asked once for the whole list: who blocked whom, and who does not share "seen"
		$blocks = ChatAccess::blocksOf($viewerId);
		$people = [$viewerId];
		foreach ($threads as $thread) {
			$people[] = (int)($thread->otherParticipant($viewerId)?->user_id ?? 0);
		}
		$hidingSeen = ChatPrivacy::hidingSeen($people);

		$out = [];
		foreach ($threads as $thread) {
			$out[] = self::thread($thread, $viewerId, $blocks, $hidingSeen);
		}

		return $out;
	}

	/**
	 * @param array{blocked: array<int, int>, blockers: array<int, int>}|null $blocks from ChatAccess::blocksOf()
	 * @param array<int, int>|null $hidingSeen from ChatPrivacy::hidingSeen()
	 */
	public static function thread(ChatThread $thread, int $viewerId, ?array $blocks = null, ?array $hidingSeen = null): array
	{
		$blocks ??= ChatAccess::blocksOf($viewerId);
		$me = $thread->participantFor($viewerId);
		$other = $thread->otherParticipant($viewerId);
		$otherId = (int)($other->user_id ?? 0);
		$last = $thread->lastMessage;

		return [
			'id'            => (int)$thread->id,
			// Addresses are relative: this is also pushed to the other person, who may be on another host name
			'url'           => route('chat.show', ['thread' => $thread->id], false),
			'other'         => self::person($other?->user, $otherId),
			'post'          => self::post($thread->post) ?? self::deletedListing($thread->subject),
			'last'          => self::lastLine($last, $viewerId, (int)($me->cleared_message_id ?? 0)),
			'unread'        => (int)($me->unread_count ?? 0),
			// How far the other person has read: not told when either of them turned "seen" off
			'read_up_to'    => ChatPrivacy::readUpTo($thread, $viewerId, $hidingSeen),
			// The member's own list: neither is seen by the other person (ChatTidy)
			'archived'      => (bool)$me?->isArchived(),
			'muted'         => (bool)$me?->isMuted(),
			'blocked_by_me' => in_array($otherId, $blocks['blocked'], true),
			'can_send'      => ChatSettings::isEnabled() && $otherId > 0 && $other?->user !== null
				&& !in_array($otherId, $blocks['blocked'], true)
				&& !in_array($otherId, $blocks['blockers'], true),
		];
	}

	/**
	 * Several messages, with the listings they are about loaded in one query.
	 * $clearedUpTo: the viewer deleted the conversation up to this message (ChatMessages::clearedUpTo); a reply
	 * that quotes something older shows no quote to them.
	 *
	 * @param \Illuminate\Support\Collection<int, ChatMessage> $messages
	 */
	public static function messages($messages, int $clearedUpTo = 0): array
	{
		$messages->loadMissing(['post.picture', 'post.category', 'post.currency', 'replyTo']);

		return $messages->map(fn (ChatMessage $message) => self::message($message, $clearedUpTo))->values()->all();
	}

	public static function message(ChatMessage $message, int $clearedUpTo = 0): array
	{
		$isUnsent = $message->isUnsent();
		$isImage = !$isUnsent && $message->isImage() && !empty($message->file_path);
		$isOffer = !$isUnsent && ($message->isOffer() || $message->isOfferAnswer());

		return [
			'id'          => (int)$message->id,
			'thread_id'   => (int)$message->thread_id,
			'user_id'     => (int)$message->user_id,
			'type'        => match (true) {
				$isImage => ChatMessage::TYPE_IMAGE,
				$isOffer => (string)$message->type,
				default  => ChatMessage::TYPE_TEXT,
			},
			// A message taken back by its sender leaves no text, photo or quote behind: only its place
			'unsent'      => $isUnsent,
			'body'        => $isUnsent ? '' : (string)$message->body,
			'image'       => !$isImage ? null : [
				'url'   => route('chat.file', ['message' => $message->id], false),
				'thumb' => route('chat.file', ['message' => $message->id, 'size' => 'thumb'], false),
			],
			// An offer's price and whether it is still open; null for every other message
			'offer'       => $isOffer ? ChatOffers::present($message) : null,
			// Sent by the site for a member who is away (their away message)
			'auto'        => !$isUnsent && (bool)$message->is_auto,
			'post'        => empty($message->post_id) ? null : self::post($message->post),
			// An answer to an offer names the offer itself ("offer"), it does not quote it
			'reply_to'    => $isUnsent || $message->isOfferAnswer() || (int)$message->reply_to_id <= $clearedUpTo
				? null
				: self::quote($message->replyTo),
			'client_uuid' => $message->client_uuid,
			'at'          => $message->created_at?->toIso8601String(),
			'caution'     => !$isUnsent && ChatSafety::needsCaution($message->body),
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
			'type'    => self::lineType($message),
			'text'    => self::lineText($message),
		];
	}

	/** The inbox line: the conversation's last message, unless this member deleted the conversation after it. */
	private static function lastLine(?ChatMessage $last, int $viewerId, int $clearedUpTo): ?array
	{
		if ($last === null || (int)$last->id <= $clearedUpTo) {
			return null;
		}

		return [
			'id'   => (int)$last->id,
			'mine' => (int)$last->user_id === $viewerId,
			'type' => self::lineType($last),
			'text' => self::lineText($last),
			'at'   => $last->created_at?->toIso8601String(),
		];
	}

	/**
	 * What a one-line summary of the message is made from: "text", "image", "offer", "offer_accepted",
	 * "offer_declined", or "unsent" for a message its sender took back. The browser writes the words.
	 */
	private static function lineType(ChatMessage $message): string
	{
		return match (true) {
			$message->isUnsent()      => self::TYPE_UNSENT,
			$message->isImage()       => ChatMessage::TYPE_IMAGE,
			$message->isOffer()       => ChatMessage::TYPE_OFFER,
			$message->isOfferAnswer() => ChatMessage::TYPE_OFFER . '_' . $message->offer_status,
			default                   => ChatMessage::TYPE_TEXT,
		};
	}

	/** The start of the text, or the amount of an offer. Nothing for a message that was taken back. */
	private static function lineText(ChatMessage $message): string
	{
		if ($message->isUnsent()) {
			return '';
		}
		if ($message->isOffer() || $message->isOfferAnswer()) {
			return ChatOffers::amountOf($message);
		}

		return Str::limit(self::oneLine((string)$message->body), self::SNIPPET_LENGTH);
	}

	public static function person(?User $user, int $fallbackId = 0): array
	{
		if ($user === null) {
			return ['id' => $fallbackId, 'name' => trans('chat.deleted_member'), 'photo' => null, 'verified' => false];
		}

		return [
			'id'       => (int)$user->id,
			'name'     => (string)$user->name,
			'photo'    => empty($user->photo_path) ? null : (string)$user->photo_url,
			// The site's own "verified" rule, as on the listing page and the profile (a confirmed email address)
			'verified' => SellerService::isVerifiedSeller($user),
		];
	}

	/** The listing card. A listing that is gone, archived or not public is shown as no longer available. */
	public static function post(?Post $post): ?array
	{
		if ($post === null) {
			return null;
		}

		$isAvailable = self::isAvailable($post);
		$canOffer = ChatOffers::canBeMadeOn($post);

		return [
			'id'        => (int)$post->id,
			'title'     => (string)$post->title,
			'price'     => trim(html_entity_decode(strip_tags((string)$post->price_formatted))),
			'photo'     => data_get($post, 'picture.file_url_small'),
			'url'       => $isAvailable ? urlGen()->post($post) : null,
			'available' => $isAvailable,
			// Whose listing it is: its owner gets the seller's tools, the other person can make an offer
			'owner_id'  => (int)$post->user_id,
			'can_offer' => $canOffer,
			'currency'  => $canOffer ? ChatOffers::symbolOf($post->currency_code) : '',
		];
	}

	/** Public: not archived, not deleted, and approved. */
	public static function isAvailable(Post $post): bool
	{
		return empty($post->archived_at) && empty($post->deleted_at) && isVerifiedPost($post);
	}

	/** The card of a listing that has been deleted: the conversation still remembers its title. */
	private static function deletedListing(?string $title): ?array
	{
		if (empty($title)) {
			return null;
		}

		return [
			'id' => 0, 'title' => $title, 'price' => '', 'photo' => null, 'url' => null, 'available' => false,
			'owner_id' => 0, 'can_offer' => false, 'currency' => '',
		];
	}

	private static function oneLine(string $text): string
	{
		return trim((string)preg_replace('/\s+/u', ' ', $text));
	}
}
