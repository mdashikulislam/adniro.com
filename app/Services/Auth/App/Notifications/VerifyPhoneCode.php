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
use Illuminate\Notifications\Messages\VonageMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioMessage;
use NotificationChannels\Twilio\TwilioSmsMessage;

class VerifyPhoneCode extends BaseNotification
{
	protected ?object $object;
	protected ?array $entityMetadata;
	private string $langKey = 'auth.communication.field_verification_code.';
	
	public function __construct(?object $object, ?array $entityMetadata)
	{
		$this->object = $object;
		$this->entityMetadata = $entityMetadata;
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		if (empty($this->object) || empty($this->entityMetadata)) {
			return false;
		}
		
		if (!isset($this->entityMetadata['nameColumn'])) {
			return false;
		}
		
		return (empty($this->object->phone_verified_at) && !empty($this->object->phone_token));
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
		$token = $this->object->phone_token;
		$countryCode = $this->object->country_code;
		
		$tokenUrl = urlGen()->phoneVerification($this->entityMetadata['key'], $token, $countryCode);
		
		$msg = trans($this->langKey . 'sms', [
			'appName'  => config('app.name'),
			'token'    => $token,
			'tokenUrl' => $tokenUrl,
		]);
		
		return castToString($msg);
	}
}
