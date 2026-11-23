<?php

namespace App\Services\Chat;

use App\Helpers\Common\Num;
use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatThread;
use App\Models\Currency;
use App\Models\Post;
use Illuminate\Support\Arr;
use Throwable;

/*
 * Offers in a chat (adniro): one person proposes a price for a listing, the other accepts or declines.
 *
 * An offer is a message ("offer"), and so is its answer ("offer_answer"): both reach the other person like any
 * message, with the unread badge, the alerts and the email. Nothing is paid or reserved on the site: an
 * accepted offer is an agreement between two people, who still meet and pay each other.
 * A newer offer on the same listing replaces the ones still open in that conversation.
 */
class ChatOffers
{
	public const AMOUNT_MAX = 999999999;

	/** Categories whose "price" is not a price to bargain over (a salary, or nothing for sale). */
	private const CATEGORY_TYPES_WITHOUT_OFFERS = ['job-offer', 'job-search', 'not-salable'];

	/** @var array<string, array|null> the currencies already looked up, by code */
	private static array $currencies = [];

	public static function isEnabled(): bool
	{
		return ChatSettings::isEnabled() && ChatSettings::offersEnabled();
	}

	/** Whether a price can be proposed for this listing: public, for sale, and not given away for free. */
	public static function canBeMadeOn(?Post $post): bool
	{
		if ($post === null || !self::isEnabled() || !ChatPresenter::isAvailable($post) || empty($post->currency_code)) {
			return false;
		}
		$isFree = is_numeric($post->price) && (float)$post->price <= 0;

		return !$isFree && !in_array($post->category?->type, self::CATEGORY_TYPES_WITHOUT_OFFERS, true);
	}

	/**
	 * Sends an offer about a listing. The listing becomes what the conversation is about, as with any message
	 * sent from a listing. With the same client_uuid it returns the offer already sent.
	 */
	public static function make(ChatThread $thread, int $senderId, Post $post, string $amount, ?string $clientUuid): ChatMessage
	{
		$offer = ChatMessages::send($thread, $senderId, null, null, $clientUuid, $post, null, [
			'type'           => ChatMessage::TYPE_OFFER,
			'offer_amount'   => $amount,
			'offer_currency' => $post->currency_code,
			'offer_status'   => ChatMessage::OFFER_PENDING,
		]);

		// One open offer per listing in a conversation: the browser applies the same rule when this one arrives
		ChatMessage::query()
			->where('thread_id', $thread->id)
			->where('type', ChatMessage::TYPE_OFFER)
			->where('post_id', $post->id)
			->where('offer_status', ChatMessage::OFFER_PENDING)
			->where('id', '<', $offer->id)
			->update(['offer_status' => ChatMessage::OFFER_REPLACED]);

		return $offer;
	}

	/**
	 * The person who received an offer accepts or declines it. Returns the answer, a message of its own, or
	 * null when the offer was no longer open (answered, replaced or withdrawn a moment before).
	 */
	public static function answer(ChatThread $thread, ChatMessage $offer, int $userId, bool $isAccepted): ?ChatMessage
	{
		$status = $isAccepted ? ChatMessage::OFFER_ACCEPTED : ChatMessage::OFFER_DECLINED;

		// Only an open offer changes, and only once, even when two answers arrive together
		$changed = ChatMessage::query()
			->whereKey($offer->id)
			->where('offer_status', ChatMessage::OFFER_PENDING)
			->whereNull('unsent_at')
			->update(['offer_status' => $status]);
		if ($changed === 0) {
			return null;
		}

		try {
			return ChatMessages::send($thread, $userId, null, null, null, null, $offer, [
				'type'           => ChatMessage::TYPE_OFFER_ANSWER,
				'offer_amount'   => $offer->offer_amount,
				'offer_currency' => $offer->offer_currency,
				'offer_status'   => $status,
			]);
		} catch (Throwable $e) {
			// The answer could not be saved: the offer is open again, so it can be answered once more
			ChatMessage::query()->whereKey($offer->id)->where('offer_status', $status)->update(['offer_status' => ChatMessage::OFFER_PENDING]);

			throw $e;
		}
	}

	/**
	 * Whether this listing has come up in the conversation: its owner can name a price for it there, and not
	 * for a listing the other person never asked about.
	 */
	public static function isTalkedAbout(ChatThread $thread, Post $post): bool
	{
		return (int)$thread->post_id === (int)$post->id
			|| ChatMessage::query()->where('thread_id', $thread->id)->where('post_id', $post->id)->exists();
	}

	/** The offer part of a message, as the chat shows it. */
	public static function present(ChatMessage $message): array
	{
		return [
			'amount'   => self::amountOf($message),
			'status'   => (string)$message->offer_status,
			// The listing the offer is about, and for an answer the offer it answers
			'post_id'  => (int)$message->post_id,
			'offer_id' => $message->isOfferAnswer() ? (int)$message->reply_to_id : null,
		];
	}

	/** "1 200 €": the amount in its own currency, the same for everyone who reads it. */
	public static function amountOf(ChatMessage $message): string
	{
		$code = (string)$message->offer_currency;
		$currency = self::currency($code);
		if ($currency === null) {
			return trim(Num::format($message->offer_amount) . ' ' . $code);
		}

		// Without an exchange rate: the amount shown is always the amount that was typed
		return trim(html_entity_decode(strip_tags(Num::money($message->offer_amount, Arr::except($currency, ['rate'])))));
	}

	/** The symbol members type an amount in, for a listing's currency. */
	public static function symbolOf(?string $code): string
	{
		return (string)(self::currency((string)$code)['symbol'] ?? $code);
	}

	private static function currency(string $code): ?array
	{
		if ($code === '') {
			return null;
		}
		if (!array_key_exists($code, self::$currencies)) {
			self::$currencies[$code] = Currency::query()->where('code', $code)->first()?->toArray();
		}

		return self::$currencies[$code];
	}
}
