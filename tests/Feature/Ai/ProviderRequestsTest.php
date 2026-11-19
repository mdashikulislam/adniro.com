<?php

namespace Tests\Feature\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\Setting;
use App\Services\Ai\AiImage;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\Ai\Contracts\AiClientContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
 * Runs the real Laravel AI SDK gateways with HTTP intercepted, to check what is actually
 * sent to each provider and how real provider answers are read.
 */

class ProviderRequestsTest extends TestCase
{
	use RefreshDatabase;
	
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
		Http::preventStrayRequests();
	}
	
	public function test_the_anthropic_request_carries_the_key_schema_cache_marker_options_and_image(): void
	{
		$model = $this->assign('anthropic', 'anthropic-key-1234', null, 'claude-opus-5-5', '{"output_config": {"effort": "low"}}');
		
		Http::fake([
			'api.anthropic.com/*' => Http::response($this->anthropicAnswer('end_turn', '{"verdict":"allow","categories":[]}')),
		]);
		
		$result = $this->client()->structured($this->request([$this->redSquare()]));
		
		$this->assertTrue($result->ok(), (string)$result->errorMessage);
		$this->assertSame(['verdict' => 'allow', 'categories' => []], $result->data);
		$this->assertSame(2200, $result->inputTokens); // The SDK counts cached tokens in the input total (1200 uncached + 1000 cached)
		$this->assertSame(1000, $result->cacheReadTokens);
		
		Http::assertSent(function (HttpRequest $request) use ($model) {
			$body = $request->data();
			$userContent = collect($body['messages'][0]['content'] ?? []);
			
			return str_ends_with($request->url(), '/v1/messages')
				&& $request->hasHeader('x-api-key', 'anthropic-key-1234')
				&& $body['model'] === 'claude-opus-5-5'
				&& $body['max_tokens'] === 500
				&& ($body['system'][0]['cache_control']['type'] ?? null) === 'ephemeral'
				&& ($body['output_config']['format']['type'] ?? null) === 'json_schema'
				&& ($body['output_config']['format']['schema']['additionalProperties'] ?? null) === false
				&& ($body['output_config']['effort'] ?? null) === 'low'
				&& !isset($body['thinking'], $body['temperature'])
				&& $userContent->contains(fn ($block) => ($block['type'] ?? null) === 'image'
					&& ($block['source']['media_type'] ?? null) === 'image/jpeg');
		});
	}
	
	public function test_an_anthropic_refusal_is_a_refusal_and_does_not_fall_back(): void
	{
		$this->assign('anthropic', 'anthropic-key-1234', null, 'claude-opus-5-5');
		
		Http::fake([
			'api.anthropic.com/*' => Http::response($this->anthropicAnswer('refusal', '')),
		]);
		
		$result = $this->client()->structured($this->request());
		
		$this->assertSame(AiResult::REFUSAL, $result->status);
		Http::assertSentCount(1);
	}
	
	public function test_an_anthropic_server_error_is_reported_as_an_error(): void
	{
		$this->assign('anthropic', 'anthropic-key-1234', null, 'claude-opus-5-5');
		
		Http::fake([
			'api.anthropic.com/*' => Http::response(['type' => 'error', 'error' => ['type' => 'invalid_request_error', 'message' => 'model: not found']], 404),
		]);
		
		$result = $this->client()->structured($this->request());
		
		$this->assertSame(AiResult::ERROR, $result->status);
		$this->assertNotEmpty($result->errorType);
	}
	
	public function test_an_openai_compatible_provider_uses_its_custom_url_and_reads_its_answer(): void
	{
		$this->assign('openai-compatible', 'gateway-key-5678', 'https://llm-gateway.example.test/v1', 'vendor/vision-model');
		
		Http::fake([
			'llm-gateway.example.test/*' => Http::response([
				'id'      => 'chatcmpl-1',
				'object'  => 'chat.completion',
				'model'   => 'vendor/vision-model',
				'choices' => [[
					'index'         => 0,
					'finish_reason' => 'stop',
					'message'       => ['role' => 'assistant', 'content' => '{"verdict":"reject","categories":["scam_fraud"]}'],
				]],
				'usage'   => ['prompt_tokens' => 900, 'completion_tokens' => 40, 'prompt_tokens_details' => ['cached_tokens' => 600]],
			]),
		]);
		
		$result = $this->client()->structured($this->request([$this->redSquare()]));
		
		$this->assertTrue($result->ok(), (string)$result->errorMessage);
		$this->assertSame(['verdict' => 'reject', 'categories' => ['scam_fraud']], $result->data);
		$this->assertSame(600, $result->cacheReadTokens);
		
		Http::assertSent(function (HttpRequest $request) {
			$body = $request->data();
			$content = json_encode($body['messages'] ?? []);
			
			return str_starts_with($request->url(), 'https://llm-gateway.example.test/v1/chat/completions')
				&& $request->hasHeader('Authorization', 'Bearer gateway-key-5678')
				&& $body['model'] === 'vendor/vision-model'
				&& ($body['response_format']['type'] ?? null) === 'json_schema'
				&& str_contains($content, 'data:image\/jpeg;base64,');
		});
	}
	
	public function test_a_content_filter_stop_from_an_openai_compatible_provider_is_a_refusal(): void
	{
		$this->assign('openai-compatible', 'gateway-key-5678', 'https://llm-gateway.example.test/v1', 'vendor/vision-model');
		
		Http::fake([
			'llm-gateway.example.test/*' => Http::response([
				'id'      => 'chatcmpl-2',
				'model'   => 'vendor/vision-model',
				'choices' => [['index' => 0, 'finish_reason' => 'content_filter', 'message' => ['role' => 'assistant', 'content' => '']]],
				'usage'   => ['prompt_tokens' => 10, 'completion_tokens' => 0],
			]),
		]);
		
		$this->assertSame(AiResult::REFUSAL, $this->client()->structured($this->request())->status);
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
	
	private function request(array $images = []): AiRequest
	{
		return new AiRequest(
			feature: 'moderation',
			instructions: 'Classify the listing.',
			prompt: '<listing>Used bicycle, good condition</listing>',
			schema: $this->schema,
			images: $images,
			maxTokens: 500,
		);
	}
	
	private function assign(string $driver, string $key, ?string $url, string $modelId, ?string $options = null): AiModel
	{
		$provider = AiProvider::query()->create(['name' => $driver, 'driver' => $driver, 'api_key' => $key, 'base_url' => $url, 'active' => true]);
		$model = AiModel::query()->create([
			'ai_provider_id'             => $provider->id,
			'model_id'                   => $modelId,
			'supports_vision'            => true,
			'supports_structured_output' => true,
			'options'                    => $options,
			'active'                     => true,
		]);
		$model->forceFill(['last_test_ok' => true])->save();
		
		Setting::query()->updateOrCreate(['name' => 'ai'], [
			'label'        => 'AI',
			'active'       => 1,
			'field_values' => ['enabled' => '1', 'moderation_model_id' => (string)$model->id, 'daily_budget_usd' => '0', 'daily_call_limit' => '0'],
		]);
		
		return $model;
	}
	
	private function anthropicAnswer(string $stopReason, string $text): array
	{
		return [
			'id'          => 'msg_test',
			'type'        => 'message',
			'role'        => 'assistant',
			'model'       => 'claude-opus-5-5',
			'content'     => $text === '' ? [] : [['type' => 'text', 'text' => $text]],
			'stop_reason' => $stopReason,
			'usage'       => ['input_tokens' => 1200, 'output_tokens' => 50, 'cache_read_input_tokens' => 1000, 'cache_creation_input_tokens' => 0],
		];
	}
	
	private function redSquare()
	{
		$gd = imagecreatetruecolor(1600, 1200);
		imagefill($gd, 0, 0, imagecolorallocate($gd, 200, 30, 30));
		ob_start();
		imagepng($gd);
		$png = (string)ob_get_clean();
		imagedestroy($gd);
		
		return AiImage::fromBinary($png);
	}
}
