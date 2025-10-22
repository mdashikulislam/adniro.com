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

use App\Helpers\Common\Date;
use App\Helpers\Common\Date\TimeZoneManager;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserRegistered extends BaseNotification
{
	protected ?object $user;
	protected string $todayDateFormatted;
	protected string $todayTimeFormatted;
	private string $langKey = 'auth.communication.user_registered.';
	
	public function __construct(?object $user)
	{
		$this->user = $user;
		
		// Get timezone
		$tz = TimeZoneManager::getContextualTimeZone();
		
		// Get today date & time
		$this->todayDateFormatted = Date::format(now($tz));
		$this->todayTimeFormatted = now($tz)->format('H:i');
	}
	
	protected function shouldSendNotificationWhen($notifiable): bool
	{
		return !empty($this->user);
	}
	
	protected function determineViaChannels($notifiable): array
	{
		return ['mail'];
	}
	
	public function toMail($notifiable): MailMessage
	{
		return (new MailMessage)
			->subject(trans($this->langKey . 'mail.subject'))
			->greeting(trans($this->langKey . 'mail.greeting'))
			->line(trans($this->langKey . 'mail.body', ['name' => $this->user->name]))
			->line(trans($this->langKey . 'mail.details', [
				'now'       => $this->todayDateFormatted,
				'time'      => $this->todayTimeFormatted,
				'authField' => $this->user->auth_field ?? '-',
				'email'     => !empty($this->user->email) ? $this->user->email : '-',
				'phone'     => !empty($this->user->phone) ? $this->user->phone : '-',
			]))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
}
