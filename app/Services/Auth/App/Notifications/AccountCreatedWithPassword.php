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

class AccountCreatedWithPassword extends BaseNotification
{
	protected ?object $user;
	protected ?string $generatedPassword;
	private string $langKey = 'auth.communication.account_created_with_password.';
	
	public function __construct(?object $user, ?string $generatedPassword)
	{
		$this->user = $user;
		$this->generatedPassword = $generatedPassword;
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return (!empty($this->user) && !empty($this->generatedPassword));
	}
	
	protected function determineViaChannels($notifiable): array
	{
		$authField = $this->user->auth_field ?? getAuthField();
		
		if ($authField == 'phone') {
			if (config('settings.sms.driver') == 'twilio') {
				return [TwilioChannel::class];
			}
			
			return ['vonage'];
		} else {
			return ['mail'];
		}
	}
	
	public function toMail($notifiable): MailMessage
	{
		$token = $this->user->email_token;
		$countryCode = $this->user->country_code;
		
		$verificationUrl = urlGen()->emailVerification('users', $token, $countryCode);
		$loginUrl = urlGen()->signIn();
		
		$mailMessage = (new MailMessage)
			->subject(trans($this->langKey . 'mail.subject'))
			->greeting(trans($this->langKey . 'mail.greeting', ['userName' => $this->user->name]))
			->line(trans($this->langKey . 'mail.body'));
		
		// Show the email or phone verification info & link,
		// when user has not yet verified his email address or his phone number.
		// Note: When the email address and phone number verification
		// is disabled the user is mentioned verified automatically
		if (!isVerifiedUser($this->user)) {
			$mailMessage->line(trans($this->langKey . 'mail.action_info'))
				->action(trans($this->langKey . 'mail.action_text'), $verificationUrl);
		}
		
		$mailMessage->line(trans($this->langKey . 'mail.password_info', ['generatedPassword' => $this->generatedPassword]));
		
		// Show the login link when the user email address and phone number are verified
		// Note: When the email address and phone number verification
		// is disabled the user is mentioned verified automatically
		if (isVerifiedUser($this->user)) {
			$mailMessage->action(trans($this->langKey . 'mail.login_prompt'), $loginUrl);
		}
		
		$mailMessage->line(
			trans($this->langKey . 'mail.footer_info', ['appName' => config('app.name')])
			. ' ' . trans($this->langKey . 'mail.footer_no_action')
		)
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
		
		return $mailMessage;
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
		$token = $this->user->phone_token;
		$countryCode = $this->user->country_code;
		
		$tokenUrl = urlGen()->phoneVerification('users', $token, $countryCode);
		
		$msg = trans($this->langKey . 'sms', [
			'appName'        => config('app.name'),
			'generatedPassword' => $this->generatedPassword,
			'token'          => $token,
			'tokenUrl'       => $tokenUrl,
		]);
		
		return castToString($msg);
	}
}
