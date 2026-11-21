<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Every email carries the brand colour, the logo and help, safety and preference links (adniro, phase 4).
 */

class BrandedEmailTest extends TestCase
{
	use RefreshDatabase;
	
	public function test_an_email_has_the_logo_brand_colour_and_footer_links(): void
	{
		// Arrange
		config(['settings.app.name' => 'Adniro', 'settings.app.logo_url' => 'https://example.test/logo.png']);
		DB::table('pages')->insert([
			'name' => json_encode(['en' => 'Safety']), 'slug' => 'anti-scam', 'title' => json_encode(['en' => 'Safety']),
			'content' => json_encode(['en' => 'Stay safe']), 'type' => 'tips', 'active' => 1,
		]);
		$mail = (new MailMessage())->subject('Hello')->line('Your listing is online.')->action('View listing', 'https://example.test/l/1');
		
		// Act
		$html = (string)$mail->render();
		
		// Assert
		$this->assertStringContainsString('alt="Adniro"', $html);
		$this->assertStringContainsString('#0d9488', $html, 'the brand colour');
		$this->assertStringContainsString('>Help</a>', $html);
		$this->assertStringContainsString('>Safety tips</a>', $html);
		$this->assertStringContainsString('/preferences"', $html);
		$this->assertStringContainsString('because you have an account or a listing on Adniro', $html);
	}
}
