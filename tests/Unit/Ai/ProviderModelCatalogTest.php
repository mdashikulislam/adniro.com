<?php

namespace Tests\Unit\Ai;

use App\Models\AiProvider;
use App\Services\Ai\ModelCatalogException;
use App\Services\Ai\ProviderModelCatalog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
 * Listing a provider's models from its API, for the "Load models" button (adniro:ai).
 */

class ProviderModelCatalogTest extends TestCase
{
	private function provider(string $driver, ?string $key = 'test-key-123456789', ?string $url = null): AiProvider
	{
		return (new AiProvider())->forceFill(['id' => 7, 'name' => 'Test', 'driver' => $driver, 'api_key' => $key, 'base_url' => $url]);
	}

	private function byId(array $models): array
	{
		return collect($models)->keyBy('id')->all();
	}

	public function test_anthropic_models_are_listed_across_pages_with_capabilities_and_known_prices(): void
	{
		// Arrange
		Http::fake([
			'api.anthropic.com/v1/models?limit=1000' => Http::response([
				'data'     => [[
					'id'           => 'claude-opus-5-5',
					'display_name' => 'Claude Opus 5.5',
					'capabilities' => ['image_input' => ['supported' => true], 'structured_outputs' => ['supported' => true]],
				]],
				'has_more' => true,
				'last_id'  => 'claude-opus-5-5',
			]),
			'api.anthropic.com/v1/models?limit=1000&after_id=claude-opus-5-5' => Http::response([
				'data'     => [
					['id' => 'claude-haiku-4-5-20251001', 'display_name' => 'Claude Haiku 4.5', 'capabilities' => ['image_input' => ['supported' => true], 'structured_outputs' => ['supported' => false]]],
					['id' => 'claude-new-9', 'display_name' => 'Claude New 9'],
				],
				'has_more' => false,
			]),
		]);

		// Act
		$models = $this->byId(app(ProviderModelCatalog::class)->list($this->provider('anthropic')));

		// Assert
		$this->assertSame(['claude-opus-5-5', 'claude-haiku-4-5-20251001', 'claude-new-9'], array_keys($models));
		$this->assertSame('Claude Opus 5.5', $models['claude-opus-5-5']['label']);
		$this->assertTrue($models['claude-opus-5-5']['vision']);
		$this->assertTrue($models['claude-opus-5-5']['structured']);
		$this->assertSame(4.0, $models['claude-opus-5-5']['input_price']);
		$this->assertSame(20.0, $models['claude-opus-5-5']['output_price']);
		$this->assertFalse($models['claude-haiku-4-5-20251001']['structured'], 'the API answer wins over the table');
		$this->assertSame(1.0, $models['claude-haiku-4-5-20251001']['input_price'], 'a dated ID gets its family price');
		$this->assertNull($models['claude-new-9']['input_price'], 'no guessed price for an unknown model');
		$this->assertNull($models['claude-new-9']['vision']);
		Http::assertSent(fn (Request $request) => $request->hasHeader('x-api-key', 'test-key-123456789')
			&& $request->hasHeader('anthropic-version', '2023-06-01'));
	}

	public function test_openai_models_skip_embeddings_and_mark_transcription(): void
	{
		// Arrange
		Http::fake(['api.openai.com/v1/models' => Http::response(['data' => [
			['id' => 'text-embedding-3-small'],
			['id' => 'whisper-1'],
			['id' => 'gpt-5-mini'],
			['id' => 'gpt-4o-2024-08-06'],
			['id' => 'gpt-9-unreleased'],
		]])]);

		// Act
		$models = $this->byId(app(ProviderModelCatalog::class)->list($this->provider('openai')));

		// Assert
		$this->assertSame(['gpt-4o-2024-08-06', 'gpt-5-mini', 'gpt-9-unreleased', 'whisper-1'], array_keys($models), 'sorted, embeddings dropped');
		$this->assertSame(2.5, $models['gpt-4o-2024-08-06']['input_price'], 'a dated ID gets its family price');
		$this->assertNull($models['gpt-9-unreleased']['input_price'], 'no guessed price for an unknown model');
		$this->assertTrue($models['whisper-1']['transcription']);
		$this->assertFalse($models['whisper-1']['vision']);
		$this->assertTrue($models['gpt-5-mini']['vision']);
		$this->assertSame(0.25, $models['gpt-5-mini']['input_price']);
		$this->assertSame(2.0, $models['gpt-5-mini']['output_price']);
		$this->assertNull($models['whisper-1']['input_price'], 'priced per minute, not per token');
		Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-key-123456789'));
	}

	public function test_gemini_models_follow_the_page_token_and_keep_only_chat_models(): void
	{
		// Arrange
		Http::fake([
			'generativelanguage.googleapis.com/v1beta/models?pageSize=1000' => Http::response([
				'models'        => [
					['name' => 'models/gemini-3-pro', 'displayName' => 'Gemini 3 Pro', 'supportedGenerationMethods' => ['generateContent', 'countTokens']],
					['name' => 'models/text-embedding-004', 'displayName' => 'Embedding', 'supportedGenerationMethods' => ['embedContent']],
				],
				'nextPageToken' => 'abc',
			]),
			'generativelanguage.googleapis.com/v1beta/models?pageSize=1000&pageToken=abc' => Http::response([
				'models' => [['name' => 'models/gemini-3-flash', 'displayName' => 'Gemini 3 Flash', 'supportedGenerationMethods' => ['generateContent']]],
			]),
		]);

		// Act
		$models = $this->byId(app(ProviderModelCatalog::class)->list($this->provider('gemini')));

		// Assert
		$this->assertSame(['gemini-3-flash', 'gemini-3-pro'], array_keys($models));
		$this->assertSame('Gemini 3 Pro', $models['gemini-3-pro']['label']);
		$this->assertTrue($models['gemini-3-pro']['vision']);
		Http::assertSent(fn (Request $request) => $request->hasHeader('x-goog-api-key', 'test-key-123456789'));
	}

	public function test_openrouter_prices_are_converted_to_dollars_per_million_tokens(): void
	{
		// Arrange
		Http::fake(['openrouter.ai/api/v1/models' => Http::response(['data' => [[
			'id'                   => 'acme/model-1',
			'name'                 => 'Acme Model 1',
			'pricing'              => ['prompt' => '0.000003', 'completion' => '0.000015'],
			'architecture'         => ['input_modalities' => ['text', 'image']],
			'supported_parameters' => ['structured_outputs'],
		]]])]);

		// Act
		$model = app(ProviderModelCatalog::class)->list($this->provider('openrouter'))[0];

		// Assert
		$this->assertSame(3.0, $model['input_price']);
		$this->assertSame(15.0, $model['output_price']);
		$this->assertTrue($model['vision']);
		$this->assertTrue($model['structured']);
	}

	public function test_mistral_capabilities_come_from_the_api(): void
	{
		// Arrange
		Http::fake(['api.mistral.ai/v1/models' => Http::response(['data' => [
			['id' => 'pixtral-large', 'capabilities' => ['completion_chat' => true, 'vision' => true]],
			['id' => 'mistral-embed', 'capabilities' => ['completion_chat' => false]],
		]])]);

		// Act
		$models = $this->byId(app(ProviderModelCatalog::class)->list($this->provider('mistral')));

		// Assert
		$this->assertSame(['pixtral-large'], array_keys($models));
		$this->assertTrue($models['pixtral-large']['vision']);
	}

	public function test_a_custom_url_is_used_and_ollama_lists_its_tags(): void
	{
		// Arrange
		Http::fake([
			'llm.example.test/v1/models' => Http::response(['data' => [['id' => 'local-model']]]),
			'gpu.example.test:11434/api/tags' => Http::response(['models' => [['name' => 'llama4:8b']]]),
		]);
		$catalog = app(ProviderModelCatalog::class);

		// Act
		$compatible = $catalog->list($this->provider('openai-compatible', url: 'https://llm.example.test/v1/'));
		$ollama = $catalog->list($this->provider('ollama', key: null, url: 'http://gpu.example.test:11434'));

		// Assert
		$this->assertSame('local-model', $compatible[0]['id']);
		$this->assertSame('llama4:8b', $ollama[0]['id']);
	}

	public function test_a_refused_key_gives_a_clear_error_without_the_key(): void
	{
		// Arrange
		Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Incorrect API key provided: test-key-123456789']], 401)]);

		// Act
		try {
			app(ProviderModelCatalog::class)->list($this->provider('openai'));
			$this->fail('expected an exception');
		} catch (ModelCatalogException $e) {
			// Assert
			$this->assertStringContainsString('refused the API key', $e->getMessage());
			$this->assertStringNotContainsString('test-key-123456789', $e->getMessage());
		}
	}

	public function test_a_provider_without_a_key_is_not_called(): void
	{
		// Arrange
		Http::fake();

		// Act & Assert
		$this->expectException(ModelCatalogException::class);
		try {
			app(ProviderModelCatalog::class)->list($this->provider('anthropic', key: null));
		} finally {
			Http::assertNothingSent();
		}
	}
}
