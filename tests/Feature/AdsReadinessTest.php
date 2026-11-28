<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * Ready for ad networks (adniro): ads.txt from the admin, ad code held back until consent where the
 * cookie banner applies, no ads on login, account, form and empty-result pages, and those pages kept
 * out of Google's index.
 */
class AdsReadinessTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const AD_CODE = '<script>window.adTest = 1;</script><div class="ad-test-unit">AD</div>';

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		config([
			'settings.listing_form.guest_can_submit_listings' => '1',
			'settings.listing_form.publication_form_type'     => 'multi-steps-form',
			'settings.footer.tracking_code'                   => '',
			'settings.other.cookie_consent_enabled'           => '0',
		]);
	}

	private function activateAds(): void
	{
		DB::table('advertising')->update([
			'active'               => 1,
			'is_responsive'        => 1,
			'tracking_code_large'  => self::AD_CODE,
			'tracking_code_medium' => self::AD_CODE,
			'tracking_code_small'  => self::AD_CODE,
		]);
	}

	/* ---------- ads.txt ---------- */

	public function test_ads_txt_is_not_found_while_the_admin_field_is_empty(): void
	{
		// Arrange
		config(['settings.seo.ads_txt' => '']);

		// Act & Assert
		$this->get('/ads.txt')->assertNotFound();
	}

	public function test_ads_txt_serves_the_admin_lines_as_plain_text(): void
	{
		// Arrange
		$lines = "google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0\r\nadsterra.com, 12345, DIRECT";
		config(['settings.seo.ads_txt' => $lines]);

		// Act
		$response = $this->get('/ads.txt');

		// Assert: one line per entry, Unix line ends, plain text
		$response->assertOk();
		$this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
		$this->assertSame("google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0\nadsterra.com, 12345, DIRECT\n", $response->getContent());
	}

	public function test_the_admin_has_an_ads_txt_field(): void
	{
		// Act
		$fields = collect(\App\Models\Setting\SeoSetting::getFields('public'))->pluck('name');

		// Assert
		$this->assertContains('ads_txt', $fields);
	}

	/* ---------- consent ---------- */

	public function test_without_the_cookie_banner_the_ad_code_runs_as_it_is(): void
	{
		// Arrange
		$this->activateAds();

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString(self::AD_CODE, $html);
		$this->assertStringNotContainsString('data-consent-ad', $html);
	}

	public function test_with_the_cookie_banner_the_ad_code_waits_for_consent(): void
	{
		// Arrange: banner on, no tracking code; active ads alone are a reason to ask
		$this->activateAds();
		config(['settings.other.cookie_consent_enabled' => '1']);

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert: the code sits in an inert template in its slot, and the banner and its script are there
		$this->assertMatchesRegularExpression('~<template data-consent-ad>\s*' . preg_quote(self::AD_CODE, '~') . '\s*</template>~', $html);
		$this->assertStringNotContainsString('<script>window.adTest = 1;</script><div class="ad-test-unit">AD</div></div>', $html);
		$this->assertStringContainsString('id="cookieConsent"', $html);
		$this->assertStringContainsString('assets/js/app/consent.js', $html);
	}

	public function test_the_banner_names_the_regions_that_must_consent(): void
	{
		// Arrange
		$this->activateAds();
		config(['settings.other.cookie_consent_enabled' => '1']);

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert: the UK and the EU are asked; the browser decides from the visitor's country
		$this->assertMatchesRegularExpression('~data-consent-regions="[^"]*\bGB\b[^"]*\bDE\b[^"]*"|data-consent-regions="[^"]*\bDE\b[^"]*\bGB\b[^"]*"~', $html);
		$this->assertStringNotContainsString(',IN,', $html);
	}

	public function test_switching_an_ad_on_in_the_admin_turns_the_banner_on_at_once(): void
	{
		// Arrange: banner allowed, no ad yet (the answer is cached)
		config(['settings.other.cookie_consent_enabled' => '1']);
		$this->assertFalse(\App\Helpers\Common\CookieConsent::required());

		// Act: an admin saves an active ad
		$ad = \App\Models\Advertising::withoutGlobalScopes()->where('slug', 'top')->firstOrFail();
		$ad->active = 1;
		$ad->tracking_code_large = self::AD_CODE;
		$ad->save();

		// Assert: the cached answer was dropped on save
		$this->assertTrue(\App\Helpers\Common\CookieConsent::required());
	}

	public function test_with_neither_ads_nor_tracking_code_there_is_nothing_to_ask(): void
	{
		// Arrange
		config(['settings.other.cookie_consent_enabled' => '1']);

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringNotContainsString('id="cookieConsent"', $html);
	}

	/* ---------- where ads may appear ---------- */

	public function test_the_home_page_carries_the_ads(): void
	{
		// Arrange
		$this->activateAds();

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('ad-test-unit', $html);
	}

	public function test_the_listing_form_and_the_contact_page_carry_no_ads(): void
	{
		// Arrange
		$this->activateAds();

		// Act & Assert
		foreach (['/posts/create', '/contact'] as $url) {
			$html = $this->get($url)->assertOk()->getContent();
			$this->assertStringNotContainsString('ad-test-unit', $html, $url);
		}
	}

	public function test_a_search_without_results_carries_no_ads(): void
	{
		// Arrange
		$this->activateAds();

		// Act
		$html = $this->get('/us/search?q=' . urlencode('zzqx nothing matches this'))->assertOk()->getContent();

		// Assert
		$this->assertStringNotContainsString('ad-test-unit', $html);
	}

	/* ---------- index ---------- */

	public function test_login_register_and_the_listing_form_are_kept_out_of_the_index(): void
	{
		// Act & Assert
		foreach (['/auth/login', '/auth/register', '/posts/create'] as $url) {
			$html = $this->get($url)->assertOk()->getContent();
			$this->assertMatchesRegularExpression('~<meta name="robots" content="noindex[^"]*">~', $html, $url);
		}
	}

	public function test_the_home_page_stays_indexable(): void
	{
		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertDoesNotMatchRegularExpression('~<meta name="robots" content="noindex~', $html);
	}
}
