<?php

namespace Tests\Feature\Ai;

use App\Jobs\Ai\ModeratePost;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\Picture;
use App\Models\Post;
use App\Models\PostModeration;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PostNotApproved;
use App\Notifications\PostReviewed;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\Moderation\ContentHash;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationTrigger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/*
 * Phase 4: enforce mode. Listings wait as pending, decisions are applied, the "online" email waits
 * for approval, rejections are emailed once, owners see why (adniro:ai).
 */

class ModerationEnforceTest extends TestCase
{
	use RefreshDatabase;
	
	private FakeAiClient $ai;
	private AiModel $model;
	
	private const ALLOW = ['verdict' => 'allow', 'categories' => [], 'confidence' => 'high', 'user_reason' => '', 'internal_reason' => 'Fine.'];
	private const REJECT = ['verdict' => 'reject', 'categories' => ['illegal_drugs'], 'confidence' => 'high', 'user_reason' => 'Selling <b>drugs</b> is not allowed.', 'internal_reason' => 'Drugs.'];
	
	protected function setUp(): void
	{
		parent::setUp();
		
		Notification::fake();
		config([
			'ai_features.queue.connection'                  => 'redis',
			'queue.connections.redis.driver'                => 'redis',
			'settings.mail.confirmation'                    => '1',
			'settings.listing_form.listings_review_activation' => '0',
		]);
		
		$this->ai = new FakeAiClient();
		$this->app->instance(AiClientContract::class, $this->ai);
		
		$provider = AiProvider::query()->create(['name' => 'Test', 'driver' => 'anthropic', 'api_key' => 'k', 'active' => true]);
		$this->model = AiModel::query()->forceCreate([
			'ai_provider_id' => $provider->id, 'model_id' => 'fake-model', 'supports_vision' => 1,
			'supports_structured_output' => 1, 'active' => 1, 'last_test_ok' => 1,
			'moderation_eval_passed_at' => now(),
		]);
		$this->setAi();
	}
	
	public function test_enforce_needs_a_model_that_passed_the_evaluation(): void
	{
		$this->assertSame('enforce', app(AiSettings::class)->moderationMode());
		
		$this->model->forceFill(['moderation_eval_passed_at' => null])->save();
		$this->assertSame('shadow', app(AiSettings::class)->moderationMode());
		
		$this->setAi(['moderation_model_id' => null]);
		$this->assertSame('shadow', app(AiSettings::class)->moderationMode());
	}
	
	public function test_a_new_listing_waits_hidden_and_goes_live_with_one_online_email(): void
	{
		Queue::fake();
		
		$post = $this->createPost();
		
		$this->assertSame('pending', $post->fresh()->moderation_status);
		$this->assertNull(Post::query()->find($post->id), 'hidden while pending');
		Queue::assertPushed(ModeratePost::class, 1);
		Notification::assertNotSentTo($post, PostReviewed::class);
		
		$this->ai->answer(self::ALLOW);
		$this->service()->moderate($post->id, ModerationTrigger::CREATED, 'en');
		
		$fresh = $post->fresh();
		$this->assertSame('approved', $fresh->moderation_status);
		$this->assertSame(ContentHash::for($fresh), $fresh->moderation_hash);
		$this->assertNotNull($fresh->moderated_at);
		$this->assertNotNull(Post::query()->find($post->id), 'public once approved');
		$this->assertSame('approved', PostModeration::query()->sole()->applied_status);
		Notification::assertSentToTimes($fresh, PostReviewed::class, 1);
	}
	
	public function test_a_rejected_listing_stays_hidden_and_its_owner_is_emailed_once(): void
	{
		$post = $this->insertPost(['moderation_status' => 'pending']);
		$this->ai->answer(self::REJECT);
		
		$this->service()->moderate($post->id, ModerationTrigger::CREATED, 'fr');
		
		$fresh = $post->fresh();
		$this->assertSame('rejected', $fresh->moderation_status);
		$this->assertNull(Post::query()->find($post->id));
		$row = PostModeration::query()->sole();
		$this->assertSame('rejected', $row->applied_status);
		$this->assertNotNull($row->notified_at);
		Notification::assertSentToTimes($fresh, PostNotApproved::class, 1);
		Notification::assertSentTo($fresh, PostNotApproved::class, fn ($n) => $n->locale === 'fr');
		Notification::assertNotSentTo($fresh, PostReviewed::class);
	}
	
	public function test_a_rejection_that_could_not_be_sent_is_not_marked_as_sent(): void
	{
		// Arrange: the owner's email is not verified, so there is no channel
		$post = $this->insertPost(['moderation_status' => 'pending', 'email_verified_at' => now()]);
		DB::table('posts')->where('id', $post->id)->update(['email' => null]);
		$this->ai->answer(self::REJECT);
		
		// Act
		$this->service()->moderate($post->id, ModerationTrigger::CREATED, 'en');
		
		// Assert
		$this->assertSame('rejected', $post->fresh()->moderation_status);
		$this->assertNull(PostModeration::query()->sole()->notified_at);
	}
	
	public function test_a_failed_admin_alert_is_logged(): void
	{
		// Arrange: an urgent case with nobody to tell
		\Illuminate\Support\Facades\Log::spy();
		$post = $this->insertPost(['moderation_status' => 'pending']);
		$this->ai->answer(['verdict' => 'reject', 'categories' => ['child_safety'], 'confidence' => 'high', 'user_reason' => '', 'internal_reason' => 'x']);
		
		// Act
		$this->service()->moderate($post->id, ModerationTrigger::CREATED, 'en');
		
		// Assert
		\Illuminate\Support\Facades\Log::shouldHaveReceived('log')->withArgs(fn ($level, $message) => $level === 'critical' && str_contains($message, 'Moderation alert'));
	}
	
	public function test_the_rejection_email_explains_and_escapes(): void
	{
		$post = $this->insertPost(['title' => 'Party <script>x</script>']);
		$row = PostModeration::query()->create([
			'post_id' => $post->id, 'source' => 'ai', 'categories' => ['illegal_drugs', 'bogus'],
			'user_reason' => 'Selling <b>drugs</b> is not allowed.',
		]);
		
		$mail = (new PostNotApproved($post, $row))->toMail($post);
		$html = implode("\n", array_merge($mail->introLines, $mail->outroLines)); // the subject is plain text, not HTML
		
		$this->assertStringContainsString('Illegal drugs', $html);
		$this->assertStringNotContainsString('bogus', $html);
		$this->assertStringContainsString('Selling &lt;b&gt;drugs&lt;/b&gt; is not allowed.', $html);
		$this->assertStringNotContainsString('<script>', $html);
		$this->assertSame(urlGen()->editPost($post), $mail->actionUrl);
		$this->assertSame(['mail'], (new PostNotApproved($post, $row))->via($post));
		$this->assertSame([], (new PostNotApproved($this->insertPost(['email_verified_at' => null]), $row))->via($post));
	}
	
	public function test_editing_a_live_listing_hides_it_until_the_new_check_without_a_second_online_email(): void
	{
		$post = $this->insertPost();
		$this->ai->answer(self::ALLOW);
		$this->service()->moderate($post->id, 'created');
		Notification::fake();
		Queue::fake();
		Cache::flush();
		
		$fresh = $post->fresh();
		$fresh->forceFill(['description' => 'Now with a new description.'])->save();
		
		$this->assertSame('pending', $fresh->fresh()->moderation_status, 'pending in the same save');
		Queue::assertPushed(ModeratePost::class, fn ($job) => $job->trigger === ModerationTrigger::EDITED);
		
		$this->service()->moderate($post->id, 'edited');
		
		$this->assertSame('approved', $post->fresh()->moderation_status);
		Notification::assertNotSentTo($post->fresh(), PostReviewed::class);
	}
	
	public function test_a_new_photo_hides_the_listing_but_a_reorder_or_payment_does_not(): void
	{
		$post = $this->insertPost();
		$pictureId = DB::table('pictures')->insertGetId(['post_id' => $post->id, 'file_path' => 'files/a.jpg', 'position' => 1, 'active' => 1]);
		Queue::fake();
		
		Picture::query()->withoutGlobalScopes()->find($pictureId)->update(['position' => 4]);
		$post->fresh()->forceFill(['featured' => 1, 'reviewed_at' => now()])->save();
		$this->assertSame('approved', $post->fresh()->moderation_status);
		
		Picture::query()->create(['post_id' => $post->id, 'file_path' => 'files/b.jpg', 'position' => 2, 'active' => 1]);
		$this->assertSame('pending', $post->fresh()->moderation_status);
		Queue::assertPushed(ModeratePost::class, fn ($job) => $job->trigger === ModerationTrigger::PICTURES);
	}
	
	public function test_admin_edits_stay_public_and_are_not_checked_again(): void
	{
		Queue::fake();
		$post = $this->insertPost();
		$this->app->instance('request', Request::create('/' . urlGen()->adminUri() . '/posts/' . $post->id . '/edit'));
		
		$post->fresh()->forceFill(['title' => 'Title fixed by an admin'])->save();
		
		$fresh = $post->fresh();
		$this->assertSame('approved', $fresh->moderation_status);
		$this->assertSame(ContentHash::for($fresh), $fresh->moderation_hash);
		Queue::assertNotPushed(ModeratePost::class);
		
		$this->app->instance('request', Request::create('/'));
		$this->assertSame(ModerationService::ALREADY_CHECKED, $this->service()->moderate($post->id, 'edited'));
		$this->assertSame(0, $this->ai->calls);
	}
	
	public function test_returning_to_content_that_was_rejected_restores_the_rejection_without_a_new_email(): void
	{
		$post = $this->insertPost();
		$this->ai->answer(self::REJECT);
		$this->service()->moderate($post->id, 'created');
		Notification::fake();
		
		$this->ai->answer(self::ALLOW);
		DB::table('posts')->where('id', $post->id)->update(['title' => 'A clean title', 'moderation_status' => 'pending']);
		$this->service()->moderate($post->id, 'edited');
		$this->assertSame('approved', $post->fresh()->moderation_status);
		
		DB::table('posts')->where('id', $post->id)->update(['title' => 'Grey sofa for sale', 'moderation_status' => 'pending']);
		$outcome = $this->service()->moderate($post->id, 'edited');
		
		$this->assertSame(ModerationService::ALREADY_CHECKED, $outcome);
		$this->assertSame('rejected', $post->fresh()->moderation_status);
		$this->assertSame(2, $this->ai->calls);
		Notification::assertNotSentTo($post->fresh(), PostNotApproved::class);
	}
	
	public function test_refusals_and_errors_go_to_a_human_without_emails(): void
	{
		$refused = $this->insertPost(['moderation_status' => 'pending']);
		$this->ai->respond(fn () => new AiResult(AiResult::REFUSAL));
		$this->service()->moderate($refused->id, 'created');
		$this->assertSame('needs_review', $refused->fresh()->moderation_status);
		
		$failed = $this->insertPost(['moderation_status' => 'pending', 'title' => 'Other']);
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR, errorType: 'Timeout', errorMessage: 'slow'));
		$this->assertSame(ModerationService::RETRY, $this->service()->moderate($failed->id, 'created', null, false));
		$this->assertSame('pending', $failed->fresh()->moderation_status);
		$this->service()->moderate($failed->id, 'created', null, true);
		$this->assertSame('needs_review', $failed->fresh()->moderation_status);
		
		Notification::assertNothingSent();
	}
	
	public function test_when_no_model_can_be_used_the_setting_decides(): void
	{
		// Arrange
		$this->ai->respond(fn () => AiResult::disabled('no_model'));
		$approved = $this->insertPost(['moderation_status' => 'pending']);
		
		// Act
		$this->service()->moderate($approved->id, 'created');
		$this->setAi(['moderation_when_unavailable' => 'review']);
		$held = $this->insertPost(['moderation_status' => 'pending', 'title' => 'Held one']);
		$this->service()->moderate($held->id, 'created');
		
		// Assert
		$this->assertSame('approved', $approved->fresh()->moderation_status);
		$this->assertSame('unavailable', PostModeration::query()->where('post_id', $approved->id)->sole()->rule);
		$this->assertSame('needs_review', $held->fresh()->moderation_status);
	}
	
	public function test_when_the_budget_runs_out_listings_wait_for_an_admin(): void
	{
		// Arrange: "publish" is chosen for an unavailable AI, but spending must not publish everything
		$this->setAi(['moderation_when_unavailable' => 'approve']);
		$this->ai->respond(fn () => AiResult::capped('daily_budget'));
		$post = $this->insertPost(['moderation_status' => 'pending']);
		
		// Act
		$this->service()->moderate($post->id, 'created');
		
		// Assert
		$this->assertSame('needs_review', $post->fresh()->moderation_status);
		$this->assertSame('capped', PostModeration::query()->where('post_id', $post->id)->sole()->rule);
	}
	
	public function test_a_listing_whose_photos_could_not_be_checked_goes_to_an_admin(): void
	{
		// Arrange: the photo file is missing, so the AI only sees the text
		$post = $this->insertPost(['moderation_status' => 'pending']);
		DB::table('pictures')->insert(['post_id' => $post->id, 'file_path' => 'files/missing.jpg', 'position' => 1, 'active' => 1]);
		$this->ai->answer(self::ALLOW);
		
		// Act
		$this->service()->moderate($post->id, 'created', 'en');
		
		// Assert
		$this->assertSame('needs_review', $post->fresh()->moderation_status);
		$row = PostModeration::query()->where('post_id', $post->id)->sole();
		$this->assertSame('photos_not_checked', $row->rule);
		$this->assertSame('allow', $row->verdict, 'the AI answer is kept for the admin');
	}
	
	public function test_the_sweep_hands_listings_to_admins_when_enforce_lost_its_evaluation(): void
	{
		// Arrange: enforce is chosen but the model no longer counts as evaluated
		$waiting = $this->insertPost(['moderation_status' => 'pending', 'updated_at' => now()->subMinutes(20)]);
		$this->model->forceFill(['moderation_eval_passed_at' => null])->save();
		
		// Act
		$this->artisan('ai:moderation-sweep')->assertSuccessful();
		
		// Assert
		$this->assertSame('needs_review', $waiting->fresh()->moderation_status);
		$this->assertSame('enforce_degraded', PostModeration::query()->where('post_id', $waiting->id)->sole()->rule);
	}
	
	public function test_the_sweep_changes_nothing_when_the_settings_cannot_be_read(): void
	{
		// Arrange
		$waiting = $this->insertPost(['moderation_status' => 'pending', 'updated_at' => now()->subMinutes(90)]);
		$this->app->instance(AiSettings::class, new class extends AiSettings {
			public function readFailed(): bool
			{
				return true;
			}
		});
		$this->app->forgetInstance(ModerationService::class);
		
		// Act
		$this->artisan('ai:moderation-sweep')->assertFailed();
		
		// Assert
		$this->assertSame('pending', $waiting->fresh()->moderation_status);
		$this->assertSame(0, PostModeration::query()->count());
	}
	
	public function test_one_broken_listing_does_not_stop_the_sweep(): void
	{
		// Arrange
		$this->setAi(['moderation_mode' => 'off']);
		$first = $this->insertPost(['moderation_status' => 'pending']);
		$second = $this->insertPost(['moderation_status' => 'pending', 'title' => 'Second']);
		$released = [];
		$service = \Mockery::mock($this->service());
		$service->shouldReceive('release')->andReturnUsing(function (Post $post) use ($first, &$released) {
			if ($post->id === $first->id) {
				throw new \RuntimeException('broken listing');
			}
			$released[] = $post->id;
		});
		$this->app->instance(ModerationService::class, $service);
		
		// Act
		$this->artisan('ai:moderation-sweep')->assertFailed();
		
		// Assert
		$this->assertSame([$second->id], $released);
	}
	
	public function test_the_sweep_requeues_and_hands_over_stuck_listings(): void
	{
		Queue::fake();
		$recent = $this->insertPost(['moderation_status' => 'pending', 'updated_at' => now()->subMinutes(2)]);
		$stuck = $this->insertPost(['moderation_status' => 'pending', 'updated_at' => now()->subMinutes(20)]);
		$lost = $this->insertPost(['moderation_status' => 'pending', 'updated_at' => now()->subMinutes(90)]);
		$unverified = $this->insertPost(['moderation_status' => 'pending', 'phone_verified_at' => null, 'updated_at' => now()->subMinutes(90)]);
		
		$this->artisan('ai:moderation-sweep')->assertSuccessful();
		
		Queue::assertPushed(ModeratePost::class, 1);
		Queue::assertPushed(ModeratePost::class, fn (ModeratePost $job) => $job->postId === $stuck->id);
		$this->assertSame('pending', $recent->fresh()->moderation_status);
		$this->assertSame('needs_review', $lost->fresh()->moderation_status);
		$this->assertSame('pending', $unverified->fresh()->moderation_status, 'waits for verification');
		$this->assertSame('sweep_timeout', PostModeration::query()->where('post_id', $lost->id)->sole()->rule);
	}
	
	public function test_the_owner_sees_why_and_others_see_nothing(): void
	{
		$post = $this->insertPost(['moderation_status' => 'rejected']);
		PostModeration::query()->create([
			'post_id' => $post->id, 'source' => 'ai', 'applied_status' => 'rejected',
			'categories' => ['scam_fraud'], 'user_reason' => 'Payment by gift card <i>first</i>.',
		]);
		$data = ['id' => $post->id, 'user_id' => 7, 'moderation_status' => 'rejected', 'title' => $post->title];
		
		$this->assertSame('', trim(view('front.ai.moderation-banner', ['post' => $data])->render()), 'guest');
		
		$this->actingAs($this->user(8));
		$this->assertSame('', trim(view('front.ai.moderation-banner', ['post' => $data])->render()), 'another member');
		
		$this->actingAs($this->user(7));
		$html = view('front.ai.moderation-banner', ['post' => $data])->render();
		$this->assertStringContainsString('Scam or fraud', $html);
		$this->assertStringContainsString('Payment by gift card &lt;i&gt;first&lt;/i&gt;.', $html);
		$this->assertStringContainsString(urlGen()->editPost($data), $html);
		
		$pending = view('front.ai.moderation-banner', ['post' => ['moderation_status' => 'pending'] + $data])->render();
		$this->assertStringContainsString('checking your listing', $pending);
		
		$this->assertStringContainsString('Not approved', view('front.ai.moderation-badge', ['post' => $data])->render());
		$this->assertSame('', trim(view('front.ai.moderation-badge', ['post' => ['moderation_status' => 'approved']])->render()));
		$this->assertStringContainsString('checking your listing', view('front.ai.moderation-finish-note')->render());
	}
	
	public function test_with_moderation_off_the_online_email_works_as_before(): void
	{
		$this->setAi(['moderation_mode' => 'off']);
		
		$post = $this->createPost();
		
		$this->assertSame('approved', $post->fresh()->moderation_status);
		Notification::assertSentToTimes($post, PostReviewed::class, 1);
	}
	
	// ----------------------------------------------------------------------
	
	private function service(): ModerationService
	{
		return app(ModerationService::class);
	}
	
	private function setAi(array $overrides = []): void
	{
		Setting::query()->updateOrCreate(['name' => 'ai'], ['label' => 'AI', 'active' => 1, 'field_values' => array_merge([
			'enabled'             => '1',
			'moderation_mode'     => 'enforce',
			'moderation_model_id' => (string)$this->model->id,
		], $overrides)]);
	}
	
	private function attributes(array $overrides = []): array
	{
		return array_merge([
			'country_code' => 'US', 'user_id' => 7, 'category_id' => 1, 'city_id' => 1, 'post_type_id' => 1,
			'title' => 'Grey sofa for sale', 'description' => 'Fabric sofa, no stains.', 'price' => 150,
			'currency_code' => 'USD', 'contact_name' => 'Sam', 'email' => 'owner@example.test', 'auth_field' => 'email',
			'email_verified_at' => now(), 'phone_verified_at' => now(), 'reviewed_at' => now(), 'create_from_ip' => '10.0.0.1',
		], $overrides);
	}
	
	private function createPost(array $overrides = []): Post
	{
		return Post::query()->forceCreate($this->attributes($overrides));
	}
	
	private function insertPost(array $overrides = []): Post
	{
		$id = DB::table('posts')->insertGetId(array_merge(['created_at' => now(), 'updated_at' => now()], $this->attributes($overrides)));
		
		return Post::query()->withoutGlobalScopes()->find($id);
	}
	
	private function user(int $id): User
	{
		return User::query()->forceCreate([
			'id' => $id, 'name' => "User {$id}", 'email' => "user{$id}@example.test", 'auth_field' => 'email',
			'password' => bcrypt(str()->random(32)), 'email_verified_at' => now(), 'phone_verified_at' => now(),
		]);
	}
}
