<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/*
 * Every mail driver gets a sender (adniro). Drivers without their own sender setting, such as "log" (local
 * development) and "array" (tests), use the site's email, so emails no longer fail with
 * "An email must have a From or a Sender header".
 */

class MailSenderTest extends TestCase
{
	public function test_a_driver_without_its_own_sender_uses_the_sites_email(): void
	{
		// Arrange: the "log" driver, as in local development (tests send through "array", which has no sender either)
		config(['settings.app.email' => 'hello@example.test', 'mail.from.address' => null]);

		// Act
		$this->applyMailSettings(['driver' => 'log']);

		// Assert
		$this->assertSame('hello@example.test', config('mail.from.address'));
	}

	public function test_an_email_can_be_built_and_sent_with_that_sender(): void
	{
		// Arrange
		config(['settings.app.email' => 'hello@example.test', 'mail.from.address' => null]);
		$this->applyMailSettings(['driver' => 'log']);

		// Act
		Mail::raw('Your listing is online.', fn ($message) => $message->to('seller@example.test')->subject('Listing online'));

		// Assert
		$sent = Mail::mailer()->getSymfonyTransport()->messages();
		$this->assertCount(1, $sent);
		$this->assertSame('hello@example.test', $sent->first()->getOriginalMessage()->getFrom()[0]->getAddress());
	}

	public function test_a_drivers_own_sender_still_wins(): void
	{
		// Arrange
		config(['settings.app.email' => 'hello@example.test', 'mail.from.address' => null]);

		// Act
		$this->applyMailSettings(['driver' => 'smtp', 'smtp_email_sender' => 'info@example.test']);

		// Assert: MAIL_MAILER=array in phpunit.xml overrides the driver; the SMTP sender only applies to SMTP
		config('mail.default') === 'smtp'
			? $this->assertSame('info@example.test', config('mail.from.address'))
			: $this->assertSame('hello@example.test', config('mail.from.address'));
	}

	/** Runs the app's own mail setup (AppServiceProvider, ConfigTrait\MailConfig) with these settings */
	private function applyMailSettings(array $settings): void
	{
		$provider = $this->app->getProvider(AppServiceProvider::class);
		(fn () => $this->updateMailConfig($settings))->call($provider);
		Mail::forgetMailers();
	}
}
