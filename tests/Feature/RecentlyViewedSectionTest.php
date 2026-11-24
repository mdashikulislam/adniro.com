<?php

namespace Tests\Feature;

use App\Models\Section\Home\RecentlyViewedSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * "Recently viewed" is a homepage section like the others (adniro): it is listed in Admin > Homepage >
 * Sections, ordered with them, switched on or off, and has its own Max items and Items per row.
 */

class RecentlyViewedSectionTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const SECTION_MARKER = 'id="recentlyViewed"';
	private const CARD = '<article class="listing-card';

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		$this->migration()->up();
	}

	public function test_the_migration_adds_the_section_just_before_the_latest_listings(): void
	{
		// Act
		$order = DB::table('sections')->where('belongs_to', 'home')->orderBy('lft')->pluck('name')->all();

		// Assert
		$latest = array_search('latest_listings', $order, true);
		$this->assertSame('recently_viewed', $order[$latest - 1]);
		$this->assertTreeIsContiguous();

		$section = DB::table('sections')->where('name', 'recently_viewed')->first();
		$this->assertSame(1, (int)$section->active);
	}

	public function test_without_saved_options_it_uses_the_defaults_and_the_usual_spacing(): void
	{
		// Act
		$values = RecentlyViewedSection::getFieldValues(null, null);

		// Assert: as it looked before, with the same bottom margin as the other sections (a margin row the form can save)
		$this->assertSame('12', $values['max_items']);
		$this->assertSame('4', $values['items_per_row']);
		$this->assertSame('1', $values['items_in_carousel']);
		$this->assertSame('1', $values['show_clear_btn']);
		$this->assertSame([['side' => 'mb', 'breakpoint' => null, 'size' => '4']], $values['margins']);
		$this->assertStringContainsString('mb-4', $values['css_classes']);
	}

	public function test_the_default_section_is_a_sideways_row_with_arrows_and_a_clear_button(): void
	{
		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('class="recently-viewed-row"', $html);
		$this->assertStringContainsString('id="recentlyViewedNext"', $html);
		$this->assertStringContainsString('id="recentlyViewedClear"', $html);
		$this->assertMatchesRegularExpression('/id="recentlyViewed"[^>]*data-max="12"/s', $html);
	}

	public function test_without_the_carousel_and_the_clear_button_it_is_a_plain_grid(): void
	{
		// Arrange
		DB::table('sections')->where('name', 'recently_viewed')->update([
			'field_values' => json_encode(['items_in_carousel' => '0', 'show_clear_btn' => '0']),
		]);

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert: the cards wrap onto more rows, no arrows, no clear button
		$this->assertStringContainsString('class="recently-viewed-grid"', $html);
		$this->assertStringNotContainsString('class="recently-viewed-row"', $html);
		$this->assertStringNotContainsString('id="recentlyViewedNext"', $html);
		$this->assertStringNotContainsString('id="recentlyViewedClear"', $html);
	}

	public function test_rolling_back_removes_the_section_and_keeps_the_order(): void
	{
		// Act
		$this->migration()->down();

		// Assert
		$this->assertFalse(DB::table('sections')->where('name', 'recently_viewed')->exists());
		$this->assertTreeIsContiguous();
	}

	public function test_the_homepage_shows_the_section_where_it_is_ordered_with_its_options(): void
	{
		// Arrange: moved after the latest listings, 5 per row, at most 8
		$this->stockListing('Grey corner sofa');
		$latestLft = (int)DB::table('sections')->where('name', 'latest_listings')->value('lft');
		DB::table('sections')->where('name', 'recently_viewed')->update([
			'lft' => $latestLft + 1, 'rgt' => $latestLft + 1,
			'field_values' => json_encode(['max_items' => '8', 'items_per_row' => '5']),
		]);

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert: once, after the latest listings, with its options for the browser
		$this->assertSame(1, substr_count($html, self::SECTION_MARKER));
		$this->assertGreaterThan(strpos($html, 'listing-section'), strpos($html, self::SECTION_MARKER));
		$this->assertMatchesRegularExpression('/id="recentlyViewed"[^>]*data-max="8"/s', $html);
		$this->assertMatchesRegularExpression('/id="recentlyViewed"[^>]*data-cols="5"/s', $html);
	}

	public function test_a_switched_off_section_is_not_on_the_homepage(): void
	{
		// Arrange
		DB::table('sections')->where('name', 'recently_viewed')->update(['active' => 0]);

		// Act & Assert
		$this->assertStringNotContainsString(self::SECTION_MARKER, $this->get('/')->assertOk()->getContent());
	}

	public function test_the_cards_follow_the_items_per_row_and_max_items_sent_by_the_section(): void
	{
		// Arrange
		$ids = collect(range(1, 4))->map(fn ($n) => $this->stockListing('Grey corner sofa ' . $n)->id)->implode(',');

		// Act
		$json = $this->getJson('/listings/recently-viewed?ids=' . $ids . '&cols=6&max=2')->assertOk()->json();

		// Assert
		$this->assertSame(2, $json['count']);
		$this->assertSame(2, substr_count($json['html'], self::CARD));
		$this->assertStringContainsString('row-cols-xl-6', $json['html']);
		// All 4 are still online, so the browser keeps its whole history
		$this->assertCount(4, $json['ids']);
	}

	public function test_the_cards_date_reads_like_on_the_other_listing_cards(): void
	{
		// Arrange: "2 days ago" on the listing cards (Listings List > Date from now)
		config(['settings.listings_list.date_from_now' => '1']);
		$post = $this->stockListing('Grey corner sofa', ['created_at' => now()->subDays(2)]);

		// Act
		$html = (string)$this->getJson('listings/recently-viewed?ids=' . $post->id)->assertOk()->json('html');
		config(['settings.listings_list.date_from_now' => '0']);
		$fullDate = (string)$this->getJson('listings/recently-viewed?again=1&ids=' . $post->id)->assertOk()->json('html');

		// Assert: the elapsed time, and the full date only when the setting is off
		$this->assertMatchesRegularExpression('~listing-card-date">.*?>2 days ago</span>~s', $html);
		$this->assertDoesNotMatchRegularExpression('~listing-card-date">.*?days ago~s', $fullDate);
	}

	public function test_out_of_range_options_are_kept_within_the_limits(): void
	{
		// Arrange
		$ids = collect(range(1, 3))->map(fn ($n) => $this->stockListing('Grey corner sofa ' . $n)->id)->implode(',');

		// Act
		$json = $this->getJson('/listings/recently-viewed?ids=' . $ids . '&cols=99&max=0')->assertOk()->json();

		// Assert: at most 6 per row; max 0 means the default (12)
		$this->assertSame(3, $json['count']);
		$this->assertStringContainsString('row-cols-xl-6', $json['html']);
	}

	public function test_the_admin_has_the_same_kind_of_options_as_the_latest_listings(): void
	{
		// Act
		$fields = collect(RecentlyViewedSection::getFields('public'))->keyBy('name');

		// Assert
		$this->assertSame('number', $fields['max_items']['type']);
		$this->assertSame(12, $fields['max_items']['attributes']['max']);
		$this->assertArrayHasKey('items_per_row', $fields->all());
		$this->assertSame('checkbox_switch', $fields['items_in_carousel']['type']);
		$this->assertSame('checkbox_switch', $fields['show_clear_btn']['type']);
		$this->assertArrayHasKey('active', $fields->all());
		$this->assertArrayHasKey('hide_on_mobile', $fields->all());
	}

	private function migration(): object
	{
		return require database_path('migrations/2026_11_22_000200_add_recently_viewed_homepage_section.php');
	}

	private function assertTreeIsContiguous(): void
	{
		$bounds = DB::table('sections')->orderBy('lft')->get(['lft', 'rgt'])
			->flatMap(fn ($row) => [(int)$row->lft, (int)$row->rgt])->all();
		$this->assertSame(range(min($bounds), min($bounds) + count($bounds) - 1), $bounds);
	}
}
