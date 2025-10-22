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
use Carbon\CarbonInterval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Throwable;

class TwoFactorSendCode extends BaseNotification
{
	protected ?object $user;
	protected ?string $code;
	private string $langKey = 'auth.communication.two_factor_verification.';
	
	protected ?string $expireTimeString = null;
	
	public function __construct(?object $user, ?string $code)
	{
		$this->user = $user;
		$this->code = $code;
		
		// Two-Factor Timeout String
		// Convert seconds into days hours minutes
		$twoFactorTimeout = otpExpireTimeInSeconds();
		try {
			$this->expireTimeString = CarbonInterval::seconds($twoFactorTimeout)->cascade()->forHumans();
		} catch (Throwable $e) {
			$this->expireTimeString = $twoFactorTimeout . ' second(s)';
		}
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return (!empty($this->user) && !empty($this->code));
	}
	
	protected function determineViaChannels($notifiable): array
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (
			isTwoFactorEnabled('email')
			&& !empty($this->user->email)
			&& $this->user->two_factor_method == 'email'
		);
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			isPhoneAsAuthFieldEnabled()
			&& isTwoFactorEnabled('sms')
			&& !empty($this->user->phone)
			&& $this->user->two_factor_method == 'sms'
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
	
	public function toMail($notifiable)
	{
		return (new MailMessage)
			->subject(trans($this->langKey . 'mail.subject'))
			->greeting(trans($this->langKey . 'mail.greeting', ['userName' => $this->user->name]))
			->line(trans($this->langKey . 'mail.body', ['code' => $this->code]))
			->line(trans($this->langKey . 'mail.expiration_info', ['expireTimeString' => $this->expireTimeString]))
			->line(trans($this->langKey . 'mail.footer_info') . ' ' . trans($this->langKey . 'mail.footer_no_action'))
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
			'appName'          => config('app.name'),
			'code'             => $this->code,
			'expireTimeString' => $this->expireTimeString,
		]);
		
		return castToString($msg);
	}
}
