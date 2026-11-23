<?php

namespace App\Services\Chat;

/*
 * What the browser needs to keep the chat live (adniro): the member's unread total, and, when a live server
 * (Laravel Reverb) is configured, where to connect. Without one the value of "live" is null: the pages then
 * load no socket code, and the chat page checks for new messages by itself.
 */
class ChatLive
{
	private const PUSHER_JS = 'assets/plugins/pusher-js/8.4.0/pusher.min.js';
	private const ECHO_JS = 'assets/plugins/laravel-echo/2.2.6/echo.iife.js';
	private const HTTPS_PORT = 443;

	public static function isEnabled(): bool
	{
		return config('broadcasting.default') === 'reverb' && !empty(config('broadcasting.connections.reverb.key'));
	}

	public static function browserConfig(int $userId): array
	{
		return [
			'user_id'      => $userId,
			'unread_total' => ChatUnread::total($userId),
			'unread_url'   => route('chat.api.unread'),
			'live'         => self::isEnabled() ? self::connection() : null,
		];
	}

	/**
	 * Browsers connect to the site's own address (nginx passes /app on to the live server), unless the
	 * REVERB_CLIENT_* settings say otherwise. On a plain http site (local) they connect to the server's port.
	 */
	private static function connection(): array
	{
		$client = (array)config('broadcasting.connections.reverb.client');
		$isSecure = request()->isSecure();
		$scheme = ($client['scheme'] ?? null) ?: ($isSecure ? 'https' : 'http');
		$serverPort = (int)config('broadcasting.connections.reverb.options.port');

		return [
			'key'        => (string)config('broadcasting.connections.reverb.key'),
			'host'       => ($client['host'] ?? null) ?: request()->getHost(),
			'port'       => (int)(($client['port'] ?? null) ?: ($scheme === 'https' ? self::HTTPS_PORT : $serverPort)),
			'scheme'     => $scheme,
			'auth_url'   => url('broadcasting/auth'),
			'pusher_src' => url(self::PUSHER_JS),
			'echo_src'   => url(self::ECHO_JS),
		];
	}
}
