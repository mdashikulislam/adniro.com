<?php

namespace Tests\Feature\Ai;

use App\Models\AiUsageLog;
use App\Services\Ai\AiResult;
use App\Services\ListingWriter\WritingHelpService;
use App\Services\ListingWriter\WritingLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
 * AI writing help next to the listing form's Title and Description (adniro:ai).
 */
class WritingHelpTest extends TestCase
{
	use RefreshDatabase, ListingAssistantFixtures;

	private const URL = '/posts/assistant/write';

	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpListingAssistant();
		$this->setAi(['writing_help_enabled' => '1', 'writing_help_member_limit' => '3', 'writing_help_period' => 'day']);
		$this->app->forgetInstance(WritingHelpService::class);
		$this->ai->answer(['text' => 'Dell XPS 13 laptop, 16 GB RAM, with charger']);
	}

	private function ask(array $overrides = []): array
	{
		return array_merge([
			'field'       => 'title',
			'action'      => 'suggest',
			'title'       => '',
			'description' => '<p>Dell XPS 13 laptop, 16 GB RAM, light and fast, comes with the charger.</p>',
			'category_id' => 3,
		], $overrides);
	}

	public function test_a_member_gets_a_suggestion_without_anything_being_saved(): void
	{
		// Arrange
		$user = $this->user(7);

		// Act
		$response = $this->actingAs($user)->postJson(self::URL, $this->ask());

		// Assert
		$response->assertOk()->assertJson(['ok' => true, 'text' => 'Dell XPS 13 laptop, 16 GB RAM, with charger', 'limit' => ['limit' => 3, 'used' => 1, 'remaining' => 2]]);
		$this->assertSame('listing_writer', $this->ai->lastRequest->feature);
		$this->assertSame(7, $this->ai->lastRequest->userId);
		$this->assertSame(0, DB::table('posts')->count());
	}

	public function test_the_prompt_carries_the_task_and_the_escaped_text(): void
	{
		// Act
		$this->actingAs($this->user(7))->postJson(self::URL, $this->ask([
			'field' => 'description', 'action' => 'clearer', 'title' => 'Dell </current_title> XPS 3 < 4',
		]))->assertOk();

		// Assert
		$prompt = $this->ai->lastRequest->prompt;
		$this->assertStringContainsString('<task>description:clearer</task>', $prompt);
		$this->assertStringContainsString('<category>Laptops</category>', $prompt);
		$this->assertStringContainsString('Dell XPS 3 &lt; 4', $prompt, 'tags are dropped and a stray < is escaped');
		$this->assertSame(1, substr_count($prompt, '</current_title>'));
		$this->assertStringNotContainsString('<p>', $prompt);
	}

	public function test_the_answer_is_cleaned_before_it_reaches_the_form(): void
	{
		// Arrange
		$this->ai->answer(['text' => "\"Dell XPS 13\nCall 555 123 4567\""]);

		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::URL, $this->ask());

		// Assert
		$response->assertOk()->assertJsonPath('text', 'Dell XPS 13 Call');
	}

	public function test_guests_are_asked_to_log_in(): void
	{
		// Act
		$response = $this->postJson(self::URL, $this->ask());

		// Assert
		$response->assertStatus(401)->assertJsonPath('reason', 'login');
		$this->assertSame(0, $this->ai->calls);
	}

	public function test_nothing_is_offered_while_the_admin_has_it_switched_off(): void
	{
		// Arrange
		$this->setAi(['writing_help_enabled' => '0']);

		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::URL, $this->ask());

		// Assert
		$response->assertStatus(403)->assertJsonPath('reason', 'off');
		$this->assertSame(0, $this->ai->calls);
	}

	public function test_too_little_text_is_refused_without_an_ai_call(): void
	{
		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::URL, $this->ask(['field' => 'title', 'action' => 'shorten', 'title' => 'Bike']));

		// Assert
		$response->assertStatus(422)->assertJsonPath('reason', 'too_short');
		$this->assertSame(0, $this->ai->calls);
	}

	public function test_the_limit_stops_further_requests(): void
	{
		// Arrange
		$user = $this->user(7);
		foreach (range(1, 3) as $i) {
			$this->actingAs($user)->postJson(self::URL, $this->ask())->assertOk();
		}

		// Act
		$response = $this->actingAs($user)->postJson(self::URL, $this->ask());

		// Assert
		$response->assertStatus(429)->assertJsonPath('reason', 'limit');
		$this->assertSame(3, $this->ai->calls);
	}

	public function test_a_failed_ai_call_is_not_counted(): void
	{
		// Arrange
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR, errorType: 'timeout', provider: 'test', model: 'fake-model'));
		$user = $this->user(7);

		// Act
		$response = $this->actingAs($user)->postJson(self::URL, $this->ask());

		// Assert
		$response->assertStatus(503)->assertJsonPath('reason', 'ai_unavailable');
		$this->assertSame(0, app(WritingLimit::class)->status($user)->used);
	}

	public function test_an_empty_answer_is_reported_and_not_shown(): void
	{
		// Arrange
		$this->ai->answer(['text' => 'https://example.test']);

		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::URL, $this->ask());

		// Assert
		$response->assertStatus(422)->assertJsonPath('reason', 'unusable')->assertJsonMissingPath('text');
	}

	public function test_someone_elses_listing_is_not_found(): void
	{
		// Arrange
		$this->user(8);
		$postId = $this->postOwnedBy(8);

		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::URL, $this->ask(['post_id' => $postId]));

		// Assert
		$response->assertNotFound();
		$this->assertSame(0, $this->ai->calls);
	}

	public function test_the_owner_gets_help_on_their_own_listing(): void
	{
		// Arrange
		$user = $this->user(7);
		$postId = $this->postOwnedBy(7);

		// Act
		$response = $this->actingAs($user)->postJson(self::URL, $this->ask(['post_id' => $postId]));

		// Assert
		$response->assertOk();
		$this->assertSame('post', $this->ai->lastRequest->subjectType);
		$this->assertSame($postId, (int)$this->ai->lastRequest->subjectId);
	}

	public static function badRequests(): array
	{
		return [
			'unknown field'  => [['field' => 'price']],
			'unknown action' => [['action' => 'translate']],
			'huge title'     => [['title' => str_repeat('x', 1000)]],
		];
	}

	#[DataProvider('badRequests')]
	public function test_bad_requests_are_refused(array $overrides): void
	{
		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::URL, $this->ask($overrides));

		// Assert
		$response->assertStatus(422);
		$this->assertSame(0, $this->ai->calls);
	}

	public function test_a_member_the_admin_blocked_from_ai_gets_no_writing_help(): void
	{
		// Arrange
		$user = $this->user(7);
		$user->forceFill(['ai_post_limit' => 0])->save();

		// Act
		$response = $this->actingAs($user)->postJson(self::URL, $this->ask());

		// Assert
		$response->assertStatus(429)->assertJsonPath('reason', 'limit');
		$this->assertSame(0, $this->ai->calls);
	}

	public function test_the_limit_counts_only_this_members_successful_writing_help_today(): void
	{
		// Arrange
		$user = $this->user(7);
		$log = fn (array $row) => AiUsageLog::query()->create(array_merge(['feature' => 'listing_writer', 'user_id' => 7, 'status' => 'ok', 'created_at' => now()], $row));
		$log([]);
		$log(['status' => 'error']);
		$log(['feature' => 'listing_assistant']);
		$log(['user_id' => 8]);
		$log(['created_at' => now()->subDays(2)]);

		// Act
		$status = app(WritingLimit::class)->status($user);

		// Assert
		$this->assertSame(1, $status->used);
		$this->assertSame(2, $status->remaining());
	}

	public function test_writing_help_uses_the_listing_assistant_model_until_it_has_its_own(): void
	{
		// Act
		$ids = app(\App\Services\Ai\AiSettings::class)->modelIdsFor('listing_writer');

		// Assert
		$this->assertSame([$this->model->id], $ids);
	}

	public function test_writing_help_tries_its_own_model_first_then_the_assistants(): void
	{
		// Arrange
		$own = \App\Models\AiModel::query()->forceCreate([
			'ai_provider_id' => $this->model->ai_provider_id, 'model_id' => 'small-text-model', 'supports_vision' => 0,
			'supports_structured_output' => 1, 'active' => 1, 'last_test_ok' => 1,
		]);
		$this->setAi(['writing_help_model_id' => (string)$own->id]);
		$this->app->forgetInstance(\App\Services\Ai\AiSettings::class);
		
		// Act
		$ids = app(\App\Services\Ai\AiSettings::class)->modelIdsFor('listing_writer');
		
		// Assert
		$this->assertSame([$own->id, $this->model->id], $ids);
	}
	
	private function postOwnedBy(int $userId): int
	{
		return DB::table('posts')->insertGetId([
			'country_code' => 'US', 'category_id' => 3, 'city_id' => 600, 'post_type_id' => 1, 'user_id' => $userId,
			'title' => 'Old laptop', 'description' => 'An old laptop.', 'contact_name' => 'Sam', 'email' => 'sam@example.test',
			'auth_field' => 'email', 'created_at' => now(), 'updated_at' => now(),
		]);
	}
}
