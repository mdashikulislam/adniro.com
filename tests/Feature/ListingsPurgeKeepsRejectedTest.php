<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The daily "listings:purge" (adniro) archives old listings and deletes old archived ones. Rejected
 * listings (the clean-up, AI moderation) wait for an admin's decision instead: the purge never
 * archives or deletes them.
 */
class ListingsPurgeKeepsRejectedTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		config([
			'settings.cron.activated_listings_expiration' => '365',
			'settings.cron.archived_listings_expiration'  => '7',
			'mail.default'                                => 'array',
		]);
	}

	private function listing(string $title, string $status, array $overrides = []): int
	{
		$id = $this->stockListing($title, array_merge(['moderation_status' => $status], $overrides))->id;

		return (int)$id;
	}

	public function test_an_old_archived_listing_is_deleted_but_a_rejected_one_is_kept(): void
	{
		// Arrange: both archived a month ago
		$archivedAt = now()->subDays(30);
		$approvedId = $this->listing('Old archived bike', 'approved', ['archived_at' => $archivedAt]);
		$rejectedId = $this->listing('Payday loans fast cash', 'rejected', ['archived_at' => $archivedAt]);

		// Act
		$this->artisan('listings:purge')->assertExitCode(0);

		// Assert: the purge ran (the approved one is gone), the rejected one is untouched
		$approved = DB::table('posts')->where('id', $approvedId)->first();
		$this->assertTrue(is_null($approved) || !is_null($approved->deleted_at), 'The old archived listing should be deleted');
		$rejected = DB::table('posts')->where('id', $rejectedId)->first();
		$this->assertNotNull($rejected);
		$this->assertNull($rejected->deleted_at);
	}

	public function test_an_old_rejected_listing_is_not_archived(): void
	{
		// Arrange: created more than a year ago, never archived
		$old = now()->subDays(400);
		$rejectedId = $this->listing('Buy pills online', 'rejected', ['created_at' => $old, 'updated_at' => $old, 'reviewed_at' => $old]);

		// Act
		$this->artisan('listings:purge')->assertExitCode(0);

		// Assert
		$this->assertNull(DB::table('posts')->where('id', $rejectedId)->value('archived_at'));
	}
}
