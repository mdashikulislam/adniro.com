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

class TwoFactorSetup extends BaseNotification
{
	protected ?object $user;
	protected bool $enable;
	private string $langKey = 'auth.communication.two_factor_enabled.';
	
	public function __construct(?object $user, $enable = true)
	{
		$this->user = $user;
		$this->enable = $enable;
		
		if (!$this->enable) {
			$this->langKey = 'auth.communication.two_factor_disabled.';
		}
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return !empty($this->user);
	}
	
	protected function determineViaChannels($notifiable): array
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (
			isTwoFactorEnabled('email')
			&& config('settings.mail.confirmation') == '1'
			&& !empty($this->user->email)
		);
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			isTwoFactorEnabled('sms')
			&& isPhoneAsAuthFieldEnabled()
			&& config('settings.sms.confirmation') == '1'
			&& isset($this->user->auth_field)
			&& $this->user->auth_field == 'phone'
			&& !empty($this->user->phone)
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
			->subject(trans($this->langKey . 'mail.subject'))
			->greeting(trans($this->langKey . 'mail.greeting', ['userName' => $this->user->name]))
			->line(trans($this->langKey . 'mail.body'))
			->line(trans($this->langKey . 'mail.security_tip'))
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
