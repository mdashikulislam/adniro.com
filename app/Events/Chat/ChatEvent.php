<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/*
 * A chat change pushed to one member's private channel (adniro): a new message, or messages read.
 * Sent at once (no queue); the live server is on the same machine.
 */
class ChatEvent implements ShouldBroadcastNow
{
	public const MESSAGE = 'chat.message';
	public const READ = 'chat.read';
	public const CHANNEL_PREFIX = 'chat.user.';

	public function __construct(
		public readonly string $name,
		public readonly int $userId,
		public readonly array $payload,
	) {
	}

	public static function channelFor(int $userId): string
	{
		return self::CHANNEL_PREFIX . $userId;
	}

	public function broadcastOn(): array
	{
		return [new PrivateChannel(self::channelFor($this->userId))];
	}

	public function broadcastAs(): string
	{
		return $this->name;
	}

	public function broadcastWith(): array
	{
		return $this->payload;
	}
}
