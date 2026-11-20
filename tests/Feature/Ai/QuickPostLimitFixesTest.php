<?php

namespace Tests\Feature\Ai;

use App\Models\PostDraft;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Fixes from the review of the AI post limit (docs/professional-website-plan.md, phase 2).
 */

class QuickPostLimitFixesTest extends TestCase
{
	use RefreshDatabase, QuickPostFixtures;

	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpQuickPost();
		$this->ai->answer($this->draftAnswer());
	}

	public function test_free_redrafts_cannot_chain_past_the_allowance(): void
	{
		// Arrange
		$this->setAi(['quickpost_member_limit' => '1']);
		$user = $this->user(7);
		$draft = fn (?string $parent) => $this->actingAs($user)->postJson(
			'/posts/create/quick/draft',
			array_filter(['text' => 'iPhone 13 blue with box ' . uniqid(), 'redraft_of' => $parent])
		);
		$root = $draft(null)->json('draft');

		// Act: each re-draft starts from the previous one
		$second = $draft($root)->assertJson(['ok' => true])->json('draft');
		$third = $draft($second)->assertJson(['ok' => true])->json('draft');
		$fourth = $draft($third);

		// Assert
		$fourth->assertStatus(429)->assertJson(['reason' => 'limit']);
		$this->assertSame(1, PostDraft::query()->where('counted', true)->count());
	}

	public function test_free_redrafts_of_siblings_share_one_allowance(): void
	{
		// Arrange
		$this->setAi(['quickpost_member_limit' => '1']);
		$user = $this->user(7);
		$root = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->json('draft');
		$first = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue, box', 'redraft_of' => $root])->json('draft');

		// Act: re-draft the first re-draft, then the root again
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13, box', 'redraft_of' => $first])->assertJson(['ok' => true]);
		$response = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 box', 'redraft_of' => $root]);

		// Assert
		$response->assertStatus(429);
	}

	public function test_a_failed_draft_gives_no_free_redrafts(): void
	{
		// Arrange
		$this->setAi(['quickpost_member_limit' => '1']);
		$user = $this->user(7);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);
		$failed = PostDraft::query()->create([
			'token' => str_repeat('f', 32), 'user_id' => $user->id, 'status' => PostDraft::FAILED, 'counted' => false,
		]);

		// Act
		$response = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 red', 'redraft_of' => $failed->token]);

		// Assert
		$response->assertStatus(429);
	}

	public function test_the_purge_keeps_counted_uses_so_week_limits_hold(): void
	{
		// Arrange
		$this->setAi(['quickpost_member_limit' => '1', 'quickpost_member_period' => 'month']);
		$user = $this->user(7);
		$this->travelTo(now()->startOfMonth()->addDay());
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);

		// Act: three days later the draft has expired and the hourly purge runs
		$this->travel(3)->days();
		$this->artisan('ai:quickpost-purge')->assertSuccessful();

		// Assert
		$kept = PostDraft::query()->where('counted', true)->sole();
		$this->assertNull($kept->input_text, 'the text is removed');
		$this->assertNull($kept->raw_output);
		$this->assertNull($kept->draft);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 red with box'])->assertStatus(429);
	}

	public function test_the_purge_deletes_counted_uses_once_no_period_can_need_them(): void
	{
		// Arrange
		$this->artisan('ai:quickpost-purge')->assertSuccessful();
		PostDraft::query()->create(['token' => str_repeat('a', 32), 'user_id' => 7, 'status' => PostDraft::DRAFTED, 'counted' => true, 'expires_at' => now()->subDays(40)]);
		PostDraft::query()->where('token', str_repeat('a', 32))->update(['created_at' => now()->subDays(40)]);

		// Act
		$this->artisan('ai:quickpost-purge')->assertSuccessful();

		// Assert
		$this->assertSame(0, PostDraft::query()->count());
	}

	public function test_the_daily_limit_resets_at_local_midnight(): void
	{
		// Arrange: a site in Dhaka (UTC+6)
		config(['app.timezone' => 'Asia/Dhaka']);
		date_default_timezone_set('Asia/Dhaka');
		$this->setAi(['quickpost_member_limit' => '1', 'quickpost_member_period' => 'day']);
		$user = $this->user(7);
		$this->travelTo(CarbonImmutable::parse('2026-11-19 21:00', 'Asia/Dhaka'));
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);

		// Act: half an hour after local midnight
		$this->travelTo(CarbonImmutable::parse('2026-11-20 00:30', 'Asia/Dhaka'));
		$response = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 red with box']);

		// Assert
		date_default_timezone_set('UTC');
		$response->assertJson(['ok' => true]);
	}

	public function test_a_user_override_applies_when_the_site_has_no_limit(): void
	{
		// Arrange
		$this->setAi(['quickpost_member_period' => 'none']);
		$user = $this->user(7);
		$user->forceFill(['ai_post_limit' => 1])->save();

		// Act
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);
		$response = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 red with box']);

		// Assert: the override counts per day
		$response->assertStatus(429)->assertJson(['limit' => ['period' => 'day']]);
	}
}
