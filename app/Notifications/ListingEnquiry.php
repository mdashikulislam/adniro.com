<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use App\Notifications\Concerns\PlainMailText;
use Illuminate\Notifications\Messages\MailMessage;

/*
 * A member's message to the seller of a listing posted without a member account (adniro): such a listing
 * cannot be chatted with, so the message is emailed. Replying to the email answers the member directly.
 * The member's words are written as plain text: no markup, no links of their making.
 */
class ListingEnquiry extends BaseNotification
{
	use PlainMailText;

	private const NAME_LIMIT = 40;
	private const TITLE_LIMIT = 60;
	private const LINE_LIMIT = 1000;

	public function __construct(protected Post $post, protected User $sender, protected string $message)
	{
	}

	protected function determineViaChannels($notifiable): array
	{
		return ['mail'];
	}

	public function toMail($notifiable): MailMessage
	{
		$name = self::plainMailText($this->sender->name, self::NAME_LIMIT);
		$title = self::plainMailText($this->post->title, self::TITLE_LIMIT);

		return (new MailMessage)
			->replyTo($this->sender->email, $name)
			->subject(trans('chat.enquiry_subject', ['title' => $title]))
			->greeting(trans('chat.enquiry_greeting'))
			->line(e(trans('chat.enquiry_intro', ['name' => $name, 'title' => $title, 'appName' => config('app.name')])))
			->line($this->quotedMessage())
			->line(e(trans('chat.enquiry_reply', ['name' => $name])))
			->action(trans('chat.enquiry_action'), urlGen()->post($this->post))
			->line(e(trans('chat.digest_safety')))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}

	/** The member's message, line by line, with nothing in it that an email would treat as markup. */
	private function quotedMessage(): string
	{
		$lines = array_map(fn (string $line) => self::plainLine($line), preg_split('/\R/u', $this->message) ?: []);

		// "<br>" for the email's HTML part; the line break becomes a space in its plain-text part
		return implode("<br>\n", array_filter($lines, fn (string $line) => $line !== ''));
	}

	/**
	 * One line as plain text. "<" and ">" are kept, escaped ("Can you do <500?"); the characters an email
	 * would read as formatting or as a link of the sender's making are removed.
	 */
	private static function plainLine(string $line): string
	{
		$line = preg_replace('/[\[\]*_`#|~\\\\]+/u', ' ', $line) ?? '';
		$line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');

		return e((string)str($line)->limit(self::LINE_LIMIT));
	}
}
