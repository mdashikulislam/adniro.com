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
use Throwable;

class ResetPasswordSendEmail extends BaseNotification
{
	protected ?object $user;
	protected ?string $token;
	
	protected ?string $expireTimeString = null;
	
	public function __construct(?object $user, ?string $token)
	{
		$this->user = $user;
		$this->token = $token;
		
		// Password Timeout String
		// Convert seconds into days hours minutes
		$passwordTimeout = otpExpireTimeInSeconds();
		try {
			$this->expireTimeString = CarbonInterval::seconds($passwordTimeout)->cascade()->forHumans();
		} catch (Throwable $e) {
			$this->expireTimeString = $passwordTimeout . ' minute(s)';
		}
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		if (empty($this->user) || empty($this->token)) {
			return false;
		}
		
		return !empty($this->user->email);
	}
	
	protected function determineViaChannels($notifiable): array
	{
		return ['mail'];
	}
	
	public function toMail($notifiable): MailMessage
	{
		$resetPasswordUrl = urlGen()->passwordReset($this->token);
		
		return isOtpEnabledForEmail()
			? $this->sendCode($resetPasswordUrl)
			: $this->sendLink($resetPasswordUrl);
	}
	
	// PRIVATE
	
	private function sendLink($resetPasswordUrl): MailMessage
	{
		$langKey = 'auth.communication.reset_password_link.';
		
		return (new MailMessage)
			->subject(trans($langKey . 'mail.subject'))
			->line(trans($langKey . 'mail.greeting'))
			->line(trans($langKey . 'mail.action_info'))
			->action(trans($langKey . 'mail.action_text'), $resetPasswordUrl)
			->line(trans($langKey . 'mail.expiration_info', ['expireTimeString' => $this->expireTimeString]))
			->line(
				trans($langKey . 'mail.footer_info')
				. ' ' . trans($langKey . 'mail.footer_no_action')
			)
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
	
	private function sendCode($resetPasswordUrl): MailMessage
	{
		$langKey = 'auth.communication.reset_password_code.';
		
		return (new MailMessage)
			->subject(trans($langKey . 'mail.subject'))
			->line(trans($langKey . 'mail.greeting'))
			->line(trans($langKey . 'mail.body', ['token' => $this->token]))
			->line(trans($langKey . 'mail.action_info'))
			->action(trans($langKey . 'mail.action_text'), $resetPasswordUrl)
			->line(trans($langKey . 'mail.expiration_info', ['expireTimeString' => $this->expireTimeString]))
			->line(
				trans($langKey . 'mail.footer_info')
				. ' ' . trans($langKey . 'mail.footer_no_action')
			)
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
}
