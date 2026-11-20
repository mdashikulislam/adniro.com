<?php

namespace Tests\Feature\Ai;

use App\Jobs\Ai\ModeratePost;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\Permission;
use App\Models\Post;
use App\Models\PostModeration;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminModerationAlert;
use App\Notifications\PostNotApproved;
use App\Notifications\PostReviewed;
use App\Services\Ai\AiResult;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\Moderation\ContentHash;
use App\Services\Ai\Moderation\ModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/*
 * Phase 5: the admin review queue and admin alerts (adniro:ai).
 */

class ModerationQueueTest extends TestCase
{
	use RefreshDatabase;
	
	private FakeAiClient $ai;
	private AiModel $model;
	private User $admin;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		Notification::fake();
		$this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
		config([
			'ai_features.queue.connection'                     => 'redis',
			'queue.connections.redis.driver'                   => 'redis',
			'settings.mail.confirmation'                       => '1',
			'settings.listing_form.listings_review_activation' => '0',
		]);
		
		$this->ai = new FakeAiClient();
		$this->app->instance(AiClientContract::class, $this->ai);
		
		$provider = AiProvider::query()->create(['name' => 'Test', 'driver' => 'anthropic', 'api_key' => 'k', 'active' => true]);
		$this->model = AiModel::query()->forceCreate([
			'ai_provider_id' => $provider->id, 'model_id' => 'fake-model', 'supports_vision' => 1,
			'supports_structured_output' => 1, 'active' => 1, 'last_test_ok' => 1, 'moderation_eval_passed_at' => now(),
		]);
		$this->setAi();
		
		foreach (array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions(), ['admin.post.edit', 'admin.post.view']) as $name) {
			Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
		}
		app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
		
		$this->admin = $this->user(1, array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions()));
	}
	
	// --- Access ------------------------------------------------------------
	
	public function test_super_admins_and_listing_editors_can_open_the_queue_other_staff_cannot(): void
	{
		$this->actingAs($this->admin)->get('/admin/moderation')->assertOk()->assertSee('AI review queue');
		
		$editor = $this->user(2, array_merge(Permission::getStaffPermissions(), ['admin.post.edit', 'admin.post.view']));
		$this->actingAs($editor)->get('/admin/moderation')->assertOk();
		
		$staff = $this->user(3, Permission::getStaffPermissions());
		$this->actingAs($staff)->get('/admin/moderation')->assertForbidden();
		$this->actingAs($staff)->post('/admin/moderation/1/approve')->assertForbidden();
	}
	
	// --- Listing -----------------------------------------------------------
	
	public function test_the_queue_lists_listings_with_the_ai_reasons_oldest_first(): void
	{
		$newer = $this->insertPost(['title' => 'Newer held listing', 'moderation_status' => 'needs_review', 'updated_at' => now()->subMinutes(5)]);
		$older = $this->insertPost(['title' => 'Older held listing', 'moderation_status' => 'needs_review', 'updated_at' => now()->subHours(3)]);
		$this->insertPost(['title' => 'Live listing']);
		$this->insertPost(['title' => 'Rejected listing', 'moderation_status' => 'rejected']);
		PostModeration::query()->create([
			'post_id' => $older->id, 'source' => 'ai', 'mode' => 'enforce', 'decided_status' => 'needs_review', 'applied_status' => 'needs_review',
			'categories' => ['weapons_explosives'], 'confidence' => 'medium', 'rule' => 'needs_human',
			'internal_reason' => 'Mentions a <b>pistol</b>.', 'model' => 'test/fake-model',
		]);
		
		$response = $this->actingAs($this->admin)->get('/admin/moderation');
		
		$response->assertOk()
			->assertSeeInOrder(['Older held listing', 'Newer held listing'])
			->assertSee('Weapons or explosives')
			->assertSee('Mentions a &lt;b&gt;pistol&lt;/b&gt;.', false)
			->assertSee('test/fake-model')
			->assertDontSee('Live listing')
			->assertDontSee('Rejected listing');
		
		$this->actingAs($this->admin)->get('/admin/moderation?status=rejected')->assertSee('Rejected listing')->assertDontSee('Older held listing');
		$this->actingAs($this->admin)->get('/admin/moderation?status=all&q=Newer')->assertSee('Newer held listing')->assertDontSee('Older held listing');
	}
	
	public function test_shadow_flags_show_live_listings_the_ai_would_have_held(): void
	{
		$flagged = $this->insertPost(['title' => 'Shadow flagged']);
		$cleared = $this->insertPost(['title' => 'Shadow cleared']);
		PostModeration::query()->create(['post_id' => $flagged->id, 'source' => 'ai', 'mode' => 'shadow', 'decided_status' => 'rejected', 'categories' => ['scam_fraud']]);
		PostModeration::query()->create(['post_id' => $cleared->id, 'source' => 'ai', 'mode' => 'shadow', 'decided_status' => 'rejected']);
		PostModeration::query()->create(['post_id' => $cleared->id, 'source' => 'human', 'mode' => 'shadow', 'decided_status' => 'approved']);
		
		$this->actingAs($this->admin)->get('/admin/moderation?status=shadow')
			->assertOk()->assertSee('Shadow flagged')->assertDontSee('Shadow cleared');
	}
	
	// --- Decisions ---------------------------------------------------------
	
	public function test_approving_publishes_the_listing_announces_it_and_sticks(): void
	{
		$post = $this->insertPost(['moderation_status' => 'needs_review']);
		
		$this->actingAs($this->admin)->post('/admin/moderation/' . $post->id . '/approve')->assertRedirect();
		
		$fresh = $post->fresh();
		$this->assertSame('approved', $fresh->moderation_status);
		$this->assertSame(ContentHash::for($fresh), $fresh->moderation_hash);
		$row = PostModeration::query()->sole();
		$this->assertSame('human', $row->source);
		$this->assertSame($this->admin->id, (int)$row->reviewer_id);
		$this->assertSame('approved', $row->applied_status);
		Notification::assertSentToTimes($fresh, PostReviewed::class, 1);
		
		// The same content is never sent to the AI again
		DB::table('posts')->where('id', $post->id)->update(['moderation_status' => 'pending']);
		$this->assertSame(ModerationService::ALREADY_CHECKED, app(ModerationService::class)->moderate($post->id, 'pictures'));
		$this->assertSame('approved', $post->fresh()->moderation_status);
		$this->assertSame(0, $this->ai->calls);
	}
	
	public function test_approving_also_marks_the_listing_reviewed_when_human_review_is_on(): void
	{
		config(['settings.listing_form.listings_review_activation' => '1']);
		$post = $this->insertPost(['moderation_status' => 'needs_review', 'reviewed_at' => null]);
		
		$this->actingAs($this->admin)->post('/admin/moderation/' . $post->id . '/approve');
		
		$this->assertNotNull($post->fresh()->reviewed_at);
	}
	
	public function test_rejecting_needs_a_reason_then_hides_and_emails_the_owner(): void
	{
		$post = $this->insertPost(['moderation_status' => 'needs_review']);
		PostModeration::query()->create(['post_id' => $post->id, 'source' => 'ai', 'decided_status' => 'needs_review', 'categories' => ['scam_fraud'], 'user_reason' => 'Gift card payment.']);
		
		$this->actingAs($this->admin)->get('/admin/moderation/' . $post->id . '/reject')
			->assertOk()->assertSee('Scam or fraud')->assertSee('Gift card payment.');
		
		$this->actingAs($this->admin)->post('/admin/moderation/' . $post->id . '/reject', ['categories' => []])->assertSessionHasErrors('categories');
		$this->actingAs($this->admin)->post('/admin/moderation/' . $post->id . '/reject', ['categories' => ['made_up']])->assertSessionHasErrors('categories.0');
		$this->assertSame('needs_review', $post->fresh()->moderation_status);
		
		$this->actingAs($this->admin)->post('/admin/moderation/' . $post->id . '/reject', [
			'categories' => ['scam_fraud'],
			'reason'     => 'Asks for gift cards. Write to me at x@y.co',
		])->assertRedirect();
		
		$fresh = $post->fresh();
		$this->assertSame('rejected', $fresh->moderation_status);
		$row = PostModeration::query()->where('source', 'human')->sole();
		$this->assertSame(['scam_fraud'], $row->categories);
		$this->assertSame('Asks for gift cards. Write to me at', $row->user_reason);
		$this->assertNotNull($row->notified_at);
		Notification::assertSentToTimes($fresh, PostNotApproved::class, 1);
		Notification::assertNotSentTo(User::all(), AdminModerationAlert::class);
	}
	
	public function test_rerun_queues_a_forced_check(): void
	{
		Queue::fake();
		$post = $this->insertPost(['moderation_status' => 'needs_review']);
		
		$this->actingAs($this->admin)->post('/admin/moderation/' . $post->id . '/rerun')->assertRedirect();
		
		Queue::assertPushed(ModeratePost::class, fn (ModeratePost $job) => $job->postId === $post->id && $job->force && $job->trigger === 'rerun');
	}
	
	public function test_a_forced_check_asks_the_ai_even_for_decided_content(): void
	{
		$post = $this->insertPost(['moderation_status' => 'needs_review']);
		DB::table('posts')->where('id', $post->id)->update(['moderation_hash' => ContentHash::for($post)]);
		$this->ai->answer(['verdict' => 'allow', 'categories' => [], 'confidence' => 'high', 'user_reason' => '', 'internal_reason' => '']);
		
		$outcome = app(ModerationService::class)->moderate($post->id, 'rerun', null, true, true);
		
		$this->assertSame(ModerationService::DECIDED, $outcome);
		$this->assertSame(1, $this->ai->calls);
		$this->assertSame('approved', $post->fresh()->moderation_status);
	}
	
	public function test_rerun_is_refused_when_moderation_is_off(): void
	{
		Queue::fake();
		$this->setAi(['moderation_mode' => 'off']);
		$post = $this->insertPost(['moderation_status' => 'needs_review']);
		
		$this->actingAs($this->admin)->post('/admin/moderation/' . $post->id . '/rerun');
		
		Queue::assertNotPushed(ModeratePost::class);
	}
	
	public function test_the_history_shows_every_decision(): void
	{
		$post = $this->insertPost(['moderation_status' => 'rejected']);
		PostModeration::query()->create(['post_id' => $post->id, 'source' => 'ai', 'decided_status' => 'needs_review', 'rule' => 'needs_human', 'model' => 'test/fake-model']);
		PostModeration::query()->create(['post_id' => $post->id, 'source' => 'human', 'decided_status' => 'rejected', 'applied_status' => 'rejected', 'rule' => 'admin_rejected', 'reviewer_id' => $this->admin->id, 'notified_at' => now()]);
		
		$this->actingAs($this->admin)->get('/admin/moderation/' . $post->id . '/history')
			->assertOk()
			->assertSeeInOrder(['Rejected by an admin', 'AI asked for a person'])
			->assertSee($this->admin->name)
			->assertSee('Owner emailed');
		
		$this->actingAs($this->admin)->get('/admin/moderation/999999/history')->assertNotFound();
	}
	
	// --- Alerts ------------------------------------------------------------
	
	public function test_staff_are_alerted_when_a_listing_goes_to_review(): void
	{
		$post = $this->insertPost(['moderation_status' => 'pending']);
		$this->ai->respond(fn () => new AiResult(AiResult::REFUSAL));
		
		app(ModerationService::class)->moderate($post->id, 'created');
		
		Notification::assertSentTo($this->admin, AdminModerationAlert::class);
	}
	
	public function test_alerts_are_limited_to_twenty_an_hour_then_counted(): void
	{
		$this->ai->respond(fn () => new AiResult(AiResult::REFUSAL));
		for ($i = 0; $i < 23; $i++) {
			$post = $this->insertPost(['moderation_status' => 'pending', 'title' => 'Listing ' . $i]);
			app(ModerationService::class)->moderate($post->id, 'created');
		}
		
		Notification::assertSentToTimes($this->admin, AdminModerationAlert::class, 20);
		$this->assertSame(3, (int)Cache::get('ai:moderation_alerts_skipped'));
		
		// Next hour: the first alert says how many were left out
		$this->travel(1)->hours();
		$post = $this->insertPost(['moderation_status' => 'pending', 'title' => 'Next hour']);
		app(ModerationService::class)->moderate($post->id, 'created');
		
		Notification::assertSentTo($this->admin, AdminModerationAlert::class, function (AdminModerationAlert $alert) {
			return str_contains(implode(' ', $alert->toMail($this->admin)->introLines), '3 more listings');
		});
	}
	
	public function test_a_child_safety_flag_alerts_even_in_shadow_mode_and_over_the_limit(): void
	{
		$this->setAi(['moderation_mode' => 'shadow']);
		Cache::put('ai:moderation_alerts:' . now()->format('YmdH'), 20, now()->addHour());
		$post = $this->insertPost();
		$this->ai->answer(['verdict' => 'reject', 'categories' => ['child_safety'], 'confidence' => 'high', 'user_reason' => '', 'internal_reason' => 'x']);
		
		app(ModerationService::class)->moderate($post->id, 'created');
		
		Notification::assertSentTo($this->admin, AdminModerationAlert::class, fn ($alert) => str_contains($alert->toMail($this->admin)->subject, 'URGENT'));
		$this->assertSame('approved', $post->fresh()->moderation_status, 'shadow mode still hides nothing');
	}
	
	public function test_alerts_can_be_switched_off(): void
	{
		$this->setAi(['moderation_alert_admins' => '0']);
		$post = $this->insertPost(['moderation_status' => 'pending']);
		$this->ai->respond(fn () => new AiResult(AiResult::REFUSAL));
		
		app(ModerationService::class)->moderate($post->id, 'created');
		
		Notification::assertNotSentTo($this->admin, AdminModerationAlert::class);
	}
	
	// ----------------------------------------------------------------------
	
	private function setAi(array $overrides = []): void
	{
		Setting::query()->updateOrCreate(['name' => 'ai'], ['label' => 'AI', 'active' => 1, 'field_values' => array_merge([
			'enabled'             => '1',
			'moderation_mode'     => 'enforce',
			'moderation_model_id' => (string)$this->model->id,
		], $overrides)]);
	}
	
	private function insertPost(array $overrides = []): Post
	{
		$id = DB::table('posts')->insertGetId(array_merge([
			'country_code' => 'US', 'user_id' => 7, 'category_id' => 1, 'city_id' => 1, 'post_type_id' => 1,
			'title' => 'Grey sofa for sale', 'description' => 'Fabric sofa, no stains.', 'price' => 150,
			'currency_code' => 'USD', 'contact_name' => 'Sam', 'email' => 'owner@example.test', 'auth_field' => 'email',
			'email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now(),
			'created_at' => now(), 'updated_at' => now(),
		], $overrides));
		
		return Post::query()->withoutGlobalScopes()->find($id);
	}
	
	private function user(int $id, array $permissions): User
	{
		$user = User::query()->forceCreate([
			'id' => $id, 'name' => "Staff {$id}", 'email' => "staff{$id}@example.test", 'auth_field' => 'email',
			'password' => bcrypt(str()->random(32)), 'email_verified_at' => now(), 'phone_verified_at' => now(),
		]);
		$user->givePermissionTo($permissions);
		
		return $user;
	}
}
