<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Services\Auth\App\Notifications;

use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;

/*
 * Account created without verification
 */

class AccountCreated extends BaseNotification
{
	protected ?object $user;
	private string $langKey = 'auth.communication.account_created.';
	
	public function __construct(?object $user)
	{
		$this->user = $user;
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return !empty($this->user);
	}
	
	protected function determineViaChannels($notifiable): array
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (
			config('settings.mail.confirmation') == '1'
			&& !empty($this->user->email)
			&& !empty($this->user->email_verified_at)
		);
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			isPhoneAsAuthFieldEnabled()
			&& config('settings.sms.confirmation') == '1'
			&& isset($this->user->auth_field)
			&& $this->user->auth_field == 'phone'
			&& !empty($this->user->phone)
			&& !empty($this->user->phone_verified_at)
			&& !isDemoDomain()
		);
		
		if ($emailNotificationCanBeSent) {
			return ['mail'];
		}
		
		if ($smsNotificationCanBeSent) {
			if (config('settings.sms.driver') == 'twilio') {
				return [TwilioChannel::class];
			}
			
			return ['vonage'];
		}
		
		return [];
	}
	
	public function toMail($notifiable): MailMessage
	{
		return (new MailMessage)
			->subject(trans($this->langKey . 'mail.subject', ['appName' => config('app.name')]))
			->greeting(trans($this->langKey . 'mail.greeting', ['userName' => $this->user->name]))
			->line(trans($this->langKey . 'mail.body', ['appName' => config('app.name')]))
			->line(trans($this->langKey . 'mail.security_notes', ['appName' => config('app.name')]))
			->line(
				trans($this->langKey . 'mail.footer_info', ['appName' => config('app.name')])
				. ' ' . trans($this->langKey . 'mail.footer_no_action')
			)
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
	
	public function toVonage($notifiable): VonageMessage
	{
		return (new VonageMessage())->content($this->getSmsMessage())->unicode();
	}
	
	public function toTwilio($notifiable): TwilioSmsMessage|TwilioMessage
	{
		return (new TwilioSmsMessage())->content($this->getSmsMessage());
	}
	
	// PRIVATE
	
	private function getSmsMessage(): string
	{
		$msg = trans($this->langKey . 'sms', [
			'appName'  => config('app.name'),
			'userName' => $this->user->name,
		]);
		
		return castToString($msg);
	}
}
