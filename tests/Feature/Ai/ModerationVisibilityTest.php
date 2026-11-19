<?php

namespace Tests\Feature\Ai;

use App\Http\Resources\PostResource;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Observers\SettingObserver;
use App\Services\Ai\Moderation\ModerationStatus;
use App\Services\Ai\Moderation\ModerationVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Phase 2: a listing that moderation has not approved is hidden from every public query,
 * while its owner and staff can still open it (adniro:ai).
 */

class ModerationVisibilityTest extends TestCase
{
	use RefreshDatabase;
	
	private const SCOPES = [VerifiedScope::class, ReviewedScope::class];
	
	private array $ids = [];
	
	protected function setUp(): void
	{
		parent::setUp();
		
		config(['settings.listing_form.listings_review_activation' => '0']);
		
		foreach (ModerationStatus::ALL as $status) {
			$this->ids[$status] = $this->insertPost(['moderation_status' => $status, 'title' => "Listing {$status}"]);
		}
	}
	
	public function test_new_rows_default_to_approved(): void
	{
		$id = DB::table('posts')->insertGetId($this->postRow(['title' => 'No status given']));
		
		$this->assertSame('approved', DB::table('posts')->where('id', $id)->value('moderation_status'));
	}
	
	public function test_public_queries_only_return_approved_listings(): void
	{
		$this->assertSame([$this->ids['approved']], Post::query()->orderBy('id')->pluck('id')->all());
		$this->assertNull(Post::query()->find($this->ids['rejected']));
	}
	
	public function test_the_local_scopes_follow_the_moderation_status(): void
	{
		$base = fn () => Post::query()->withoutGlobalScopes(self::SCOPES)->orderBy('id');
		
		$this->assertSame([$this->ids['approved']], $base()->verified()->pluck('id')->all());
		$this->assertSame([$this->ids['approved']], $base()->reviewed()->pluck('id')->all());
		$this->assertEqualsCanonicalizing(
			[$this->ids['pending'], $this->ids['needs_review'], $this->ids['rejected']],
			$base()->unverified()->pluck('id')->all()
		);
	}
	
	public function test_the_unverified_scope_stays_inside_other_filters(): void
	{
		// With review on, the old scope added a bare "or reviewed_at is null" that ignored "user_id"
		config(['settings.listing_form.listings_review_activation' => '1']);
		$otherUsersPost = $this->insertPost(['user_id' => 99, 'reviewed_at' => null, 'title' => 'Someone else']);
		
		$ids = Post::query()->withoutGlobalScopes(self::SCOPES)->where('user_id', 7)->unverified()->pluck('id')->all();
		
		$this->assertNotContains($otherUsersPost, $ids);
		$this->assertContains($this->ids['rejected'], $ids);
	}
	
	public function test_the_admin_panel_still_sees_every_listing(): void
	{
		$this->app->instance('request', \Illuminate\Http\Request::create('/' . urlGen()->adminUri() . '/posts'));
		
		$this->assertTrue(isFromAdminPanel());
		$this->assertCount(4, Post::query()->get());
	}
	
	public function test_is_verified_post_requires_approval(): void
	{
		$row = ['email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now()];
		
		$this->assertTrue(isVerifiedPost($row), 'a missing status counts as approved');
		$this->assertTrue(isVerifiedPost($row + ['moderation_status' => 'approved']));
		$this->assertFalse(isVerifiedPost($row + ['moderation_status' => 'rejected']));
		$this->assertFalse(isVerifiedPost($row + ['moderation_status' => 'pending']));
		$this->assertTrue(isVerifiedPost($row + ['moderation_status' => 'rejected'], false), 'email and phone only');
		$this->assertFalse(isVerifiedPost(['email_verified_at' => null, 'phone_verified_at' => now(), 'reviewed_at' => now()]));
	}
	
	public function test_only_the_owner_and_staff_can_see_a_hidden_listing(): void
	{
		$hidden = ['user_id' => 7, 'moderation_status' => 'rejected'];
		
		$this->assertTrue(ModerationVisibility::canSee(['user_id' => 7, 'moderation_status' => 'approved']));
		$this->assertTrue(ModerationVisibility::canSee(['user_id' => 7]));
		$this->assertFalse(ModerationVisibility::canSee($hidden), 'guest');
		$this->assertFalse(ModerationVisibility::canSee(null));
		
		$this->actingAs($this->makeUser(8));
		$this->assertFalse(ModerationVisibility::canSee($hidden), 'another member');
		
		$this->actingAs($this->makeUser(7));
		$this->assertTrue(ModerationVisibility::canSee($hidden), 'owner');
		
		$staff = $this->makeUser(9);
		foreach (Permission::getStaffPermissions() as $name) {
			Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
		}
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
		$staff->givePermissionTo(Permission::getStaffPermissions());
		$this->actingAs($staff);
		$this->assertTrue(ModerationVisibility::canSee($hidden), 'staff');
	}
	
	public function test_a_user_cannot_set_the_moderation_columns(): void
	{
		$post = new Post();
		$post->fill(['title' => 'x', 'moderation_status' => 'approved', 'moderation_hash' => str_repeat('a', 64), 'moderated_at' => now()]);
		
		$this->assertSame('x', strtolower($post->title));
		$this->assertNull($post->getAttribute('moderation_status'));
		$this->assertNull($post->getAttribute('moderation_hash'));
		$this->assertNull($post->getAttribute('moderated_at'));
	}
	
	public function test_the_api_resource_exposes_the_status(): void
	{
		$post = Post::query()->withoutGlobalScopes(self::SCOPES)->find($this->ids['rejected']);
		
		$data = (new PostResource($post, []))->toArray(request());
		
		$this->assertSame('rejected', $data['moderation_status']);
		$this->assertFalse(isVerifiedPost($data));
	}
	
	public function test_saving_the_listing_form_settings_does_not_unhide(): void
	{
		DB::table('posts')->update(['reviewed_at' => null]);
		$setting = (object)['field_values' => ['listings_review_activation' => '1']];
		
		$method = new \ReflectionMethod(SettingObserver::class, 'autoReviewedExistingPostsIfApprobationIsEnabled');
		$method->invoke(new SettingObserver(), $setting);
		
		// The bulk update goes through the global scopes, so it only touches public listings
		$this->assertNotNull(DB::table('posts')->where('id', $this->ids['approved'])->value('reviewed_at'));
		$this->assertNull(DB::table('posts')->where('id', $this->ids['rejected'])->value('reviewed_at'));
		$this->assertSame('rejected', DB::table('posts')->where('id', $this->ids['rejected'])->value('moderation_status'));
		$this->assertSame([$this->ids['approved']], Post::query()->pluck('id')->all());
	}
	
	public function test_a_paid_promotion_does_not_unhide(): void
	{
		// What App\Helpers\Services\Payment::register() does to a promoted listing
		$payable = Post::query()->withoutGlobalScopes(self::SCOPES)->find($this->ids['rejected']);
		$payable->reviewed_at = now();
		$payable->featured = 1;
		Post::withoutEvents(fn () => $payable->save());
		
		$this->assertSame('rejected', $payable->fresh()->moderation_status);
		$this->assertNull(Post::query()->find($this->ids['rejected']));
	}
	
	// ----------------------------------------------------------------------
	
	private function insertPost(array $overrides = []): int
	{
		return DB::table('posts')->insertGetId($this->postRow($overrides));
	}
	
	private function postRow(array $overrides = []): array
	{
		return array_merge([
			'country_code'      => 'US',
			'user_id'           => 7,
			'category_id'       => 1,
			'city_id'           => 1,
			'title'             => 'Listing',
			'description'       => 'A description long enough.',
			'email'             => 'owner@example.test',
			'auth_field'        => 'email',
			'email_verified_at' => now(),
			'phone_verified_at' => now(),
			'reviewed_at'       => now(),
			'created_at'        => now(),
			'updated_at'        => now(),
		], $overrides);
	}
	
	private function makeUser(int $id): User
	{
		return User::query()->forceCreate([
			'id'                => $id,
			'name'              => "User {$id}",
			'email'             => "user{$id}@example.test",
			'auth_field'        => 'email',
			'password'          => bcrypt(str()->random(32)),
			'email_verified_at' => now(),
			'phone_verified_at' => now(),
		]);
	}
}
