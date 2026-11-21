<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * Smoke tests for the main public pages (adniro, phase 7): they must still render after a
 * LaraClassifier upgrade or a dependency update. The stock seeders give the countries,
 * languages, settings, categories and pages a real install has.
 */

class PublicPagesSmokeTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
	}

	public function test_the_home_page_renders_with_its_structured_data(): void
	{
		// Act
		$response = $this->get('/');

		// Assert
		$response->assertOk()->assertSee('<h1', false)->assertSee('"@type":"Organization"', false);
	}

	public function test_a_listing_page_renders(): void
	{
		// Arrange
		$post = $this->stockListing('Grey corner sofa');

		// Act & Assert
		$this->get(urlGen()->post($post))
			->assertOk()
			->assertSee('Grey corner sofa')
			->assertSee('application/ld+json', false);
	}

	public function test_search_finds_a_listing(): void
	{
		// Arrange
		$this->stockListing('Grey corner sofa');

		// Act & Assert
		$this->get('/us/search?q=sofa')->assertOk()->assertSee('Grey corner sofa');
	}

	public function test_a_page_and_the_listing_form_render(): void
	{
		// Arrange: guests may post, as on the live site
		config(['settings.listing_form.guest_can_submit_listings' => '1']);
		$page = DB::table('pages')->where('active', 1)->value('slug');

		// Act & Assert
		$this->get('/page/' . $page)->assertOk();
		$this->get('/posts/create')->assertOk()->assertSee('payableForm', false);
	}

	public function test_a_missing_page_answers_404_and_the_sitemap_is_xml(): void
	{
		// Act & Assert
		$this->get('/no-such-page-' . uniqid())->assertNotFound();
		$this->get('/us/sitemaps.xml')->assertOk()->assertSee('<sitemapindex', false);
	}
}
