<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The sitemap page people read (adniro): a head with the page's figures and jump links, every category as a
 * card with its icon and all of its sub-categories, and the cities from A to Z.
 */

class SitemapPageTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const URL = '/us/sitemap';

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		foreach (['Boston' => 650000, 'Austin' => 950000, 'Albany' => 99000, 'Chicago' => 2700000] as $name => $population) {
			DB::table('cities')->insert([
				'country_code' => 'US', 'name' => json_encode(['en' => $name]), 'latitude' => 40, 'longitude' => -80,
				'population' => $population, 'active' => 1, 'created_at' => now(), 'updated_at' => now(),
			]);
		}
	}

	public function test_the_head_names_the_page_and_counts_what_is_on_it(): void
	{
		// Act
		$html = $this->page();
		$categories = substr_count($html, '<article class="sm-cat"');

		// Assert: the figures are those of the page itself
		$this->assertGreaterThan(5, $categories);
		$this->assertMatchesRegularExpression('~<h1 class="sm-hero-title"[^>]*>\s*Sitemap\s*</h1>~', $html);
		$this->assertMatchesRegularExpression('~<span class="sm-stat-value">' . $categories . '</span>\s*<span class="sm-stat-label">Categories</span>~', $html);
		$this->assertMatchesRegularExpression('~<span class="sm-stat-value">4</span>\s*<span class="sm-stat-label">Cities</span>~', $html);
		$this->assertStringContainsString('href="#sitemap-categories"', $html);
		$this->assertStringContainsString('href="#sitemap-cities"', $html);
		$this->assertStringContainsString('id="sitemap-categories"', $html);
		$this->assertStringContainsString('id="sitemap-cities"', $html);
	}

	public function test_every_category_is_a_card_with_its_icon_and_all_its_sub_categories(): void
	{
		// Arrange
		$parent = DB::table('categories')->whereNull('parent_id')->where('active', 1)->orderBy('lft')->first();
		$children = DB::table('categories')->where('parent_id', $parent->id)->where('active', 1)->count();
		$this->assertGreaterThan(5, $children, 'a category the old page cut short behind "View more"');

		// Act
		$card = $this->categoryCard($this->page(), (int)$parent->id);

		// Assert
		$this->assertMatchesRegularExpression('~<span class="sm-cat-icon" aria-hidden="true"><i class="[^"]+"></i></span>~', $card);
		$this->assertSame($children, substr_count($card, '<li class="sm-cat-item">'));
		$this->assertStringContainsString('<span class="sm-cat-count">' . $children . '</span>', $card);
		$this->assertStringNotContainsString('long-list-home', $card, 'nothing is folded away');
	}

	public function test_cities_are_listed_from_a_to_z_under_their_letter(): void
	{
		// Act
		$html = $this->page();
		preg_match_all('~<h3 class="sm-letter"[^>]*>\s*(\S+)\s*</h3>~', $html, $letters);
		preg_match_all('~<a href="[^"]+" class="sm-city"[^>]*>.*?<span class="sm-city-name">([^<]+)</span>~s', $html, $cities);

		// Assert
		$this->assertSame(['A', 'B', 'C'], $letters[1]);
		$this->assertSame(['Albany', 'Austin', 'Boston', 'Chicago'], $cities[1]);
	}

	public function test_the_page_brings_its_own_stylesheet(): void
	{
		$this->assertMatchesRegularExpression('~<link href="[^"]*assets/css/sitemap\.css\?v=\d+" rel="stylesheet">~', $this->page());
		$this->assertFileExists(public_path('assets/css/sitemap.css'));
	}

	private function page(): string
	{
		return $this->get(self::URL)->assertOk()->getContent();
	}

	private function categoryCard(string $html, int $id): string
	{
		$start = strpos($html, 'id="sitemap-cat-' . $id . '"');
		$this->assertNotFalse($start);

		return substr($html, $start, strpos($html, '</article>', $start) - $start);
	}
}
