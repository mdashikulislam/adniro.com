<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use App\Services\Ai\Support\KnownModels;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
 * Lists the models a provider offers, with the saved API key, for the "Load models" button on
 * Admin > AI Models (adniro:ai). Each model comes back as:
 *   ['id', 'label', 'vision', 'structured', 'transcription', 'input_price', 'output_price']
 * where null means "unknown": the form then keeps what the admin typed.
 */

final class ProviderModelCatalog
{
	private const TIMEOUT = 15; // seconds
	private const MAX_PAGES = 10;

	/** The URLs the Laravel AI SDK uses when a provider has no custom URL. */
	private const DEFAULT_URLS = [
		'anthropic'  => 'https://api.anthropic.com/v1',
		'openai'     => 'https://api.openai.com/v1',
		'gemini'     => 'https://generativelanguage.googleapis.com/v1beta',
		'mistral'    => 'https://api.mistral.ai/v1',
		'groq'       => 'https://api.groq.com/openai/v1',
		'xai'        => 'https://api.x.ai/v1',
		'deepseek'   => 'https://api.deepseek.com/v1',
		'openrouter' => 'https://openrouter.ai/api/v1',
		'ollama'     => 'http://localhost:11434',
	];

	/** Models that cannot write text, left out of OpenAI-style lists. */
	private const NOT_TEXT = '/(embed|moderation|tts|dall-e|davinci|babbage|^gpt-image|-image-|realtime|search)/i';

	/** ElevenLabs has no model list for speech-to-text. */
	private const ELEVEN_MODELS = [['id' => 'scribe_v1', 'label' => 'Scribe v1']];

	/**
	 * @return list<array{id: string, label: string, vision: ?bool, structured: ?bool, transcription: ?bool, input_price: ?float, output_price: ?float}>
	 *
	 * @throws ModelCatalogException
	 */
	public function list(AiProvider $provider): array
	{
		$driver = (string)$provider->driver;
		$key = $provider->resolvedApiKey();
		if ($key === '' && !in_array($driver, ['ollama', 'openai-compatible'], true)) {
			throw new ModelCatalogException(trans('ai.catalog_no_key'));
		}

		$base = rtrim((string)($provider->base_url ?: (self::DEFAULT_URLS[$driver] ?? '')), '/');
		if ($base === '' && $driver !== 'eleven') {
			throw new ModelCatalogException(trans('ai.catalog_no_url'));
		}

		$models = match ($driver) {
			'anthropic'  => $this->anthropic($base, $key),
			'gemini'     => $this->gemini($base, $key),
			'mistral'    => $this->mistral($base, $key),
			'openrouter' => $this->openRouter($base, $key),
			'ollama'     => $this->ollama($base, $key),
			'eleven'     => array_map(fn (array $model) => $this->entry($model['id'], $model['label'], transcription: true), self::ELEVEN_MODELS),
			default      => $this->openAiStyle($base, $key),
		};

		$models = array_map(fn (array $model) => KnownModels::complete($model), $models);

		return array_values(collect($models)->unique('id')->all());
	}

	private function anthropic(string $base, string $key): array
	{
		$http = $this->http()->withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01']);
		$models = [];
		$afterId = null;

		for ($page = 0; $page < self::MAX_PAGES; $page++) {
			$query = ['limit' => 1000] + ($afterId ? ['after_id' => $afterId] : []);
			$body = $this->get($http, $base . '/models', $query);

			foreach ((array)($body['data'] ?? []) as $item) {
				$caps = (array)($item['capabilities'] ?? []);
				$models[] = $this->entry(
					(string)$item['id'],
					(string)($item['display_name'] ?? $item['id']),
					vision: isset($caps['image_input']['supported']) ? (bool)$caps['image_input']['supported'] : null,
					structured: isset($caps['structured_outputs']['supported']) ? (bool)$caps['structured_outputs']['supported'] : null,
				);
			}

			$afterId = $body['last_id'] ?? null;
			if (empty($body['has_more']) || empty($afterId)) {
				break;
			}
		}

		return $models;
	}

	private function gemini(string $base, string $key): array
	{
		$http = $this->http()->withHeaders(['x-goog-api-key' => $key]);
		$models = [];
		$token = null;

		for ($page = 0; $page < self::MAX_PAGES; $page++) {
			$query = ['pageSize' => 1000] + ($token ? ['pageToken' => $token] : []);
			$body = $this->get($http, $base . '/models', $query);

			foreach ((array)($body['models'] ?? []) as $item) {
				if (!in_array('generateContent', (array)($item['supportedGenerationMethods'] ?? []), true)) {
					continue;
				}
				$id = preg_replace('#^models/#', '', (string)$item['name']);
				$models[] = $this->entry($id, (string)($item['displayName'] ?? $id));
			}

			$token = $body['nextPageToken'] ?? null;
			if (empty($token)) {
				break;
			}
		}

		return $this->sorted($models);
	}

	private function mistral(string $base, string $key): array
	{
		$body = $this->get($this->http()->withToken($key), $base . '/models');
		$models = [];

		foreach ((array)($body['data'] ?? []) as $item) {
			$caps = (array)($item['capabilities'] ?? []);
			$isTranscription = !empty($caps['audio_transcription']);
			if (empty($caps['completion_chat']) && !$isTranscription) {
				continue;
			}
			$models[] = $this->entry(
				(string)$item['id'],
				(string)($item['name'] ?? $item['id']),
				vision: array_key_exists('vision', $caps) ? (bool)$caps['vision'] : null,
				transcription: $isTranscription,
			);
		}

		return $this->sorted($models);
	}

	private function openRouter(string $base, string $key): array
	{
		$body = $this->get($this->http()->withToken($key), $base . '/models');
		$models = [];

		foreach ((array)($body['data'] ?? []) as $item) {
			$pricing = (array)($item['pricing'] ?? []);
			$parameters = (array)($item['supported_parameters'] ?? []);
			$models[] = $this->entry(
				(string)$item['id'],
				(string)($item['name'] ?? $item['id']),
				vision: isset($item['architecture']['input_modalities']) ? in_array('image', (array)$item['architecture']['input_modalities'], true) : null,
				structured: $parameters ? (in_array('structured_outputs', $parameters, true) || in_array('response_format', $parameters, true)) : null,
				inputPrice: $this->perMillion($pricing['prompt'] ?? null),
				outputPrice: $this->perMillion($pricing['completion'] ?? null),
			);
		}

		return $this->sorted($models);
	}

	private function ollama(string $base, string $key): array
	{
		$base = preg_replace('#/(v1|api)$#', '', $base);
		$http = $key !== '' ? $this->http()->withToken($key) : $this->http();
		$body = $this->get($http, $base . '/api/tags');

		$models = array_map(
			fn (array $item) => $this->entry((string)$item['name'], (string)$item['name']),
			array_filter((array)($body['models'] ?? []), fn ($item) => is_array($item) && !empty($item['name']))
		);

		return $this->sorted($models);
	}

	/** OpenAI, Groq, xAI, DeepSeek and OpenAI-compatible servers. */
	private function openAiStyle(string $base, string $key): array
	{
		$http = $key !== '' ? $this->http()->withToken($key) : $this->http();
		$body = $this->get($http, $base . '/models');
		$models = [];

		foreach ((array)($body['data'] ?? []) as $item) {
			$id = (string)($item['id'] ?? '');
			if ($id === '' || preg_match(self::NOT_TEXT, $id) === 1) {
				continue;
			}
			$models[] = $this->entry($id, $id);
		}

		return $this->sorted($models);
	}

	private function http(): PendingRequest
	{
		return Http::acceptJson()
			->timeout(self::TIMEOUT)
			->connectTimeout(5)
			->withOptions(['allow_redirects' => false]);
	}

	/**
	 * @throws ModelCatalogException
	 */
	private function get(PendingRequest $http, string $url, array $query = []): array
	{
		$host = (string)parse_url($url, PHP_URL_HOST);

		try {
			$response = $http->get($url, $query);
		} catch (ConnectionException $e) {
			Log::warning('AI model list: connection failed', ['host' => $host, 'error' => SecretRedactor::redact($e->getMessage())]);
			throw new ModelCatalogException(trans('ai.catalog_unreachable', ['host' => $host]));
		}

		if ($response->failed()) {
			throw new ModelCatalogException($this->failureMessage($response, $host));
		}

		$body = $response->json();
		if (!is_array($body)) {
			throw new ModelCatalogException(trans('ai.catalog_bad_answer', ['host' => $host]));
		}

		return $body;
	}

	private function failureMessage(Response $response, string $host): string
	{
		$status = $response->status();
		Log::warning('AI model list: provider error', ['host' => $host, 'status' => $status, 'body' => SecretRedactor::redact($response->body())]);

		if (in_array($status, [401, 403], true)) {
			return trans('ai.catalog_key_refused', ['host' => $host]);
		}

		$detail = $response->json('error.message') ?? $response->json('error') ?? $response->json('message') ?? '';
		$detail = is_string($detail) ? SecretRedactor::redact($detail) : '';

		return trans('ai.catalog_http_error', ['host' => $host, 'status' => $status])
			. ($detail !== '' ? ' ' . $detail : '');
	}

	private function entry(
		string $id,
		string $label,
		?bool $vision = null,
		?bool $structured = null,
		?bool $transcription = null,
		?float $inputPrice = null,
		?float $outputPrice = null,
	): array {
		return [
			'id'            => $id,
			'label'         => $label,
			'vision'        => $vision,
			'structured'    => $structured,
			'transcription' => $transcription,
			'input_price'   => $inputPrice,
			'output_price'  => $outputPrice,
		];
	}

	/** OpenRouter prices are dollars per token, as strings. */
	private function perMillion(mixed $perToken): ?float
	{
		if (!is_numeric($perToken) || (float)$perToken < 0) {
			return null;
		}

		return round((float)$perToken * 1_000_000, 4);
	}

	private function sorted(array $models): array
	{
		usort($models, fn (array $a, array $b) => strcmp($a['id'], $b['id']));

		return $models;
	}
}
