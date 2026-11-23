<?php

namespace App\Services\Chat;

use App\Models\Chat\ChatParticipant;
use App\Models\Chat\ChatThread;
use App\Models\User;
use App\Notifications\ChatUnreadDigest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * The unread-messages email (adniro). A member who has left messages unread for ten minutes gets one email
 * listing those conversations; a conversation is not mentioned again until it has a newer message, and at
 * most once an hour. Muted or hidden conversations, blocked people, members without an email address and
 * members who turned the email off (ChatEmailChoice) are skipped. It is written in the member's own language.
 */
class ChatDigest
{
	public const UNREAD_AFTER_MINUTES = 10;
	public const AGAIN_AFTER_MINUTES = 60;
	private const MAX_MEMBERS_PER_RUN = 200;

	/** Sends the emails that are due. Returns how many members were emailed. */
	public static function send(): int
	{
		$sent = 0;
		foreach (self::due()->groupBy('user_id')->take(self::MAX_MEMBERS_PER_RUN) as $userId => $participants) {
			$sent += self::sendTo((int)$userId, $participants) ? 1 : 0;
		}

		return $sent;
	}

	/** @return Collection<int, ChatParticipant> */
	private static function due(): Collection
	{
		$threads = (new ChatThread())->getTable();
		$participants = (new ChatParticipant())->getTable();

		return ChatParticipant::query()
			->select($participants . '.*')
			->join($threads, $threads . '.id', '=', $participants . '.thread_id')
			->whereNull($threads . '.deleted_at')
			->visible()
			->where($participants . '.unread_count', '>', 0)
			->where($threads . '.last_message_at', '<=', now()->subMinutes(self::UNREAD_AFTER_MINUTES))
			->where(function ($query) use ($participants, $threads) {
				$query->whereNull($participants . '.digest_sent_at')
					->orWhere(function ($again) use ($participants, $threads) {
						$again->whereColumn($participants . '.digest_sent_at', '<', $threads . '.last_message_at')
							->where($participants . '.digest_sent_at', '<=', now()->subMinutes(self::AGAIN_AFTER_MINUTES));
					});
			})
			->where(function ($query) use ($participants) {
				$query->whereNull($participants . '.muted_until')->orWhere($participants . '.muted_until', '<=', now());
			})
			->with(['thread.participants.user'])
			->orderBy($participants . '.id')
			->get();
	}

	/** The member's own language when the chat has texts in it, or the site's. The job runs without a visitor. */
	private static function languageOf(User $user): string
	{
		$code = (string)$user->language_code;

		return preg_match('/^[A-Za-z_-]{2,10}$/', $code) === 1 && is_file(lang_path($code . '/chat.php'))
			? $code
			: (string)config('app.locale');
	}

	/** @param Collection<int, ChatParticipant> $participants */
	private static function sendTo(int $userId, Collection $participants): bool
	{
		$user = User::query()->withoutGlobalScopes()->find($userId);
		$blocks = ChatAccess::blocksOf($userId);

		$conversations = $participants
			->map(function (ChatParticipant $participant) use ($userId, $blocks) {
				$other = $participant->thread?->otherParticipant($userId);
				if ($other === null || in_array((int)$other->user_id, $blocks['blocked'], true)) {
					return null;
				}

				return ['name' => $other->user?->name, 'title' => $participant->thread->subject, 'count' => (int)$participant->unread_count];
			})
			->filter()
			->values()
			->all();

		// Marked even when nothing is sent, so the same rows are not picked up every minute
		ChatParticipant::query()->whereIn('id', $participants->pluck('id'))->update(['digest_sent_at' => now()]);

		if ($user === null || empty($user->email) || empty($conversations) || !ChatEmailChoice::isOn($user)) {
			return false;
		}

		try {
			$notification = new ChatUnreadDigest($conversations, ChatEmailChoice::offUrl($userId));
			$user->notify($notification->locale(self::languageOf($user)));
		} catch (Throwable $e) {
			Log::warning('Chat: unread digest failed', ['user' => $userId, 'error' => $e->getMessage()]);

			return false;
		}

		return true;
	}
}
