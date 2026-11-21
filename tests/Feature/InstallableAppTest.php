<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The installable app (adniro): an offline page and a service worker that caches nothing else, the
 * install card and the "Add to Home Screen" guide (hidden until the phone script shows them), the
 * always-available install entries, and the iPhone app tags.
 */

class InstallableAppTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const MEMBER_ID = 61;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
	}

	public function test_the_offline_page_names_the_site_offers_a_retry_and_is_not_indexed(): void
	{
		// Act
		$response = $this->get('/offline');

		// Assert
		$response->assertOk()
			->assertHeader('X-Robots-Tag', 'noindex')
			->assertSee('Adniro')
			->assertSee(trans('app_install.offline_title'))
			->assertSee(trans('app_install.offline_retry'));
		// Shown without a network: everything it needs is in the page
		$this->assertDoesNotMatchRegularExpression('/<link[^>]+rel="stylesheet"|<script[^>]+src=/', $response->getContent());
	}

	public function test_the_service_worker_only_keeps_the_offline_page_and_leaves_private_pages_alone(): void
	{
		// Act
		$worker = (string)file_get_contents(public_path('sw.js'));

		// Assert
		$this->assertStringContainsString("const OFFLINE_URL = '/offline';", $worker);
		$this->assertStringContainsString("request.mode !== 'navigate'", $worker);
		foreach (['admin', 'account', 'auth', 'posts\/create', 'payment'] as $private) {
			$this->assertStringContainsString($private, $worker);
		}
		$this->assertStringNotContainsString('cache.put', $worker);
	}

	public function test_pages_carry_the_install_card_and_guide_hidden_with_their_settings(): void
	{
		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert: hidden until the phone script decides, with the service worker and texts it needs
		$this->assertMatchesRegularExpression('/<section[^>]+id="adnInstall"[^>]*hidden/', $html);
		$this->assertMatchesRegularExpression('/<div[^>]+id="adnInstallSheet"[^>]*hidden/', $html);
		$this->assertMatchesRegularExpression('/data-sw="' . preg_quote(url('sw.js'), '/') . '\?v=\d+"/', $html);
		$this->assertStringContainsString(e(trans('app_install.title', ['name' => 'Adniro'])), $html);
		$this->assertStringContainsString(e(trans('app_install.safari_2')), $html);
		$this->assertStringContainsString('assets/js/app/app-install.js', $html);
	}

	public function test_the_footer_and_the_phone_account_menu_offer_the_app_hidden_until_the_script_shows_them(): void
	{
		// Arrange
		$member = $this->stockMember(self::MEMBER_ID);

		// Act
		$html = $this->actingAs($member)->get('/')->assertOk()->getContent();

		// Assert
		$this->assertMatchesRegularExpression('/<section[^>]+class="adn-install-foot[^"]*"[^>]*hidden/', $html);
		$this->assertMatchesRegularExpression('/<button[^>]+class="dropdown-item adn-account-menu-item"[^>]+data-adn-install[^>]*hidden/', $html);
	}

	public function test_the_phone_header_menu_offers_the_app_hidden_until_the_script_shows_it(): void
	{
		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert: inside the header menu, phones only, with the same install action and label as the other entries
		$menu = substr($html, strpos($html, 'id="navbarNav"'), strpos($html, '</nav>') - strpos($html, 'id="navbarNav"'));
		$this->assertMatchesRegularExpression('/<li class="nav-item adn-nav-install d-md-none" data-adn-install-entry hidden>\s*<button type="button" class="btn btn-outline-primary" data-adn-install>/', $menu);
		$this->assertStringContainsString('<span data-adn-install-label>' . e(trans('app_install.install')) . '</span>', $menu);
	}

	public function test_iphones_get_the_app_tags(): void
	{
		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('<meta name="apple-mobile-web-app-capable" content="yes">', $html);
		$this->assertStringContainsString('<meta name="mobile-web-app-capable" content="yes">', $html);
		$this->assertStringContainsString('<meta name="apple-mobile-web-app-title" content="Adniro">', $html);
	}
}
