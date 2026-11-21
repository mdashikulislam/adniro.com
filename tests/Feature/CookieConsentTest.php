<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Analytics only after cookie consent, decided in the browser (adniro, phase 5).
 */

class CookieConsentTest extends TestCase
{
	use RefreshDatabase;
	
	private const TRACKING = "<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-TEST\"></script>\n<script>gtag('config', 'G-TEST');</script>";
	
	public function test_with_the_banner_on_the_tracking_code_waits_for_consent(): void
	{
		// Arrange
		config(['settings.other.cookie_consent_enabled' => '1', 'settings.footer.tracking_code' => self::TRACKING]);
		
		// Act
		$tracking = view('front.common.consent.tracking')->render();
		$banner = view('front.common.consent.banner')->render();
		
		// Assert: inert inside a template, and a banner that the browser shows or not
		$this->assertMatchesRegularExpression('#<template id="consentGatedScripts">.*googletagmanager.*</template>#s', $tracking);
		$this->assertStringContainsString('id="cookieConsent"', $banner);
		$this->assertStringContainsString('d-none', $banner, 'hidden until consent.js decides, so cached pages work');
		$this->assertStringContainsString('consent.js', $banner);
	}
	
	public function test_with_the_banner_off_the_tracking_code_runs_as_before(): void
	{
		// Arrange
		config(['settings.other.cookie_consent_enabled' => '0', 'settings.footer.tracking_code' => self::TRACKING]);
		
		// Act
		$tracking = view('front.common.consent.tracking')->render();
		
		// Assert
		$this->assertStringNotContainsString('<template', $tracking);
		$this->assertStringContainsString('googletagmanager', $tracking);
		$this->assertSame('', trim(view('front.common.consent.banner')->render()));
	}
	
	public function test_without_a_tracking_code_there_is_nothing_to_ask(): void
	{
		// Arrange
		config(['settings.other.cookie_consent_enabled' => '1', 'settings.footer.tracking_code' => '']);
		
		// Act & Assert
		$this->assertSame('', trim(view('front.common.consent.banner')->render()));
		$this->assertSame('', trim(view('front.common.consent.tracking')->render()));
	}
	
	public function test_the_layout_uses_the_consent_partials_instead_of_the_server_side_banner(): void
	{
		// Arrange
		$layout = file_get_contents(resource_path('views/front/layouts/master.blade.php'));
		
		// Assert
		$this->assertStringContainsString("@include('front.common.consent.banner')", $layout);
		$this->assertStringContainsString("@include('front.common.consent.tracking')", $layout);
		$this->assertStringNotContainsString("@include('cookie-consent::index')", $layout);
	}
}
