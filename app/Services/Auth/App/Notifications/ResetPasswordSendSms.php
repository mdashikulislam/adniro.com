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
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Throwable;

class ResetPasswordSendSms extends BaseNotification
{
	protected ?object $user;
	protected ?string $token;
	private string $langKey = 'auth.communication.reset_password_code.';
	
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
		
		return !empty($this->user->phone);
	}
	
	protected function determineViaChannels($notifiable): array
	{
		if (config('settings.sms.driver') == 'twilio') {
			return [TwilioChannel::class];
		}
		
		return ['vonage'];
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
			'token'            => $this->token,
			'expireTimeString' => $this->expireTimeString,
		]);
		
		return castToString($msg);
	}
}
