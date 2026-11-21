<?php

namespace Tests\Feature\ListingForm;

use App\Services\ListingForm\PriceHint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Ai\ListingAssistantFixtures;
use Tests\TestCase;

/*
 * The price hint under the listing form's Price (adniro): statistics from live listings, no AI.
 * The fixtures give categories Computers (4) > Laptops (3), Phones (1) > Mobile phones (2), and
 * the cities Springfield (600) and Chicago (700).
 */
class PriceHintTest extends TestCase
{
	use RefreshDatabase, ListingAssistantFixtures;

	private const URL = '/posts/price-hint';
	private const LAPTOPS = 3;
	private const SPRINGFIELD = 600;
	private const CHICAGO = 700;

	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpListingAssistant();
		config([
			'country.name'                              => 'United States',
			'currency'                                  => ['code' => 'USD', 'symbol' => '$', 'in_left' => 1, 'decimal_places' => 0, 'decimal_separator' => '.', 'thousand_separator' => ','],
			'settings.listing_form.price_hint_enabled'  => '1',
			'settings.listing_form.price_hint_min_sample' => 8,
			'settings.listing_form.listings_review_activation' => '0',
		]);
		Cache::flush();
	}

	/**
	 * @param int[] $prices
	 */
	private function listings(array $prices, int $categoryId = self::LAPTOPS, int $cityId = self::SPRINGFIELD, array $overrides = []): array
	{
		$ids = [];
		foreach ($prices as $price) {
			$ids[] = DB::table('posts')->insertGetId(array_merge([
				'country_code' => 'US', 'category_id' => $categoryId, 'city_id' => $cityId, 'post_type_id' => 1,
				'title' => 'Laptop', 'description' => 'A laptop.', 'price' => $price, 'currency_code' => 'USD',
				'contact_name' => 'Sam', 'email' => 'sam@example.test', 'auth_field' => 'email',
				'email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now(),
				'moderation_status' => 'approved', 'created_at' => now()->subDays(3), 'updated_at' => now(),
			], $overrides));
		}

		return $ids;
	}

	public function test_listings_in_the_same_city_give_the_usual_price_range(): void
	{
		// Arrange
		$this->listings(range(400, 900, 50));

		// Act
		$response = $this->getJson(self::URL . '?category_id=3&city_id=600');

		// Assert
		$response->assertOk()->assertJson(['ok' => true, 'hint' => ['low' => 530, 'high' => 780, 'count' => 11, 'scope' => 'city']]);
		$this->assertSame('Similar listings in Springfield are priced $530 – $780 (11 listings).', $response->json('hint.text'));
	}

	public function test_a_quiet_city_uses_the_whole_country(): void
	{
		// Arrange
		$this->listings([500, 520]);
		$this->listings(range(400, 900, 50), self::LAPTOPS, self::CHICAGO);

		// Act
		$hint = app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD);

		// Assert
		$this->assertSame('country', $hint['scope']);
		$this->assertSame(13, $hint['count']);
		$this->assertStringContainsString('in United States', $hint['text']);
	}

	public function test_a_quiet_category_uses_its_parent(): void
	{
		// Arrange: Laptops has 3 listings; Computers (its parent) has more under another child
		DB::table('categories')->insert(['id' => 5, 'parent_id' => 4, 'name' => json_encode(['en' => 'Desktops']), 'slug' => 'desktops', 'lft' => 9, 'rgt' => 10, 'depth' => 1, 'type' => 'classified', 'active' => 1]);
		$this->listings([700, 750, 800]);
		$this->listings(range(400, 900, 50), 5);

		// Act
		$hint = app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD);

		// Assert
		$this->assertSame('parent', $hint['scope']);
		$this->assertSame(14, $hint['count']);
		$this->assertStringContainsString('Computers', $hint['text']);
	}

	public function test_too_few_listings_give_no_hint(): void
	{
		// Arrange
		$this->listings([400, 500, 600, 700, 800, 900, 1000]);

		// Act
		$response = $this->getJson(self::URL . '?category_id=3&city_id=600');

		// Assert
		$response->assertOk()->assertJson(['ok' => false])->assertJsonMissingPath('hint');
	}

	public function test_only_live_priced_listings_in_the_site_currency_from_the_last_year_count(): void
	{
		// Arrange: 8 good ones, and one of each kind that must not count, all priced 9999
		$this->listings(range(400, 750, 50));
		$this->listings([9999], self::LAPTOPS, self::SPRINGFIELD, ['moderation_status' => 'needs_review']);
		$this->listings([9999], self::LAPTOPS, self::SPRINGFIELD, ['archived_at' => now()]);
		$this->listings([9999], self::LAPTOPS, self::SPRINGFIELD, ['email_verified_at' => null]);
		$this->listings([9999], self::LAPTOPS, self::SPRINGFIELD, ['currency_code' => 'EUR']);
		$this->listings([9999], self::LAPTOPS, self::SPRINGFIELD, ['created_at' => now()->subMonths(13)]);
		$this->listings([9999], self::LAPTOPS, self::SPRINGFIELD, ['deleted_at' => now()]);
		$this->listings([9999], self::LAPTOPS, self::SPRINGFIELD, ['country_code' => 'CA']);
		$this->listings([0, 0]);

		// Act
		$hint = app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD);

		// Assert
		$this->assertSame(8, $hint['count']);
		$this->assertLessThan(800, $hint['high']);
	}

	public function test_the_owner_editing_a_listing_is_not_compared_with_it(): void
	{
		// Arrange: exactly 8 listings, one of them the owner's
		$owner = $this->user(7);
		$ids = $this->listings(range(400, 750, 50));
		DB::table('posts')->where('id', $ids[0])->update(['user_id' => 7]);

		// Act
		$response = $this->actingAs($owner)->getJson(self::URL . '?category_id=3&city_id=600&post_id=' . $ids[0]);

		// Assert: 7 left, too few
		$response->assertOk()->assertJson(['ok' => false]);
	}

	public function test_someone_elses_listing_id_changes_nothing(): void
	{
		// Arrange
		$ids = $this->listings(range(400, 750, 50));

		// Act
		$response = $this->actingAs($this->user(7))->getJson(self::URL . '?category_id=3&city_id=600&post_id=' . $ids[0]);

		// Assert
		$response->assertOk()->assertJsonPath('hint.count', 8);
	}

	public function test_the_hint_is_cached(): void
	{
		// Arrange
		$this->listings(range(400, 900, 50));
		app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD);
		$this->listings([5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000, 5000]);

		// Act
		$hint = app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD);

		// Assert
		$this->assertSame(11, $hint['count']);
	}

	public function test_nothing_is_shown_while_the_admin_has_it_off(): void
	{
		// Arrange
		config(['settings.listing_form.price_hint_enabled' => '0']);
		$this->listings(range(400, 900, 50));

		// Act
		$response = $this->getJson(self::URL . '?category_id=3&city_id=600');

		// Assert
		$response->assertOk()->assertJson(['ok' => false, 'reason' => 'off']);
	}

	public function test_a_category_is_required(): void
	{
		// Act
		$response = $this->getJson(self::URL . '?city_id=600');

		// Assert
		$response->assertStatus(422);
	}

	public function test_the_text_is_plain_and_in_the_site_currency_whatever_the_visitor_chose(): void
	{
		// Arrange: superscript decimals on, and a visitor who picked euros for display
		config([
			'settings.other.decimals_superscript' => '1',
			'selectedCurrency'                     => ['code' => 'EUR', 'symbol' => '€', 'in_left' => 0, 'rate' => 0.5],
		]);
		$this->listings(range(400, 900, 50));
		
		// Act
		$text = app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD)['text'];
		
		// Assert
		$this->assertStringContainsString('$530 – $780', $text);
		$this->assertStringNotContainsString('<', $text);
		$this->assertStringNotContainsString('€', $text);
	}
	
	public function test_a_changed_minimum_applies_at_once(): void
	{
		// Arrange: 5 listings, no hint with the default minimum of 8
		$this->listings([400, 450, 500, 550, 600]);
		$this->assertNull(app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD));
		config(['settings.listing_form.price_hint_min_sample' => 5]);
		
		// Act
		$hint = app(PriceHint::class)->for(self::LAPTOPS, self::SPRINGFIELD);
		
		// Assert
		$this->assertSame(5, $hint['count']);
	}
	
	public function test_made_up_categories_and_cities_from_other_countries_are_refused(): void
	{
		// Arrange
		DB::table('cities')->insert(['id' => 900, 'country_code' => 'CA', 'name' => json_encode(['en' => 'Toronto']), 'latitude' => 43.7, 'longitude' => -79.4, 'population' => 2700000, 'active' => 1]);
		
		// Act and assert
		$this->getJson(self::URL . '?category_id=99999')->assertStatus(422);
		$this->getJson(self::URL . '?category_id=3&city_id=900')->assertStatus(422);
	}
	
	public function test_the_hint_needs_no_ai_call(): void
	{
		// Arrange
		$this->listings(range(400, 900, 50));

		// Act
		$this->getJson(self::URL . '?category_id=3&city_id=600')->assertOk();

		// Assert
		$this->assertSame(0, $this->ai->calls);
	}
}
