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
		$this->assertStringContainsString('<p class="sm-cat-meta">' . $children . ' sub-categories</p>', $card);
		$this->assertMatchesRegularExpression('~class="sm-cat-go" aria-label="All in [^"]+"~', $card);
		$this->assertStringNotContainsString('long-list-home', $card, 'nothing is folded away');
	}

	public function test_every_sub_category_has_its_own_icon(): void
	{
		// Arrange: one sub-category with its own icon, one without
		$parent = DB::table('categories')->whereNull('parent_id')->where('active', 1)->orderBy('lft')->first();
		$children = DB::table('categories')->where('parent_id', $parent->id)->where('active', 1)->orderBy('lft')->pluck('id');
		DB::table('categories')->where('id', $children[0])->update(['icon_class' => 'fa-solid fa-truck-pickup']);
		DB::table('categories')->where('id', $children[1])->update(['icon_class' => null]);

		// Act
		$card = $this->categoryCard($this->page(), (int)$parent->id);

		// Assert: an icon before every name; the site's folder icon where a sub-category has none
		$this->assertSame($children->count(), substr_count($card, '<span class="sm-link-icon" aria-hidden="true">'));
		$this->assertStringContainsString('<span class="sm-link-icon" aria-hidden="true"><i class="fa-solid fa-truck-pickup"></i></span>', $card);
		$this->assertStringContainsString('<span class="sm-link-icon" aria-hidden="true"><i class="fa-solid fa-folder"></i></span>', $card);
	}

	public function test_each_card_says_how_wide_it_is_on_a_grid_of_four_three_and_two_columns(): void
	{
		// Act
		preg_match_all('~<article class="sm-cat" id="sitemap-cat-\d+" style="--sm-span-4: (\d); --sm-span-3: (\d); --sm-span-2: (\d)"~', $this->page(), $spans);

		// Assert: every row is full at each width
		$this->assertGreaterThan(5, count($spans[0]));
		foreach ([1 => 4, 2 => 3, 3 => 2] as $group => $columns) {
			$this->assertSame(0, array_sum($spans[$group]) % $columns, "{$columns} columns");
		}
	}

	public function test_cities_are_tiles_like_the_homepages_from_a_to_z(): void
	{
		// Act
		$html = $this->page();
		$section = substr($html, strpos($html, 'id="sitemap-cities"'));
		preg_match_all('~<a href="[^"]+" class="[^"]*\bcity-chip"[^>]*data-sm-item>\s*<span class="city-chip-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>\s*<span class="city-chip-name">([^<]+)</span>~', $section, $cities);

		// Assert: the homepage's own grid and tiles, with its icon tile in the title
		$this->assertSame(['Albany', 'Austin', 'Boston', 'Chicago'], $cities[1]);
		$this->assertStringContainsString('class="row row-cols-lg-4 row-cols-md-3 row-cols-2 g-2 city-list"', $section);
		$this->assertMatchesRegularExpression('~<h2 class="h4 mb-0 fw-lighter section-title location-title" id="sitemapCitiesTitle">\s*<span class="location-title-icon" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>\s*<span class="fw-bold">Cities in United States</span>~', $section);
		$this->assertStringNotContainsString('sm-letter', $html, 'no more rows by letter');
	}

	public function test_the_cities_have_no_more_cities_tile(): void
	{
		// Act
		$html = $this->page();

		// Assert: the page lists its cities itself, so the homepage's last tile and its window are left out
		$this->assertStringNotContainsString('city-chip-more', $html);
		$this->assertStringNotContainsString('id="browseLocations"', $html);
	}

	public function test_the_page_ends_with_a_share_bar_of_round_brand_buttons(): void
	{
		// Arrange
		config(['settings.social_share' => ['facebook' => '1', 'twitter' => '1', 'whatsapp' => '1']]);

		// Act
		$html = $this->page();
		$bar = substr($html, (int)strpos($html, '<section class="sm-share"'));
		$bar = substr($bar, 0, (int)strpos($bar, '</section>'));

		// Assert: a title, then the three networks switched on, with the plain glyphs that suit a round button
		$this->assertStringContainsString('<h2 class="sm-share-title" id="sitemapShareTitle">Share this page</h2>', $bar);
		$this->assertSame(3, preg_match_all('~<a class="[a-z-]+ text-decoration-none" title="Share on [^"]+"~', $bar));
		$this->assertStringContainsString('<i class="fa-brands fa-facebook-f"></i>', $bar);
		$this->assertStringContainsString('<i class="fa-brands fa-x-twitter"></i>', $bar);
		$this->assertStringContainsString('<i class="fa-brands fa-whatsapp"></i>', $bar);
		$this->assertStringNotContainsString('fa-square-', $bar);
	}

	public function test_no_share_bar_when_sharing_is_off(): void
	{
		// Arrange
		config(['settings.social_share' => ['facebook' => '0', 'twitter' => '0']]);

		// Act & Assert
		$this->assertStringNotContainsString('sm-share', $this->page());
	}

	public function test_other_pages_keep_the_square_share_icons(): void
	{
		// Arrange
		config(['settings.social_share' => ['facebook' => '1']]);

		// Act
		$html = view('front.layouts.partials.social.horizontal')->render();

		// Assert
		$this->assertStringContainsString('<i class="fa-brands fa-square-facebook"></i>', $html);
	}

	public function test_the_page_brings_its_own_stylesheet_and_search_script(): void
	{
		// Act
		$html = $this->page();

		// Assert
		$this->assertMatchesRegularExpression('~<link href="[^"]*assets/css/sitemap\.css\?v=\d+" rel="stylesheet">~', $html);
		$this->assertMatchesRegularExpression('~<script src="[^"]*assets/js/app/sitemap\.js\?v=\d+" defer></script>~', $html);
		$this->assertFileExists(public_path('assets/css/sitemap.css'));
		$this->assertFileExists(public_path('assets/js/app/sitemap.js'));
	}

	public function test_the_search_waits_for_its_script_and_the_page_is_complete_without_it(): void
	{
		// Act
		$html = $this->page();

		// Assert: hidden until sitemap.js shows it; every part and link is there, and "nothing matches" is hidden
		$this->assertMatchesRegularExpression('~<form class="sm-search" role="search" hidden\s+data-sm-search~', $html);
		$this->assertStringContainsString('placeholder="Search categories and cities"', $html);
		$this->assertMatchesRegularExpression('~<div class="sm-empty" hidden data-sm-empty>~', $html);
		$this->assertDoesNotMatchRegularExpression('~<(section|article|li)[^>]*\shidden[\s>]~', $html);
	}

	public function test_section_titles_follow_the_homepage_with_one_part_in_bold(): void
	{
		// Act
		$html = $this->page();

		// Assert
		$this->assertStringContainsString('<h2 class="h4 mb-0 fw-lighter section-title" id="sitemapCategoriesTitle">Browse by <span class="fw-bold">Category</span></h2>', $html);
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
