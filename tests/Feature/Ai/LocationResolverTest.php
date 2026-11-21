<?php

namespace Tests\Feature\Ai;

use App\Services\ListingAssistant\LocationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Finding the listing's city from the place named in the description (adniro:ai).
 */

class LocationResolverTest extends TestCase
{
	use RefreshDatabase;

	protected function setUp(): void
	{
		parent::setUp();

		$city = fn (int $id, string $name, int $population, string $country = 'US') => [
			'id' => $id, 'country_code' => $country, 'name' => json_encode(['en' => $name]),
			'latitude' => 40.7, 'longitude' => -74.0, 'population' => $population, 'active' => 1,
		];
		DB::table('cities')->insert([
			$city(1, 'New York City', 8_000_000),
			$city(2, 'West New York', 50_000),
			$city(3, 'Spring', 60_000),
			$city(4, 'Springfield', 170_000),
			$city(5, 'Newark', 300_000),
			$city(6, 'New Yorkshire', 1_000, 'GB'),
		]);
	}

	public function test_a_partial_name_finds_the_city_it_starts(): void
	{
		// Act
		[$city, $source] = $this->resolve('New York');

		// Assert
		$this->assertSame(1, $city?->id);
		$this->assertSame('text', $source);
	}

	public function test_an_exact_name_wins_over_a_longer_one(): void
	{
		// Act
		[$city] = $this->resolve('Spring');

		// Assert: "Springfield" is bigger, but "Spring" is the name given
		$this->assertSame(3, $city?->id);
	}

	public function test_a_prefix_must_end_at_a_word(): void
	{
		// Act
		[$city] = $this->resolve('New');

		// Assert: neither "Newark" nor "New York City"
		$this->assertNull($city);
	}

	public function test_short_or_unknown_names_find_nothing(): void
	{
		// Act & Assert
		$this->assertNull($this->resolve('Ne')[0]);
		$this->assertNull($this->resolve('Boston')[0]);
	}

	public function test_only_cities_of_the_current_country_are_used(): void
	{
		// Act
		[$city] = $this->resolve('New York', 'GB');

		// Assert: "New Yorkshire" does not start with the word "New York"
		$this->assertNull($city);
	}

	/** @return array{0: ?\App\Models\City, 1: ?string} */
	private function resolve(string $text, string $country = 'US'): array
	{
		return app(LocationResolver::class)->resolve($country, null, null, $text, null);
	}
}
