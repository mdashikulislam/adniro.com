<?php

namespace Tests\Unit\ListingForm;

use App\Services\ListingForm\PriceStats;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PriceStatsTest extends TestCase
{
	public function test_the_middle_half_of_the_prices_is_the_usual_range(): void
	{
		// Arrange: 100, 110 ... 200
		$prices = range(100, 200, 10);

		// Act
		$stats = PriceStats::summarize($prices, 8);

		// Assert
		$this->assertSame(['low' => 130.0, 'high' => 180.0, 'median' => 150.0, 'count' => 11], $stats);
	}

	public function test_far_off_prices_are_left_out(): void
	{
		// Arrange: a "1" placeholder and a typo with three extra zeros
		$prices = [1, 120, 125, 130, 135, 140, 145, 150, 155, 160, 150000];

		// Act
		$stats = PriceStats::summarize($prices, 8);

		// Assert
		$this->assertSame(9, $stats['count']);
		$this->assertGreaterThanOrEqual(120, $stats['low']);
		$this->assertLessThanOrEqual(160, $stats['high']);
	}

	public function test_too_few_prices_give_no_range(): void
	{
		// Act
		$stats = PriceStats::summarize([100, 120, 140], 8);

		// Assert
		$this->assertNull($stats);
	}

	public function test_zero_and_negative_prices_do_not_count(): void
	{
		// Act
		$stats = PriceStats::summarize([0, -5, 0, 100, 100, 100, 100, 100, 100, 100], 8);

		// Assert
		$this->assertNull($stats, 'only 7 real prices');
	}

	public function test_identical_prices_give_one_value(): void
	{
		// Act
		$stats = PriceStats::summarize(array_fill(0, 10, 250), 8);

		// Assert
		$this->assertSame(250.0, $stats['low']);
		$this->assertSame(250.0, $stats['high']);
	}

	public function test_a_majority_of_placeholder_prices_gives_no_range(): void
	{
		// Arrange: ten "1" placeholders and a few real prices
		$prices = [...array_fill(0, 10, 1), 300, 400];
		
		// Act
		$stats = PriceStats::summarize($prices, 8);
		
		// Assert
		$this->assertNull($stats);
	}
	
	public static function roundings(): array
	{
		return [
			'two digits stay'         => [47, 47.0],
			'hundreds to tens'        => [187, 190.0],
			'thousands to hundreds'   => [1234, 1200.0],
			'ten thousands'           => [12345, 12000.0],
			'millions'                => [1234567, 1200000.0],
			'below ten keeps a digit' => [7.4, 7.4],
			'small currency units'    => [0.254, 0.25],
			'zero'                    => [0, 0.0],
		];
	}

	#[DataProvider('roundings')]
	public function test_amounts_are_rounded_to_two_significant_digits(float $value, float $expected): void
	{
		// Act
		$rounded = PriceStats::roundNice($value);

		// Assert
		$this->assertSame($expected, $rounded);
	}
}
