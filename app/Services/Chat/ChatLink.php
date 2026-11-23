<?php

namespace App\Services\Chat;

use Hashids\Hashids;

/*
 * The address of a chat conversation (adniro): /chat/<a 12-character code> instead of /chat/<its number>, so
 * the address does not show how many conversations the site has, or invite trying the next number. Who may
 * open a conversation is still decided by ChatAccess: the code is only its name in the address.
 *
 * The code is made from the conversation's number with a key derived from the app's own secret (APP_KEY), so
 * it cannot be worked back to the number from outside. Old addresses with the number still work: they are
 * sent on to the coded one (ChatController::legacy).
 */
class ChatLink
{
	public const KEY_LENGTH = 12;
	public const KEY_PATTERN = '[A-Za-z0-9]{12,20}';
	private const ALPHABET = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

	private static ?Hashids $hashids = null;

	public static function key(int $threadId): string
	{
		return self::hashids()->encode($threadId);
	}

	/** The conversation's number, or null for a code that is not one of ours. */
	public static function threadIdFrom(string $key): ?int
	{
		$decoded = self::hashids()->decode($key);
		$threadId = (int)($decoded[0] ?? 0);

		// Only the one code made for a number counts (Hashids can read other strings as numbers too)
		return $threadId > 0 && count($decoded) === 1 && self::key($threadId) === $key ? $threadId : null;
	}

	/** The page of a conversation. Relative by default: it is also pushed to the other person's browser. */
	public static function url(int $threadId, bool $absolute = false): string
	{
		return route('chat.show', ['key' => self::key($threadId)], $absolute);
	}

	private static function hashids(): Hashids
	{
		return self::$hashids ??= new Hashids(
			substr(hash('sha256', 'adniro-chat-link|' . config('app.key')), 0, 32),
			self::KEY_LENGTH,
			self::ALPHABET
		);
	}
}
