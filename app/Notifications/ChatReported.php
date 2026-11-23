<?php

namespace App\Notifications;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ThreadReport;
use App\Notifications\Concerns\PlainMailText;
use Illuminate\Notifications\Messages\MailMessage;

/*
 * Tells the admins that a member has reported a chat conversation (adniro), with the last messages so the
 * report can be judged from the email.
 */
class ChatReported extends BaseNotification
{
	use PlainMailText;

	private const LAST_MESSAGES = 6;
	private const MESSAGE_LIMIT = 200;

	public function __construct(protected ThreadReport $report)
	{
	}

	protected function determineViaChannels($notifiable): array
	{
		return ['mail'];
	}

	public function toMail($notifiable): MailMessage
	{
		$report = $this->report;
		$thread = $report->thread;
		$subject = self::plainMailText($thread?->subject, 60);

		$mail = (new MailMessage)
			->subject(trans('chat.report_mail_subject', ['title' => $subject]))
			->greeting(trans('chat.report_mail_greeting'))
			->line(e(trans('chat.report_mail_intro', ['id' => $report->thread_id, 'title' => $subject, 'reporter' => $report->reporter_id])))
			->line('<strong>' . e(trans('chat.report_mail_reason')) . '</strong> ' . e(trans('chat.js.reason_' . $report->reason)));

		if (!empty($report->note)) {
			$mail->line('<strong>' . e(trans('chat.report_mail_note')) . '</strong> ' . e(self::plainMailText($report->note, 500)));
		}

		$messages = ChatMessage::query()
			->where('thread_id', $report->thread_id)
			->orderByDesc('id')
			->limit(self::LAST_MESSAGES)
			->get()
			->reverse();
		foreach ($messages as $message) {
			$text = $message->isImage() ? '[' . trans('chat.js.photo') . ']' : self::plainMailText($message->body, self::MESSAGE_LIMIT);
			$mail->line(e('#' . $message->user_id . ': ' . $text));
		}

		return $mail->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
}
