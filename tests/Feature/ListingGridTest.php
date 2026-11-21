<?php

namespace Tests\Feature;

use App\Helpers\Common\ListingGrid;
use App\Models\Section\Home\LatestListingsSection;
use App\Models\Section\Home\PremiumListingsSection;
use App\Models\Setting\ListingsListSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * Listings per row and rows per page, set from the admin panel (adniro): the homepage's Latest and
 * Premium listings sections each have their own "Items per row", the search page uses Listings List >
 * "Grid View Columns" and "Rows per page".
 */

class ListingGridTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const CARD = '<article class="listing-card';

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
	}

	public function test_columns_stay_between_two_and_six_and_follow_the_search_setting_by_default(): void
	{
		// Arrange
		config(['settings.listings_list.grid_view_cols' => '5']);

		// Act & Assert
		$this->assertSame(5, ListingGrid::columns());
		$this->assertSame(6, ListingGrid::columns(6));
		$this->assertSame(2, ListingGrid::columns(1));
		$this->assertSame(6, ListingGrid::columns(9));
		$this->assertSame(5, ListingGrid::columns(''));
	}

	public function test_wide_screens_get_the_chosen_columns_and_smaller_screens_fewer(): void
	{
		$this->assertSame('row-cols-xl-6 row-cols-lg-4 row-cols-md-3 row-cols-2', ListingGrid::rowColsClass(6));
		$this->assertSame('row-cols-xl-3 row-cols-lg-3 row-cols-md-3 row-cols-2', ListingGrid::rowColsClass(3));
		$this->assertSame('row-cols-xl-2 row-cols-lg-2 row-cols-md-2 row-cols-2', ListingGrid::rowColsClass(2));
	}

	public function test_rows_per_page_times_columns_gives_full_rows_and_empty_keeps_the_pagination_setting(): void
	{
		// Arrange
		config(['settings.listings_list.grid_view_cols' => '4', 'settings.listings_list.display_mode' => 'grid-view']);

		// Act & Assert: grid view fills every row
		config(['settings.listings_list.rows_per_page' => '5']);
		$this->assertSame(20, ListingGrid::searchPerPage());

		// One listing per row in the list and compact views
		config(['settings.listings_list.display_mode' => 'list-view']);
		$this->assertSame(5, ListingGrid::searchPerPage());

		// Not set: the Pagination setting decides
		config(['settings.listings_list.rows_per_page' => '']);
		$this->assertNull(ListingGrid::searchPerPage());
	}

	public function test_the_search_page_shows_the_chosen_columns_and_rows(): void
	{
		// Arrange: 3 per row, 2 rows, 8 listings
		config([
			'settings.listings_list.display_mode'   => 'grid-view',
			'settings.listings_list.grid_view_cols' => '3',
			'settings.listings_list.rows_per_page'  => '2',
		]);
		foreach (range(1, 8) as $n) {
			$this->stockListing('Grey corner sofa ' . $n);
		}

		// Act
		$html = $this->get('/us/search?q=sofa')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('row-cols-xl-3 row-cols-lg-3', $html);
		$this->assertSame(6, substr_count($html, self::CARD));
	}

	public function test_the_homepage_latest_listings_use_their_own_items_per_row(): void
	{
		// Arrange
		config(['settings.listings_list.grid_view_cols' => '3']);
		$this->setSectionOption('latest_listings', ['items_per_row' => '6', 'items_in_carousel' => '0']);
		$this->stockListing('Grey corner sofa');

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('row-cols-xl-6 row-cols-lg-4', $html);
	}

	public function test_the_homepage_keeps_four_per_row_when_the_option_is_not_set(): void
	{
		// Arrange: the search page is at 3 per row
		config(['settings.listings_list.grid_view_cols' => '3']);
		$this->setSectionOption('latest_listings', ['items_in_carousel' => '0']);
		$this->stockListing('Grey corner sofa');

		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('row-cols-xl-4 row-cols-lg-4', $html);
	}

	public function test_the_admin_has_the_options(): void
	{
		// Act
		$sectionFields = collect(LatestListingsSection::getFields('public'))->keyBy('name');
		$premiumFields = collect(PremiumListingsSection::getFields('public'))->keyBy('name');
		$listFields = collect(ListingsListSetting::getFields('public'))->keyBy('name');

		// Assert
		$this->assertSame(['2', '3', '4', '5', '6'], array_map('strval', array_keys($sectionFields['items_per_row']['options'])));
		$this->assertArrayHasKey('items_per_row', $premiumFields->all());
		$this->assertSame(['2', '3', '4', '5', '6'], array_map('strval', array_keys($listFields['grid_view_cols']['options'])));
		$this->assertSame('number', $listFields['rows_per_page']['type']);
	}

	public function test_the_migration_keeps_the_search_page_at_three_per_row(): void
	{
		// Arrange: the search page always showed 3 per row, whatever this setting said
		$this->setListingsList(['grid_view_cols' => '4', 'display_mode' => 'grid-view']);
		$migration = require database_path('migrations/2026_11_22_000100_keep_search_page_at_three_listings_per_row.php');

		// Act
		$migration->up();

		// Assert
		$values = json_decode((string)DB::table('settings')->where('name', 'listings_list')->value('field_values'), true);
		$this->assertSame('3', $values['grid_view_cols']);
		$this->assertSame('grid-view', $values['display_mode']);
	}

	private function setSectionOption(string $name, array $values): void
	{
		$current = (array)json_decode((string)DB::table('sections')->where('name', $name)->value('field_values'), true);
		DB::table('sections')->where('name', $name)->update(['field_values' => json_encode(array_merge($current, $values)), 'active' => 1]);
	}

	private function setListingsList(array $values): void
	{
		$current = (array)json_decode((string)DB::table('settings')->where('name', 'listings_list')->value('field_values'), true);
		DB::table('settings')->where('name', 'listings_list')->update(['field_values' => json_encode(array_merge($current, $values))]);
	}
}
