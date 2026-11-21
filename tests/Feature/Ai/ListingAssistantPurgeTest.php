<?php

namespace Tests\Feature\Ai;

use App\Models\PostDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * The hourly purge of listing assistant drafts and temporary uploads (adniro:ai).
 */

class ListingAssistantPurgeTest extends TestCase
{
	use RefreshDatabase, ListingAssistantFixtures;
	
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpListingAssistant();
		
		Storage::fake(config('filesystems.default', 'public'));
		config([
			'settings.listing_form.pictures_limit' => 5,
			'settings.upload.max_image_size'       => 2500,
			'settings.upload.image_types'          => 'jpg,jpeg,png,webp',
		]);
	}
	
	public function test_the_purge_removes_expired_drafts_and_old_temporary_uploads(): void
	{
		$disk = Storage::disk(config('filesystems.default', 'public'));
		$disk->put('temporary/old1/a.jpg', 'x');
		$disk->put('temporary/new1/b.jpg', 'x');
		touch($disk->path('temporary/old1/a.jpg'), now()->subHours(50)->getTimestamp());
		
		$expired = PostDraft::query()->create(['token' => str_repeat('a', 32), 'status' => 'drafted', 'expires_at' => now()->subHour()]);
		$fresh = PostDraft::query()->create(['token' => str_repeat('b', 32), 'status' => 'drafted', 'expires_at' => now()->addHour()]);
		
		$this->artisan('ai:listing-assistant-purge', ['--dry-run' => true])->expectsOutputToContain('Would delete: 1 drafts (0 counted drafts kept for the AI post limit, emptied), 1 temporary files')->assertSuccessful();
		$disk->assertExists('temporary/old1/a.jpg');
		
		$this->artisan('ai:listing-assistant-purge')->assertSuccessful();
		
		$disk->assertMissing('temporary/old1/a.jpg');
		$this->assertFalse($disk->exists('temporary/old1'), 'the empty folder is removed');
		$disk->assertExists('temporary/new1/b.jpg');
		$this->assertNull(PostDraft::query()->find($expired->id));
		$this->assertNotNull(PostDraft::query()->find($fresh->id));
	}
}
