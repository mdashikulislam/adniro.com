<?php

namespace App\Services\Chat;

/*
 * The chat's settings (adniro), from Admin > Settings > Chat (App\Models\Setting\ChatSetting). Each has a
 * default, used until an admin changes it, and a safe range: a typing mistake in the admin panel cannot stop
 * the chat or open it to floods.
 */
class ChatSettings
{
	public const DEFAULTS = [
		'enabled'                    => '1',
		'photos_enabled'             => '1',
		'offers_enabled'             => '1',
		'unread_email_enabled'       => '1',
		'unread_email_delay_minutes' => '10',
		'messages_per_minute'        => '20',
		'messages_per_day'           => '300',
		'unsend_minutes'             => '60',
	];

	private const MINUTES_IN_A_DAY = 1440;

	/** Off: nobody can send, and "Chat with seller" is hidden. Conversations can still be read. */
	public static function isEnabled(): bool
	{
		return self::value('enabled') === '1';
	}

	public static function photosEnabled(): bool
	{
		return self::value('photos_enabled') === '1';
	}

	/** "Make an offer" in a conversation about a listing. */
	public static function offersEnabled(): bool
	{
		return self::value('offers_enabled') === '1';
	}

	public static function unreadEmailEnabled(): bool
	{
		return self::value('unread_email_enabled') === '1';
	}

	/** How long a message waits unread before its email. */
	public static function unreadEmailDelayMinutes(): int
	{
		return self::number('unread_email_delay_minutes', 1, self::MINUTES_IN_A_DAY);
	}

	public static function messagesPerMinute(): int
	{
		return self::number('messages_per_minute', 1, 120);
	}

	public static function messagesPerDay(): int
	{
		return self::number('messages_per_day', 10, 5000);
	}

	/** How long a sender can take a message back. 0: never. */
	public static function unsendMinutes(): int
	{
		return self::number('unsend_minutes', 0, self::MINUTES_IN_A_DAY);
	}

	private static function number(string $key, int $min, int $max): int
	{
		$value = self::value($key);

		return is_numeric($value) ? max($min, min($max, (int)$value)) : (int)self::DEFAULTS[$key];
	}

	private static function value(string $key): string
	{
		return (string)(config('settings.chat.' . $key) ?? self::DEFAULTS[$key]);
	}
}
