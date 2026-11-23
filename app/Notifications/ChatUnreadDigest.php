<?php

namespace App\Notifications;

use App\Notifications\Concerns\PlainMailText;
use Illuminate\Notifications\Messages\MailMessage;

/*
 * One "you have unread messages" email for a member (adniro), instead of one email per message. It names who
 * wrote and about which listing, never the messages themselves. Sent by the chat:unread-digest command.
 */
class ChatUnreadDigest extends BaseNotification
{
	use PlainMailText;

	private const NAME_LIMIT = 40;
	private const TITLE_LIMIT = 60;

	/** @param array<int, array{name: ?string, title: ?string, count: int}> $conversations */
	public function __construct(protected array $conversations)
	{
	}

	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return !empty($this->conversations);
	}

	protected function determineViaChannels($notifiable): array
	{
		return !empty($notifiable->email) ? ['mail'] : [];
	}

	public function toMail($notifiable): MailMessage
	{
		$total = array_sum(array_column($this->conversations, 'count'));

		$mail = (new MailMessage)
			->subject(trans_choice('chat.digest_subject', $total, ['count' => $total, 'appName' => config('app.name')]))
			->greeting(trans('chat.digest_greeting', ['name' => self::plainMailText($notifiable->name, self::NAME_LIMIT)]))
			->line(e(trans_choice('chat.digest_intro', $total, ['count' => $total])));

		foreach ($this->conversations as $conversation) {
			$mail->line(e(trans_choice('chat.digest_line', $conversation['count'], [
				'count' => $conversation['count'],
				'name'  => self::plainMailText($conversation['name'], self::NAME_LIMIT),
				'title' => self::plainMailText($conversation['title'], self::TITLE_LIMIT),
			])));
		}

		return $mail
			->action(trans('chat.digest_action'), route('chat.index'))
			->line(e(trans('chat.digest_safety')))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
}
