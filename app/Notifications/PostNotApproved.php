<?php

namespace App\Notifications;

use App\Models\PostModeration;
use App\Notifications\Concerns\PlainMailText;
use App\Services\Ai\Moderation\Policy;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;

/*
 * "Your listing was not approved" (adniro:ai).
 *
 * Sent once per rejection decision, by email to a verified address,
 * or by SMS to owners who signed up with their phone number.
 * The reason is written by the AI: it is cleaned before it is stored and escaped here.
 */

class PostNotApproved extends BaseNotification
{
	use PlainMailText;
	
	protected ?object $post;
	protected array $categories;
	protected ?string $reason;
	
	public function __construct(?object $post, ?PostModeration $decision = null)
	{
		$this->post = $post;
		$this->categories = array_values((array)($decision?->categories ?? []));
		$this->reason = $decision?->user_reason;
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return !empty($this->post);
	}
	
	protected function determineViaChannels($notifiable): array
	{
		$channels = [];
		
		if (!empty($this->post->email) && !empty($this->post->email_verified_at)) {
			$channels[] = 'mail';
		}
		
		$smsCanBeSent = (
			isPhoneAsAuthFieldEnabled()
			&& ($this->post->auth_field ?? null) == 'phone'
			&& !empty($this->post->phone)
			&& !empty($this->post->phone_verified_at)
			&& !isDemoDomain()
		);
		if ($smsCanBeSent) {
			if (config('settings.sms.driver') == 'twilio') {
				$channels[] = TwilioChannel::class;
			}
			if (config('settings.sms.driver') == 'vonage') {
				$channels[] = 'vonage';
			}
		}
		
		return $channels;
	}
	
	public function toMail($notifiable): MailMessage
	{
		$title = self::plainMailText($this->post->title, 80);
		$appName = (string)config('app.name');
		
		$message = (new MailMessage)
			->subject(trans('ai.mail_not_approved_subject', ['title' => self::plainMailText($this->post->title, 50)]))
			->greeting(trans('ai.mail_not_approved_greeting'))
			->line(e(trans('ai.mail_not_approved_intro', ['title' => $title, 'appName' => $appName])));
		
		$labels = $this->categoryLabels();
		if (!empty($labels)) {
			$message->line('<strong>' . e(trans('ai.mail_not_approved_categories')) . '</strong> ' . e(implode(', ', $labels)));
		}
		if (!empty($this->reason)) {
			$message->line('<strong>' . e(trans('ai.mail_not_approved_reason')) . '</strong> ' . e($this->reason));
		}
		
		return $message
			->line(e(trans('ai.mail_not_approved_fix')))
			->action(trans('ai.mail_not_approved_edit'), urlGen()->editPost($this->post))
			->line(trans('ai.mail_not_approved_contact', ['contactUrl' => e(urlGen()->contact())]))
			->salutation(trans('mail.footer_salutation', ['appName' => $appName]));
	}
	
	public function toVonage($notifiable): VonageMessage
	{
		return (new VonageMessage())->content($this->smsMessage())->unicode();
	}
	
	public function toTwilio($notifiable): TwilioSmsMessage|TwilioMessage
	{
		return (new TwilioSmsMessage())->content($this->smsMessage());
	}
	
	private function categoryLabels(): array
	{
		return array_map(fn ($code) => Policy::label((string)$code), array_filter(
			$this->categories,
			fn ($code) => is_string($code) && Policy::severity($code) !== null
		));
	}
	
	private function smsMessage(): string
	{
		return castToString(trans('ai.sms_not_approved', [
			'appName' => config('app.name'),
			'title'   => (string)str($this->post->title)->limit(40),
		]));
	}
}
