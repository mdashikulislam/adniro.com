<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostModeration;
use App\Notifications\PostNotApproved;
use App\Services\ListingCleanUp\CleanUpRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/*
 * php artisan listings:clean-up (adniro): hides the live listings that match the clean-up rules.
 * It only lists them unless --apply is given, records why each one was hidden, keeps the newest
 * of a set of copies, and never emails the owners.
 */

class ListingCleanUpCommandTest extends TestCase
{
	use RefreshDatabase;
	
	private const LONG_TEXT = 'Certified trainers, flexible batches and placement help for every student who joins this month.';
	
	protected function setUp(): void
	{
		parent::setUp();
		
		Notification::fake();
	}
	
	public function test_without_apply_it_lists_the_matches_and_changes_nothing(): void
	{
		// Arrange
		$pills = $this->listing(['title' => 'Shop Codeine Online with Fast Delivery']);
		$this->listing(['title' => 'Grey sofa for sale']);
		
		// Act
		$this->artisan('listings:clean-up')
			->expectsOutputToContain('Shop Codeine Online with Fast Delivery')
			->expectsOutputToContain('Nothing was changed')
			->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $pills->fresh()->moderation_status);
		$this->assertSame(0, PostModeration::query()->count());
	}
	
	public function test_apply_hides_a_matching_listing_and_records_why(): void
	{
		// Arrange
		$pills = $this->listing(['title' => 'Shop Codeine Online with Fast Delivery']);
		$sofa = $this->listing(['title' => 'Grey sofa for sale']);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Assert
		$this->assertSame('rejected', $pills->fresh()->moderation_status);
		$this->assertNull(Post::query()->find($pills->id), 'no longer public');
		$this->assertSame('approved', $sofa->fresh()->moderation_status);
		
		$row = PostModeration::query()->sole();
		$this->assertSame($pills->id, (int)$row->post_id);
		$this->assertSame('system', $row->source);
		$this->assertSame('cleanup', $row->trigger);
		$this->assertSame(CleanUpRules::PROHIBITED, $row->rule);
		$this->assertSame(['illegal_drugs'], $row->categories);
		$this->assertSame('rejected', $row->applied_status);
	}
	
	public function test_the_owners_are_not_emailed(): void
	{
		// Arrange
		$pills = $this->listing(['title' => 'Shop Codeine Online with Fast Delivery']);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Assert
		Notification::assertNotSentTo($pills, PostNotApproved::class);
		$this->assertNull(PostModeration::query()->sole()->notified_at);
	}
	
	public function test_the_newest_copy_of_a_repeated_listing_stays(): void
	{
		// Arrange
		$oldest = $this->listing(['title' => 'Hanning Law Office', 'user_id' => 1]);
		$older = $this->listing(['title' => 'hanning law office ', 'user_id' => 2]);
		$newest = $this->listing(['title' => 'Hanning Law Office', 'user_id' => 3]);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $newest->fresh()->moderation_status);
		$this->assertSame('rejected', $older->fresh()->moderation_status);
		$this->assertSame('rejected', $oldest->fresh()->moderation_status);
		$this->assertSame([CleanUpRules::DUPLICATE], PostModeration::query()->distinct()->pluck('rule')->all());
	}
	
	public function test_the_same_text_under_another_title_is_a_copy_too(): void
	{
		// Arrange
		$older = $this->listing(['title' => 'Safety course in Leeds', 'description' => '<p>' . self::LONG_TEXT . '</p>']);
		$newest = $this->listing(['title' => 'Safety course in York', 'description' => self::LONG_TEXT]);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $newest->fresh()->moderation_status);
		$this->assertSame('rejected', $older->fresh()->moderation_status);
	}
	
	public function test_two_short_texts_that_are_the_same_are_not_copies(): void
	{
		// Arrange
		$first = $this->listing(['title' => 'Grey sofa', 'description' => 'Call me.']);
		$second = $this->listing(['title' => 'Oak table', 'description' => 'Call me.']);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $first->fresh()->moderation_status);
		$this->assertSame('approved', $second->fresh()->moderation_status);
	}
	
	public function test_a_listing_an_admin_approved_by_hand_is_left_alone(): void
	{
		// Arrange
		$post = $this->listing(['title' => 'Buy Weight Loss Medication Online']);
		PostModeration::query()->create([
			'post_id' => $post->id, 'trigger' => 'admin', 'source' => 'human', 'mode' => 'off',
			'decided_status' => 'approved', 'applied_status' => 'approved', 'rule' => 'admin_approved',
		]);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $post->fresh()->moderation_status);
	}
	
	public function test_listings_that_are_not_live_are_not_touched(): void
	{
		// Arrange
		$archived = $this->listing(['title' => 'Shop Codeine Online', 'archived_at' => now()]);
		$deleted = $this->listing(['title' => 'Shop Codeine Online now', 'deleted_at' => now()]);
		$waiting = $this->listing(['title' => 'Shop Codeine Online today', 'moderation_status' => 'needs_review']);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $archived->fresh()->moderation_status);
		$this->assertSame('approved', $deleted->fresh()->moderation_status);
		$this->assertSame('needs_review', $waiting->fresh()->moderation_status);
		$this->assertSame(0, PostModeration::query()->count());
	}
	
	public function test_it_can_be_limited_to_some_rules(): void
	{
		// Arrange
		$pills = $this->listing(['title' => 'Shop Codeine Online with Fast Delivery']);
		$training = $this->listing(['title' => 'Python Online Training from Hyderabad', 'country_code' => 'US']);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true, '--rule' => ['wrong_country']])->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $pills->fresh()->moderation_status);
		$this->assertSame('rejected', $training->fresh()->moderation_status);
	}
	
	public function test_an_unknown_rule_is_refused(): void
	{
		$this->artisan('listings:clean-up', ['--rule' => ['everything']])
			->expectsOutputToContain('Unknown rule')
			->assertFailed();
	}
	
	public function test_listings_can_be_left_out_by_their_number(): void
	{
		// Arrange
		$kept = $this->listing(['title' => 'Shop Codeine Online with Fast Delivery']);
		$hidden = $this->listing(['title' => 'Hydrocodone painkillers offered online']);
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true, '--except' => (string)$kept->id])->assertSuccessful();
		
		// Assert
		$this->assertSame('approved', $kept->fresh()->moderation_status);
		$this->assertSame('rejected', $hidden->fresh()->moderation_status);
	}
	
	public function test_a_second_run_finds_nothing_more(): void
	{
		// Arrange
		$this->listing(['title' => 'Shop Codeine Online with Fast Delivery']);
		$this->artisan('listings:clean-up', ['--apply' => true])->assertSuccessful();
		
		// Act
		$this->artisan('listings:clean-up', ['--apply' => true])
			->expectsOutputToContain('No listing matches')
			->assertSuccessful();
		
		// Assert
		$this->assertSame(1, PostModeration::query()->count());
	}
	
	private function listing(array $overrides = []): Post
	{
		$id = DB::table('posts')->insertGetId(array_merge([
			'country_code' => 'US', 'user_id' => 7, 'category_id' => 1, 'city_id' => 1, 'post_type_id' => 1,
			'title' => 'Grey sofa for sale', 'description' => 'Fabric sofa, no stains.', 'price' => 150,
			'currency_code' => 'USD', 'contact_name' => 'Sam', 'email' => 'owner@example.test', 'auth_field' => 'email',
			'email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now(),
			'moderation_status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
		], $overrides));
		
		return Post::query()->withoutGlobalScopes()->find($id);
	}
}
