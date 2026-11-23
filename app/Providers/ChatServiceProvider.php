<?php

namespace App\Providers;

use App\Events\Chat\ChatEvent;
use App\Models\User;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/*
 * Live chat wiring (adniro): the live channels and who may listen to them, and the send limits.
 * Past a limit the reply is the site's usual 429; the chat page shows its own "slow down" text for it.
 * Channels are declared here, not in a routes file, so they are still known when the routes are cached.
 */
class ChatServiceProvider extends ServiceProvider
{
	private const NEW_ACCOUNT_HOURS = 24;

	/** An account's first day gets this share of the send limits set in Admin > Settings > Chat. */
	private const NEW_ACCOUNT_SHARE = ['minute' => 0.4, 'day' => 0.2];
	/** [per minute, per day] for an established member and for an account's first day. */
	private const START_LIMITS = ['member' => [5, 30], 'new' => [2, 10]];
	private const REPORTS_PER_HOUR = 5;
	/** "Email the seller" (listings without a member account): a real email each time, so far fewer than chat. */
	private const EMAIL_SELLER_LIMITS = ['member' => [3, 10], 'new' => [2, 3]];
	private const EMAILS_PER_LISTING_PER_DAY = 2;
	/** From all members together: several accounts cannot be used to flood one seller's inbox. */
	private const EMAILS_TO_ONE_LISTING_PER_DAY = 10;

	public function boot(): void
	{
		$this->registerChannels();
		$this->registerLimits();
	}

	private function registerChannels(): void
	{
		Broadcast::routes(['middleware' => ['web', 'auth', 'twoFactor', 'banned.user']]);

		// A member's own channel: new messages, read receipts and the unread badge
		Broadcast::channel(ChatEvent::CHANNEL_PREFIX . '{userId}', function (User $user, int $userId) {
			return (int)$user->id === $userId;
		});

		// A conversation's presence channel: who is online in it, and typing (browser to browser)
		Broadcast::channel('chat.thread.{threadId}', function (User $user, int $threadId) {
			if (!ChatAccess::canSeePresence($threadId, (int)$user->id)) {
				return false;
			}

			return ['id' => (int)$user->id];
		});
	}

	private function registerLimits(): void
	{
		RateLimiter::for('chat-send', fn (Request $request) => $this->limits($request, $this->sendLimits(), 'send'));
		RateLimiter::for('chat-start', fn (Request $request) => $this->limits($request, self::START_LIMITS, 'start'));
		RateLimiter::for('chat-email-seller', fn (Request $request) => array_merge(
			$this->limits($request, self::EMAIL_SELLER_LIMITS, 'email-seller'),
			[
				Limit::perDay(self::EMAILS_PER_LISTING_PER_DAY)->by('chat-email-seller:' . $request->user()?->id . ':' . $request->route('post')),
				Limit::perDay(self::EMAILS_TO_ONE_LISTING_PER_DAY)->by('chat-email-seller:listing:' . $request->route('post')),
			],
		));
		RateLimiter::for('chat-report', function (Request $request) {
			return Limit::perHour(self::REPORTS_PER_HOUR)->by('chat-report:' . $request->user()?->id);
		});
	}

	/** @return array{member: array{0: int, 1: int}, new: array{0: int, 1: int}} [per minute, per day] */
	private function sendLimits(): array
	{
		$perMinute = ChatSettings::messagesPerMinute();
		$perDay = ChatSettings::messagesPerDay();

		return [
			'member' => [$perMinute, $perDay],
			'new'    => [
				max(1, (int)round($perMinute * self::NEW_ACCOUNT_SHARE['minute'])),
				max(1, (int)round($perDay * self::NEW_ACCOUNT_SHARE['day'])),
			],
		];
	}

	/** @return array<int, Limit> */
	private function limits(Request $request, array $limits, string $name): array
	{
		$user = $request->user();
		$isNew = $user?->created_at !== null && now()->diffInHours($user->created_at, true) < self::NEW_ACCOUNT_HOURS;
		[$perMinute, $perDay] = $limits[$isNew ? 'new' : 'member'];
		$key = 'chat-' . $name . ':' . ($user?->id ?? $request->ip());

		return [
			Limit::perMinute($perMinute)->by($key . ':minute'),
			Limit::perDay($perDay)->by($key . ':day'),
		];
	}
}
