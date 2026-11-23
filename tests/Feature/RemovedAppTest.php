<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The installable app was removed (adniro): pages no longer offer it, and the old service worker on phones that
 * installed it is replaced by one that cleans up and unregisters itself.
 */

class RemovedAppTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
	}

	public function test_pages_no_longer_offer_the_app(): void
	{
		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		foreach (['adnInstall', 'data-adn-install', 'app-install.js', 'app-install.css', 'adn-splash', 'apple-mobile-web-app-capable'] as $gone) {
			$this->assertStringNotContainsString($gone, $html);
		}
	}

	public function test_the_offline_page_is_gone(): void
	{
		$this->get('/offline')->assertNotFound();
	}

	public function test_the_service_worker_only_cleans_up_and_unregisters_itself(): void
	{
		// Act
		$worker = (string)file_get_contents(public_path('sw.js'));

		// Assert
		$this->assertStringContainsString('self.registration.unregister()', $worker);
		$this->assertStringContainsString('caches.delete', $worker);
		$this->assertStringNotContainsString("addEventListener('fetch'", $worker);
		$this->assertStringNotContainsString('cache.put', $worker);
		$this->assertStringNotContainsString('cache.add', $worker);
	}
}
