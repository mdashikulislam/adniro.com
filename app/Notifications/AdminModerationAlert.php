<?php

namespace App\Notifications;

use App\Models\PostModeration;
use App\Notifications\Concerns\PlainMailText;
use App\Services\Ai\Moderation\Policy;
use Illuminate\Notifications\Messages\MailMessage;

/*
 * Tells the staff that a listing needs a person (adniro:ai).
 * Throttled by ModerationService: at most 20 an hour, then a count of the ones left out.
 */

class AdminModerationAlert extends BaseNotification
{
	use PlainMailText;
	
	protected ?object $post;
	protected array $categories;
	protected ?string $internalReason;
	protected ?string $rule;
	
	public function __construct(?object $post, ?PostModeration $decision, protected int $skipped = 0, protected bool $urgent = false)
	{
		$this->post = $post;
		$this->categories = array_values((array)($decision?->categories ?? []));
		$this->internalReason = $decision?->internal_reason;
		$this->rule = $decision?->rule;
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return !empty($this->post);
	}
	
	protected function determineViaChannels($notifiable): array
	{
		return ['mail'];
	}
	
	public function toMail($notifiable): MailMessage
	{
		$title = self::plainMailText($this->post->title, 60);
		
		$message = (new MailMessage)
			->subject(trans($this->urgent ? 'ai.alert_subject_urgent' : 'ai.alert_subject', ['title' => $title]))
			->greeting(trans('ai.alert_greeting'))
			->line(e(trans('ai.alert_intro', ['title' => $title, 'id' => $this->post->id])));
		
		$labels = array_map(fn ($code) => Policy::label($code), array_filter(
			$this->categories,
			fn ($code) => is_string($code) && Policy::severity($code) !== null
		));
		if (!empty($labels)) {
			$message->line('<strong>' . e(trans('ai.alert_categories')) . '</strong> ' . e(implode(', ', $labels)));
		}
		if (!empty($this->internalReason)) {
			$message->line('<strong>' . e(trans('ai.alert_note')) . '</strong> ' . e($this->internalReason));
		} else if (!empty($this->rule)) {
			$message->line('<strong>' . e(trans('ai.alert_note')) . '</strong> ' . e(trans('ai.rule_' . $this->rule)));
		}
		if ($this->skipped > 0) {
			$message->line(e(trans('ai.alert_skipped', ['count' => $this->skipped])));
		}
		
		return $message->action(trans('ai.alert_open_queue'), urlGen()->adminUrl('moderation?status=needs_review'));
	}
}
