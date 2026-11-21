<?php

namespace Tests\Feature\Ai;

use App\Models\AiUsageLog;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiSpend;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
 * Phase 9: translations, the mobile API, the spend widget, the quick post evaluation and the
 * one-screen review (adniro:ai).
 */

class AiPolishTest extends TestCase
{
	use RefreshDatabase, QuickPostFixtures;
	
	private const USER_KEYS = '/^(mail_not_approved|sms_not|owner_|badge_|finish_|moderation_category_|qp_|box_|status_(approved|pending|needs_review|rejected|none))/';
	
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpQuickPost();
		config(['larapen.core.api.token' => 'test-api-token']);
	}
	
	// --- Translations ------------------------------------------------------
	
	public function test_every_active_language_has_all_user_texts_with_the_same_placeholders(): void
	{
		$en = require lang_path('en/ai.php');
		$keys = array_values(array_filter(array_keys($en), fn ($key) => preg_match(self::USER_KEYS, $key)));
		$this->assertGreaterThan(80, count($keys));
		
		foreach (['fr', 'es', 'ar', 'de', 'it', 'ru', 'hi', 'bn'] as $code) {
			$lang = require lang_path($code . '/ai.php');
			$this->assertSame([], array_values(array_diff($keys, array_keys($lang))), $code . ': missing texts');
			
			foreach ($keys as $key) {
				preg_match_all('/:[a-zA-Z]+/', $en[$key], $a);
				preg_match_all('/:[a-zA-Z]+/', $lang[$key], $b);
				$this->assertEqualsCanonicalizing(array_unique($a[0]), array_unique($b[0]), "{$code}.{$key}: placeholders");
				$this->assertSame(substr_count($en[$key], '|'), substr_count($lang[$key], '|'), "{$code}.{$key}: plural parts");
			}
		}
	}
	
	public function test_texts_follow_the_locale_and_admin_texts_fall_back_to_english(): void
	{
		app()->setLocale('bn');
		$this->assertSame('অনুমোদিত নয়', trans('ai.badge_rejected'));
		$this->assertStringContainsString('5-এর মধ্যে 3টি', trans_choice('ai.qp_left_day', 3, ['left' => 3, 'limit' => 5]));
		$this->assertSame('General', trans('ai.setting_tab_general'));
	}
	
	// --- Mobile API --------------------------------------------------------
	
	public function test_the_api_reports_the_limit(): void
	{
		$this->actingAs($this->user(7), 'sanctum')
			->getJson('/api/quickpost/limit', ['X-AppApiToken' => 'test-api-token'])
			->assertOk()
			->assertJson(['success' => true, 'result' => ['available' => true, 'limit' => ['limit' => 20, 'remaining' => 20, 'period' => 'day']]]);
	}
	
	public function test_the_api_drafts_from_text_and_photos_in_post_field_names(): void
	{
		$this->ai->answer($this->draftAnswer());
		
		$response = $this->actingAs($this->user(7), 'sanctum')->post('/api/quickpost/drafts', [
			'text'   => 'iPhone 13 blue with box',
			'photos' => [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.jpg', 800, 600)],
			'lat'    => 39.80,
			'lon'    => -89.60,
		], ['Accept' => 'application/json', 'X-AppApiToken' => 'test-api-token']);
		
		$response->assertOk()->assertJson([
			'success' => true,
			'result'  => [
				'post' => ['category_id' => 2, 'title' => 'Apple iPhone 13 128GB Blue', 'price' => 420, 'city_id' => 600, 'country_code' => 'US', 'cf' => ['10' => 101, '11' => '128']],
				'city' => ['id' => 600],
			],
			'extra'   => ['limit' => ['remaining' => 19]],
		]);
		$this->assertCount(2, $this->ai->lastRequest->images);
		$this->assertNull(session('postInput'), 'the API never writes the browser session');
	}
	
	public function test_the_api_answers_429_with_the_reset_time_at_the_limit(): void
	{
		$this->setAi(['quickpost_member_limit' => '1']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		$headers = ['Accept' => 'application/json', 'X-AppApiToken' => 'test-api-token'];
		
		$this->actingAs($user, 'sanctum')->post('/api/quickpost/drafts', ['text' => 'iPhone 13 blue with box'], $headers)->assertOk();
		$response = $this->actingAs($user, 'sanctum')->post('/api/quickpost/drafts', ['text' => 'iPhone 12 blue with box'], $headers);
		
		$response->assertStatus(429)->assertJson(['success' => false, 'extra' => ['reason' => 'limit', 'limit' => ['remaining' => 0]]]);
		$this->assertNotEmpty($response->headers->get('Retry-After'));
		$this->assertNotNull($response->json('extra.limit.resets_at'));
	}
	
	public function test_the_api_refuses_when_quick_post_is_off_and_does_not_fill_a_session_on_failure(): void
	{
		$headers = ['Accept' => 'application/json', 'X-AppApiToken' => 'test-api-token'];
		
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR));
		$this->actingAs($this->user(7), 'sanctum')->post('/api/quickpost/drafts', ['text' => 'Grey sofa, three seats'], $headers)
			->assertOk()->assertJson(['success' => false, 'extra' => ['reason' => 'ai_unavailable']]);
		$this->assertNull(session('postInput'));
		
		$this->setAi(['quickpost_enabled' => '0']);
		$this->app->forgetInstance(QuickPostService::class);
		$this->post('/api/quickpost/drafts', ['text' => 'Grey sofa, three seats'], $headers)
			->assertForbidden()->assertJson(['success' => false, 'extra' => ['reason' => 'off']]);
	}
	
	// --- Spend widget ------------------------------------------------------
	
	public function test_the_spend_summary_and_widget(): void
	{
		$this->setAi(['daily_budget_usd' => '2']);
		AiUsageLog::query()->create(['feature' => 'moderation', 'status' => 'ok', 'cost_usd' => 0.5, 'created_at' => now()]);
		AiUsageLog::query()->create(['feature' => 'quickpost_draft', 'status' => 'ok', 'cost_usd' => 0.25, 'created_at' => now()]);
		AiUsageLog::query()->create(['feature' => 'quickpost_draft', 'status' => 'error', 'cost_usd' => 0, 'created_at' => now()]);
		AiUsageLog::query()->create(['feature' => 'moderation', 'status' => 'ok', 'cost_usd' => 1, 'created_at' => now()->subDays(40)]);
		
		$summary = app(AiSpend::class)->summary();
		
		$this->assertEqualsWithDelta(0.75, $summary['today_cost'], 0.0001);
		$this->assertSame(3, $summary['today_calls']);
		$this->assertSame(1, $summary['today_failed']);
		$this->assertSame(38.0, (float)$summary['budget_used']);
		$this->assertCount(7, $summary['days']);
		$this->assertSame(2, $summary['features']['quickpost_draft']['calls']);
		
		$this->assertSame('', trim(view('admin.ai.spend-widget')->render()), 'hidden from people who are not super admins');
		$this->actingAs($this->admin());
		$html = view('admin.ai.spend-widget')->render();
		$this->assertStringContainsString('$0.75', $html);
		$this->assertStringContainsString('38% of today', $html);
		$this->assertStringContainsString('AI listing assistant drafts', $html);
		$this->assertStringNotContainsStringIgnoringCase('quick post', $html);
	}
	
	// --- AI listing assistant evaluation and model list --------------------
	
	public function test_the_quickpost_evaluation_grades_categories_prices_and_non_listings(): void
	{
		$answer = function ($request) {
			$base = $this->draftAnswer();
			$base['price'] = str_contains($request->prompt, 'charger')
				? ['stated' => false, 'amount' => 0, 'currency' => '']
				: ['stated' => true, 'amount' => str_contains($request->prompt, '35000') || str_contains($request->prompt, '৩৫০০০') ? 35000 : 420, 'currency' => 'USD'];
			
			return new AiResult(AiResult::OK, $base);
		};
		$this->ai->respond($answer);
		$this->artisan('ai:listing-assistant-eval', ['model' => $this->model->id, '--only' => 'phone-'])
			->expectsOutputToContain('Partial run')
			->assertSuccessful();
		$this->assertSame(3, $this->ai->calls);
		
		// A model that invents a price fails
		$this->ai->answer($this->draftAnswer());
		$this->artisan('ai:quickpost-eval', ['model' => $this->model->id, '--only' => 'phone-noprice'])->assertFailed();
		
		// A full run that fails clears the model's pass
		$this->model->forceFill(['quickpost_eval_passed_at' => now()])->save();
		$this->artisan('ai:quickpost-eval', ['model' => $this->model->id])->assertFailed();
		$this->assertNull($this->model->fresh()->quickpost_eval_passed_at);
		
		$html = $this->model->fresh()->crudEvaluationsColumn();
		$this->assertStringContainsString('Listing assistant –', $html);
	}
	
	public function test_evaluations_stop_early_and_count_failed_calls_as_failures(): void
	{
		$noKey = fn () => new AiResult(AiResult::ERROR, errorType: 'missing_api_key', errorMessage: 'No API key');
		$this->model->forceFill(['moderation_eval_passed_at' => now(), 'quickpost_eval_passed_at' => now()])->save();
		
		$this->ai->respond($noKey);
		$this->artisan('ai:moderation-eval', ['model' => $this->model->id])
			->expectsOutputToContain('Stopped: the first 3 calls failed with "error: No API key"')
			->assertFailed();
		$this->assertSame(3, $this->ai->calls);
		$this->artisan('ai:quickpost-eval', ['model' => $this->model->id])
			->expectsOutputToContain('Stopped: the first 3 calls failed')
			->assertFailed();
		$this->assertNotNull($this->model->fresh()->moderation_eval_passed_at, 'an aborted run changes nothing');
		
		// Failures later in the run are failures, never "held" passes
		$calls = 0;
		$this->ai->respond(function () use (&$calls, $noKey) {
			return ++$calls <= 3
				? new AiResult(AiResult::OK, ['verdict' => 'reject', 'categories' => ['scam_fraud'], 'confidence' => 'high', 'user_reason' => '', 'internal_reason' => ''])
				: $noKey();
		});
		$this->artisan('ai:moderation-eval', ['model' => $this->model->id, '--only' => 'bad-'])
			->expectsOutputToContain('Failed calls')
			->assertFailed();
	}
	
	public function test_a_call_stopped_for_a_missing_key_does_not_use_the_daily_allowance(): void
	{
		$this->assertFalse((new AiResult(AiResult::ERROR, errorType: 'missing_api_key'))->reachedProvider());
		$this->assertTrue((new AiResult(AiResult::ERROR, errorType: 'RateLimited'))->reachedProvider());
		
		AiUsageLog::query()->create(['feature' => 'moderation', 'status' => 'error', 'error_type' => 'missing_api_key', 'created_at' => now()]);
		AiUsageLog::query()->create(['feature' => 'moderation', 'status' => 'ok', 'created_at' => now()]);
		\Illuminate\Support\Facades\Cache::flush();
		$this->setAi(['daily_call_limit' => '2']);
		
		$this->assertNull(app(\App\Services\Ai\AiBudget::class)->check(), 'only the call that went out counts');
	}
	
	// --- One-screen review -------------------------------------------------
	
}
