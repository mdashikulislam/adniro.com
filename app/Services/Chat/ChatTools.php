<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatParticipant;
use App\Models\Chat\ChatPreference;
use App\Models\Chat\ChatThread;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * A member's own chat tools (adniro): replies they saved to answer the same question quickly, and an away
 * message, sent for them while they cannot answer.
 *
 * The away message is sent once per conversation every twelve hours, as a message of the member marked
 * "automatic reply". It never answers another automatic reply, so two members who are both away do not
 * write to each other for ever.
 */
class ChatTools
{
	public const SAVED_REPLIES_MAX = 5;
	public const TEXT_MAX = 300;
	public const AWAY_AGAIN_AFTER_HOURS = 12;

	/** @return array{away_on: bool, away_message: string, saved_replies: array<int, string>} */
	public static function of(int $userId): array
	{
		$preference = ChatPreference::query()->where('user_id', $userId)->first();

		return [
			'away_on'       => (bool)($preference?->away_on ?? false),
			'away_message'  => (string)($preference?->away_message ?? ''),
			'saved_replies' => array_values((array)($preference?->saved_replies ?? [])),
		];
	}

	/**
	 * @param array<int, string> $savedReplies
	 * @return array{away_on: bool, away_message: string, saved_replies: array<int, string>}
	 */
	public static function save(int $userId, bool $isAway, string $awayMessage, array $savedReplies): array
	{
		ChatPreference::query()->updateOrCreate(['user_id' => $userId], [
			'away_on'       => $isAway,
			'away_message'  => $awayMessage === '' ? null : $awayMessage,
			'saved_replies' => array_values($savedReplies),
		]);

		return self::of($userId);
	}

	/** The member's account is deleted. */
	public static function forget(int $userId): void
	{
		ChatPreference::query()->where('user_id', $userId)->delete();
	}

	/**
	 * After a message was sent: if the person it is for is away, their away message answers it. A failure here
	 * never fails the message that was sent.
	 */
	public static function answerWhileAway(ChatThread $thread, ChatMessage $message): void
	{
		if ($message->is_auto) {
			return;
		}

		try {
			$away = $thread->otherParticipant((int)$message->user_id);
			$text = $away === null ? '' : self::awayMessageOf((int)$away->user_id);
			if ($text === '' || !self::claimAwayReply($away)) {
				return;
			}

			ChatMessages::send($thread, (int)$away->user_id, $text, special: ['is_auto' => true]);
		} catch (Throwable $e) {
			Log::warning('Chat: away message failed', ['thread' => $thread->id, 'error' => $e->getMessage()]);
		}
	}

	private static function awayMessageOf(int $userId): string
	{
		$preference = ChatPreference::query()->where('user_id', $userId)->where('away_on', true)->first();

		return trim((string)$preference?->away_message);
	}

	/** True for one request only, every twelve hours: two messages arriving together get one away message. */
	private static function claimAwayReply(ChatParticipant $away): bool
	{
		$claimed = ChatParticipant::query()
			->whereKey($away->id)
			->where(function ($query) {
				$query->whereNull('away_replied_at')
					->orWhere('away_replied_at', '<=', now()->subHours(self::AWAY_AGAIN_AFTER_HOURS));
			})
			->update(['away_replied_at' => now()]);

		return $claimed > 0;
	}
}
