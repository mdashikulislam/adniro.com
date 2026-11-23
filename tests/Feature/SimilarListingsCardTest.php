<?php

namespace Tests\Feature;

use App\Helpers\Common\ListingGrid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * "Similar Listings" under a listing shows the same card as the homepage and the search page (adniro):
 * with the excerpt and the chat icon, and as many per row as its own setting says (5 when not set).
 */

class SimilarListingsCardTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const EXCERPT = 'A blue armchair, barely used.';

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		config([
			'settings.listing_page.similar_listings'             => '1',
			'settings.listing_page.similar_listings_in_carousel' => '0',
		]);
	}

	public function test_a_similar_listing_card_has_the_excerpt_and_the_chat_icon(): void
	{
		// Arrange
		$seller = $this->stockMember(71);
		$post = $this->stockListing('Grey corner sofa', ['user_id' => $seller->id]);
		$this->stockListing('Blue armchair', ['user_id' => $seller->id, 'description' => self::EXCERPT]);

		// Act
		$section = $this->similarSection($post);

		// Assert
		$this->assertStringContainsString('Blue armchair', $section);
		$this->assertStringContainsString('<p class="listing-card-desc">' . self::EXCERPT . '</p>', $section);
		$this->assertMatchesRegularExpression('~<a href="[^"]+#chat" class="listing-chat"~', $section);
	}

	public function test_a_listing_posted_without_an_account_has_no_chat_icon(): void
	{
		// Arrange
		$post = $this->stockListing('Grey corner sofa');
		$this->stockListing('Blue armchair', ['user_id' => null]);

		// Act
		$section = $this->similarSection($post);

		// Assert
		$this->assertStringContainsString('Blue armchair', $section);
		$this->assertStringNotContainsString('listing-chat', $section);
	}

	public function test_similar_listings_sit_five_per_row_unless_the_setting_says_otherwise(): void
	{
		// Arrange
		$post = $this->stockListing('Grey corner sofa');
		$this->stockListing('Blue armchair');

		// Act
		$default = $this->similarSection($post);
		config(['settings.listing_page.similar_listings_per_row' => '3']);
		$three = $this->similarSection($post, '?again=1');

		// Assert
		$this->assertSame(5, ListingGrid::SIMILAR_DEFAULT_COLUMNS);
		$this->assertStringContainsString(ListingGrid::rowColsClass(5), $default);
		$this->assertStringContainsString(ListingGrid::rowColsClass(3), $three);
	}

	/** The page from its "Similar Listings" heading on */
	private function similarSection($post, string $query = ''): string
	{
		$html = $this->get(urlGen()->post($post) . $query)->assertOk()->getContent();
		$this->assertStringContainsString('listing-section', $html);

		return Str::after($html, 'listing-section');
	}
}
