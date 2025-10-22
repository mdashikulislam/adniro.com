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

class VerifyEmailLink extends BaseNotification
{
	protected ?object $object;
	protected ?array $entityMetadata;
	
	public function __construct(object|int|string $object, ?array $entityMetadata)
	{
		$model = $entityMetadata['model'] ?? null;
		$scopes = $entityMetadata['scopes'] ?? [];
		
		if (!is_object($object)) {
			$object = !empty($model)
				? $model::query()->withoutGlobalScopes($scopes)->find($object)
				: null;
		}
		
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
		
		return (empty($this->object->email_verified_at) && !empty($this->object->email_token));
	}
	
	protected function determineViaChannels($notifiable): array
	{
		return ['mail'];
	}
	
	public function toMail($notifiable): MailMessage
	{
		$token = $this->object->email_token;
		$countryCode = $this->object->country_code;
		
		$verificationUrl = urlGen()->emailVerification($this->entityMetadata['key'], $token, $countryCode);
		
		return $this->sendLink($token, $verificationUrl);
	}
	
	// PRIVATE
	
	private function sendLink($token, $verificationUrl): MailMessage
	{
		$langKey = 'auth.communication.email_verification_link.';
		
		return (new MailMessage)
			->subject(trans($langKey . 'mail.subject'))
			->greeting(trans($langKey . 'mail.greeting', ['userName' => $this->object->{$this->entityMetadata['nameColumn']}]))
			->line(trans($langKey . 'mail.action_info'))
			->action(trans($langKey . 'mail.action_text'), $verificationUrl)
			->line(trans($langKey . 'mail.body', ['token' => $token]))
			->line(
				trans($langKey . 'mail.footer_info', ['appName' => config('app.name')])
				. ' ' . trans($langKey . 'mail.footer_no_action')
			)
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
	
	private function sendCode($token, $verificationUrl): MailMessage
	{
		$langKey = 'auth.communication.field_verification_code.';
		
		return (new MailMessage)
			->subject(trans($langKey . 'mail.subject'))
			->greeting(trans($langKey . 'mail.greeting', ['userName' => $this->object->{$this->entityMetadata['nameColumn']}]))
			->line(trans($langKey . 'mail.body', ['token' => $token]))
			->line(trans($langKey . 'mail.action_info'))
			->action(trans($langKey . 'mail.action_text'), $verificationUrl)
			->line(
				trans($langKey . 'mail.footer_info', ['appName' => config('app.name')])
				. ' ' . trans($langKey . 'mail.footer_no_action')
			)
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
}
