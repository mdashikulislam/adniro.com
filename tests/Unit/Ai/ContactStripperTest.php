<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\ContactStripper;
use PHPUnit\Framework\TestCase;

class ContactStripperTest extends TestCase
{
	/**
	 * @dataProvider kept
	 */
	public function test_dates_years_and_sizes_are_kept(string $text): void
	{
		$this->assertSame($text, ContactStripper::strip($text));
	}
	
	public static function kept(): array
	{
		return [
			'iso date'     => ['Bought on 2026-11-20, used twice.'],
			'dotted date'  => ['Serviced 12.03.2025 at the dealer.'],
			'year range'   => ['Fits models 1998-2004.'],
			'storage'      => ['iPhone 13, 128 GB, 2021.'],
			'mileage'      => ['Only 45 000 km.'],
		];
	}
	
	/**
	 * @dataProvider removed
	 */
	public function test_contact_details_are_removed(string $text, string $contact): void
	{
		$this->assertStringNotContainsString($contact, ContactStripper::strip($text));
	}
	
	public static function removed(): array
	{
		return [
			'local phone'   => ['Call 0171 234 5678 today', '234 5678'],
			'intl phone'    => ['WhatsApp +44 20 7946 0958', '7946 0958'],
			'dashed phone'  => ['Ring 555-123-4567', '123-4567'],
			'email'         => ['Write to sam@example.com', 'sam@example.com'],
			'link'          => ['See https://example.com/x for more', 'example.com'],
		];
	}
}
