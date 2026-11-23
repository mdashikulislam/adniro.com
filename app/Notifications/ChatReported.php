<?php

namespace App\Notifications;

use App\Models\Chat\ChatMessage;
use App\Models\Chat\ThreadReport;
use App\Services\Chat\ChatOffers;
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
	private const NAME_LIMIT = 40;

	public function __construct(protected ThreadReport $report)
	{
	}

	protected function determineViaChannels($notifiable): array
	{
		return ['mail'];
	}

	public function toMail($notifiable): MailMessage
	{
		$report = $this->report->loadMissing(['reporter', 'thread.participants.user']);
		$thread = $report->thread;
		$subject = self::plainMailText($thread?->subject, 60);
		$reportedId = $report->reportedUserId();
		$names = [
			(int)$report->reporter_id => $this->nameOf($report->reporter?->name, (int)$report->reporter_id),
			$reportedId               => $this->nameOf($thread?->participantFor($reportedId)?->user?->name, $reportedId),
		];

		$mail = (new MailMessage)
			->subject(trans('chat.report_mail_subject', ['title' => $subject]))
			->greeting(trans('chat.report_mail_greeting'))
			->line(e(trans('chat.report_mail_intro', [
				'title'    => $subject,
				'reporter' => $names[(int)$report->reporter_id],
				'reported' => $names[$reportedId],
			])))
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
			$text = match (true) {
				$message->isImage()       => '[' . trans('chat.js.photo') . ']',
				$message->isOffer()       => '[' . trans('chat.js.offer_line', ['amount' => ChatOffers::amountOf($message)]) . ']',
				$message->isOfferAnswer() => '[' . trans('chat.js.offer_' . $message->offer_status . '_line', ['amount' => ChatOffers::amountOf($message)]) . ']',
				default                   => self::plainMailText($message->body, self::MESSAGE_LIMIT),
			};
			$mail->line(e(($names[(int)$message->user_id] ?? '#' . $message->user_id) . ': ' . $text));
		}

		// The whole conversation, and closing the report, are in the admin panel
		return $mail
			->action(trans('chat.report_mail_action'), urlGen()->adminUrl('chat-reports/' . $report->id))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}

	/** A member's name with their number, safe for an email. */
	private function nameOf(?string $name, int $userId): string
	{
		return trim(self::plainMailText($name ?? trans('chat.deleted_member'), self::NAME_LIMIT) . ' #' . $userId);
	}
}
