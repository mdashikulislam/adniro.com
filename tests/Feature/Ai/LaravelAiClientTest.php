<?php

namespace Tests\Feature\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiUsageLog;
use App\Models\Setting;
use App\Services\Ai\Agents\StructuredTaskAgent;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\Ai\Contracts\AiClientContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use RuntimeException;
use Tests\TestCase;

class LaravelAiClientTest extends TestCase
{
	use RefreshDatabase;
	
	private AiModel $primary;
	private AiModel $fallback;
	
	private array $schema = [
		'type'       => 'object',
		'required'   => ['verdict', 'categories'],
		'properties' => [
			'verdict'    => ['type' => 'string', 'enum' => ['allow', 'reject']],
			'categories' => ['type' => 'array', 'items' => ['type' => 'string']],
		],
	];
	
	protected function setUp(): void
	{
		parent::setUp();
		
		Cache::flush();
		
		$provider = AiProvider::query()->create([
			'name'    => 'Test provider',
			'driver'  => 'anthropic',
			'api_key' => 'test-key-1234',
			'active'  => true,
		]);
		
		$this->primary = $this->makeModel($provider, 'primary-model', 4.0, 20.0);
		$this->fallback = $this->makeModel($provider, 'fallback-model', 1.0, 5.0);
		
		$this->setAi([
			'enabled'                      => '1',
			'moderation_model_id'          => (string)$this->primary->id,
			'moderation_fallback_model_id' => (string)$this->fallback->id,
			'daily_budget_usd'             => '5',
			'daily_call_limit'             => '500',
		]);
	}
	
	public function test_a_valid_answer_is_returned_and_logged_with_tokens_and_cost(): void
	{
		StructuredTaskAgent::fake([
			$this->structured(['verdict' => 'allow', 'categories' => []], 1000, 200),
		]);
		
		$result = $this->client()->structured($this->request());
		
		$this->assertTrue($result->ok());
		$this->assertSame(['verdict' => 'allow', 'categories' => []], $result->data);
		$this->assertSame('primary-model', $result->model);
		$this->assertFalse($result->wasFallback);
		$this->assertSame(1000, $result->inputTokens);
		$this->assertEqualsWithDelta(0.008, $result->costUsd, 0.000001); // 1000 * $4/M + 200 * $20/M
		
		$log = AiUsageLog::query()->sole();
		$this->assertSame('ok', $log->status);
		$this->assertSame('moderation', $log->feature);
		$this->assertSame($this->primary->id, $log->ai_model_id);
		$this->assertSame(1000, (int)$log->input_tokens);
	}
	
	public function test_nothing_is_sent_when_ai_is_switched_off(): void
	{
		StructuredTaskAgent::fake()->preventStrayPrompts();
		$this->setAi(['enabled' => '0', 'moderation_model_id' => (string)$this->primary->id]);
		
		$result = $this->client()->structured($this->request());
		
		$this->assertSame(AiResult::DISABLED, $result->status);
		$this->assertSame(0, AiUsageLog::query()->count());
	}
	
	public function test_a_feature_without_a_usable_model_is_off(): void
	{
		StructuredTaskAgent::fake()->preventStrayPrompts();
		$this->primary->update(['active' => false]);
		$this->fallback->forceFill(['last_test_ok' => false])->save();
		
		$result = $this->client()->structured($this->request());
		
		$this->assertSame(AiResult::DISABLED, $result->status);
		$this->assertFalse($this->client()->enabled('moderation'));
	}
	
	public function test_an_invalid_answer_is_retried_once_then_the_fallback_model_is_used(): void
	{
		StructuredTaskAgent::fake([
			['verdict' => 'maybe', 'categories' => []],
			['verdict' => 'maybe', 'categories' => []],
			['verdict' => 'reject', 'categories' => ['scam_fraud']],
		]);
		
		$result = $this->client()->structured($this->request());
		
		$this->assertTrue($result->ok());
		$this->assertTrue($result->wasFallback);
		$this->assertSame('fallback-model', $result->model);
		$this->assertSame(['invalid', 'invalid', 'ok'], AiUsageLog::query()->orderBy('id')->pluck('status')->all());
	}
	
	public function test_a_provider_error_falls_back_to_the_second_model(): void
	{
		// The fake does not advance after a throwing response, so answer by model instead.
		StructuredTaskAgent::fake(fn ($prompt, $attachments, $provider, $model) => $model === 'primary-model'
			? throw new RuntimeException('Upstream overloaded')
			: ['verdict' => 'allow', 'categories' => []]);
		
		$result = $this->client()->structured($this->request());
		
		$this->assertTrue($result->ok());
		$this->assertTrue($result->wasFallback);
		$this->assertSame(['error', 'ok'], AiUsageLog::query()->orderBy('id')->pluck('status')->all());
		$this->assertSame('Upstream overloaded', AiUsageLog::query()->orderBy('id')->first()->error_message);
	}
	
	public function test_when_every_model_fails_the_last_error_is_returned(): void
	{
		StructuredTaskAgent::fake(fn ($prompt, $attachments, $provider, $model) => throw new RuntimeException(
			$model === 'primary-model' ? 'down' : 'also down'
		));
		
		$result = $this->client()->structured($this->request());
		
		$this->assertSame(AiResult::ERROR, $result->status);
		$this->assertSame('also down', $result->errorMessage);
	}
	
	public function test_the_daily_budget_stops_calls_before_they_reach_the_provider(): void
	{
		StructuredTaskAgent::fake()->preventStrayPrompts();
		$this->setAi([
			'enabled'             => '1',
			'moderation_model_id' => (string)$this->primary->id,
			'daily_budget_usd'    => '1',
			'daily_call_limit'    => '0',
		]);
		AiUsageLog::query()->create(['feature' => 'moderation', 'status' => 'ok', 'cost_usd' => 1.5, 'created_at' => now()]);
		
		$result = $this->client()->structured($this->request());
		
		$this->assertSame(AiResult::CAPPED, $result->status);
		$this->assertSame('daily_budget', $result->errorType);
		$this->assertSame('capped', AiUsageLog::query()->latest('id')->first()->status);
	}
	
	public function test_the_daily_call_limit_stops_calls(): void
	{
		StructuredTaskAgent::fake([['verdict' => 'allow', 'categories' => []]]);
		$this->setAi([
			'enabled'             => '1',
			'moderation_model_id' => (string)$this->primary->id,
			'daily_budget_usd'    => '0',
			'daily_call_limit'    => '1',
		]);
		
		$this->assertTrue($this->client()->structured($this->request())->ok());
		$this->assertSame(AiResult::CAPPED, $this->client()->structured($this->request())->status);
	}
	
	public function test_the_admin_test_works_while_ai_is_off_and_records_the_result(): void
	{
		$this->setAi(['enabled' => '0']);
		$this->primary->forceFill(['last_test_ok' => null])->save();
		StructuredTaskAgent::fake([['ok' => true, 'answer' => 'pong', 'colour' => 'red']]);
		
		$result = $this->client()->testModel($this->primary);
		
		$this->assertTrue($result->ok());
		$this->assertTrue($this->primary->fresh()->last_test_ok);
		$this->assertNotNull($this->primary->fresh()->last_tested_at);
		StructuredTaskAgent::assertPrompted(fn ($prompt) => $prompt->attachments->count() === 1);
	}
	
	public function test_a_failed_admin_test_stores_the_reason(): void
	{
		StructuredTaskAgent::fake([fn () => throw new RuntimeException('Invalid model name')]);
		
		$result = $this->client()->testModel($this->primary);
		
		$this->assertFalse($result->ok());
		$this->assertFalse($this->primary->fresh()->last_test_ok);
		$this->assertStringContainsString('Invalid model name', $this->primary->fresh()->last_test_error);
	}
	
	public function test_the_api_key_is_encrypted_at_rest_and_only_shown_masked(): void
	{
		$raw = DB::table('ai_providers')->value('api_key');
		
		$this->assertNotSame('test-key-1234', $raw);
		$this->assertStringNotContainsString('test-key-1234', $raw);
		$this->assertSame('test-key-1234', AiProvider::query()->first()->api_key);
		$this->assertSame('•••• 1234', AiProvider::query()->first()->maskedApiKey());
		$this->assertArrayNotHasKey('api_key', AiProvider::query()->first()->toArray());
	}
	
	public function test_image_features_only_accept_tested_models_that_understand_images(): void
	{
		$this->assertTrue($this->primary->isEligibleFor('moderation'));
		
		$this->primary->update(['supports_vision' => false]);
		$this->assertFalse($this->primary->fresh()->isEligibleFor('moderation'));
		
		$this->fallback->forceFill(['last_test_ok' => null])->save();
		$this->assertFalse($this->fallback->fresh()->isEligibleFor('moderation'));
	}
	
	public function test_errors_never_store_something_that_looks_like_a_key(): void
	{
		StructuredTaskAgent::fake(fn ($prompt, $attachments, $provider, $model) => throw new RuntimeException(
			$model === 'primary-model' ? 'Unauthorized for key sk-ant-abcdefghijklmnop' : 'Bearer abc.def.ghi rejected'
		));
		
		$this->client()->structured($this->request());
		
		$this->assertSame(2, AiUsageLog::query()->count());
		$messages = AiUsageLog::query()->pluck('error_message')->implode(' ');
		$this->assertStringNotContainsString('abcdefghijklmnop', $messages);
		$this->assertStringNotContainsString('abc.def.ghi', $messages);
	}
	
	/*
	|--------------------------------------------------------------------------
	| Helpers
	|--------------------------------------------------------------------------
	*/
	
	private function client(): AiClientContract
	{
		return app(AiClientContract::class);
	}
	
	private function request(): AiRequest
	{
		return new AiRequest(
			feature: 'moderation',
			instructions: 'Classify the listing.',
			prompt: '<listing>Used bicycle, good condition</listing>',
			schema: $this->schema,
			maxTokens: 500,
			subjectType: 'post',
			subjectId: 1,
		);
	}
	
	private function makeModel(AiProvider $provider, string $modelId, float $in, float $out): AiModel
	{
		$model = AiModel::query()->create([
			'ai_provider_id'             => $provider->id,
			'model_id'                   => $modelId,
			'supports_vision'            => true,
			'supports_structured_output' => true,
			'input_price_per_mtok'       => $in,
			'output_price_per_mtok'      => $out,
			'active'                     => true,
		]);
		$model->forceFill(['last_test_ok' => true, 'last_tested_at' => now()])->save();
		
		return $model->fresh('provider');
	}
	
	private function setAi(array $values): void
	{
		Setting::query()->updateOrCreate(
			['name' => 'ai'],
			['label' => 'AI', 'active' => 1, 'field_values' => $values]
		);
	}
	
	private function structured(array $data, int $in, int $out): StructuredTextResponse
	{
		return new StructuredTextResponse($data, json_encode($data), new TextUsage($in, $out), new Meta('anthropic', 'primary-model'));
	}
}
