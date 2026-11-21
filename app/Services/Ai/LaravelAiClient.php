<?php

namespace App\Services\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiUsageLog;
use App\Services\Ai\Agents\StructuredTaskAgent;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\Support\SchemaRules;
use Laravel\Ai\Ai;
use Laravel\Ai\Transcription;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\FinishReason;
use Throwable;

/*
 * AI client built on the Laravel AI SDK (adniro:ai).
 *
 * - Uses the model the admin assigned to the feature, and its fallback once on errors.
 * - Never falls back on a refusal: a declined request is a decision, not an outage.
 * - Re-validates every answer against the schema; an invalid answer is retried once on the same model.
 * - Registers the provider in the SDK at call time from the database record, so a changed key
 *   or model is used by the next call, including in long-running queue workers.
 * - Writes one ai_usage_logs row per provider call, successful or not.
 */

class LaravelAiClient implements AiClientContract
{
	public function __construct(
		private readonly AiSettings $settings,
		private readonly AiModelResolver $resolver,
		private readonly AiBudget $budget,
	) {}
	
	public function enabled(string $feature): bool
	{
		return $this->settings->enabled() && !empty($this->resolver->candidatesFor($feature));
	}
	
	public function structured(AiRequest $request): AiResult
	{
		$isAdminTest = $request->model instanceof AiModel;
		
		if (!$isAdminTest && !$this->settings->enabled()) {
			return AiResult::disabled('ai_disabled');
		}
		
		$candidates = $isAdminTest ? [$request->model] : $this->resolver->candidatesFor($request->feature);
		if (empty($candidates)) {
			return AiResult::disabled('no_model');
		}
		
		if (!$isAdminTest) {
			$reason = $this->budget->check($request->feature);
			if (!is_null($reason)) {
				$result = AiResult::capped($reason);
				$this->log($request, null, $result);
				
				return $result;
			}
		}
		
		$result = null;
		foreach ($candidates as $index => $model) {
			$wasFallback = $index > 0;
			
			$result = $this->attempt($request, $model, $wasFallback);
			if ($result->status === AiResult::INVALID) {
				$result = $this->attempt($request, $model, $wasFallback);
			}
			
			if ($result->ok() || $result->status === AiResult::REFUSAL) {
				return $result;
			}
		}
		
		return $result;
	}
	
	public function transcribe(string $audioPath, ?string $mimeType = null, ?string $language = null, ?int $userId = null, ?string $ip = null, ?AiModel $model = null): AiResult
	{
		$isAdminTest = $model instanceof AiModel;
		$request = new AiRequest(
			feature: 'transcription',
			instructions: '',
			prompt: '',
			schema: [],
			userId: $userId,
			ip: $ip,
			subjectType: $isAdminTest ? 'ai_model' : 'listing_assistant_voice',
			subjectId: $model?->id,
			meta: array_filter(['language' => $language]),
			model: $model,
		);
		
		if (!$isAdminTest && !$this->settings->enabled()) {
			return AiResult::disabled('ai_disabled');
		}
		
		$candidates = $isAdminTest ? [$model] : $this->resolver->candidatesFor('transcription');
		if (empty($candidates)) {
			return AiResult::disabled('no_model');
		}
		
		if (!$isAdminTest) {
			$reason = $this->budget->check('transcription');
			if (!is_null($reason)) {
				$result = AiResult::capped($reason);
				$this->log($request, null, $result);
				
				return $result;
			}
		}
		
		$speechModel = $candidates[0];
		$speechModel->loadMissing('provider');
		$provider = $speechModel->provider;
		$started = hrtime(true);
		$base = ['provider' => $provider?->driver, 'model' => $speechModel->model_id, 'aiModelId' => $speechModel->id];
		
		if (!$provider instanceof AiProvider) {
			return $this->finish($request, $speechModel, self::make(['status' => AiResult::ERROR, 'errorType' => 'no_provider'], $base));
		}
		if (!$provider->hasApiKey() && !in_array($provider->driver, ['ollama'], true)) {
			return $this->finish($request, $speechModel, self::make(['status' => AiResult::ERROR, 'errorType' => 'missing_api_key', 'errorMessage' => trans('ai.key_missing')], $base));
		}
		
		try {
			Ai::build($provider->sdkConfig());
			
			$pending = Transcription::fromPath($audioPath, $mimeType)->timeout((int)config('ai_features.timeout', 60));
			if (!empty($language)) {
				$pending->language($language);
			}
			$response = $pending->generate($provider->sdkName(), $speechModel->model_id);
		} catch (Throwable $e) {
			return $this->finish($request, $speechModel, self::make(['status' => AiResult::ERROR, 'errorType' => class_basename($e), 'errorMessage' => $this->safeMessage($e), 'retryable' => $e instanceof FailoverableException, 'latencyMs' => $this->elapsedMs($started)], $base));
		}
		
		$inputTokens = (int)($response->usage->inputTokens ?? 0);
		$outputTokens = (int)($response->usage->outputTokens ?? 0);
		
		return $this->finish($request, $speechModel, self::make(['status' => AiResult::OK, 'data' => ['text' => trim((string)$response->text)]], $base, [
			'inputTokens'  => $inputTokens,
			'outputTokens' => $outputTokens,
			'costUsd'      => CostCalculator::cost($speechModel, $inputTokens, $outputTokens),
			'latencyMs'    => $this->elapsedMs($started),
		]));
	}
	
	public function testModel(AiModel $model): AiResult
	{
		$model->loadMissing('provider');
		
		// A speech-to-text model is tested with a second of silence: any answer, even empty, is fine
		if ($model->supports_transcription && !$model->supports_structured_output) {
			$path = $this->testAudio();
			$result = $this->transcribe($path, 'audio/wav', null, auth()->id(), request()?->ip(), $model);
			@unlink($path);
			
			$model->forceFill([
				'last_tested_at'  => now(),
				'last_test_ok'    => $result->ok(),
				'last_test_error' => $result->ok() ? null : $this->describe($result),
			])->save();
			
			return $result;
		}
		
		$images = [];
		$prompt = 'This is a connection test. Reply with ok set to true and the word "pong" as the answer.';
		if ($model->supports_vision) {
			$image = $this->testImage();
			if ($image) {
				$images[] = $image;
				$prompt = 'This is a connection test. Reply with ok set to true, the word "pong" as the answer, and the main colour of the attached image.';
			}
		}
		
		$result = $this->structured(new AiRequest(
			feature: 'test',
			instructions: 'You are a connection test for a classified ads website. Answer only with the requested JSON.',
			prompt: $prompt,
			schema: [
				'type'       => 'object',
				'required'   => ['ok', 'answer', 'colour'],
				'properties' => [
					'ok'     => ['type' => 'boolean'],
					'answer' => ['type' => 'string'],
					'colour' => ['type' => ['string', 'null']],
				],
			],
			images: $images,
			maxTokens: (int)config('ai_features.test.max_tokens', 2000),
			userId: auth()->id(),
			ip: request()?->ip(),
			subjectType: 'ai_model',
			subjectId: $model->id,
			model: $model,
		));
		
		$model->forceFill([
			'last_tested_at'  => now(),
			'last_test_ok'    => $result->ok(),
			'last_test_error' => $result->ok() ? null : $this->describe($result),
		])->save();
		
		return $result;
	}
	
	public function testProvider(AiProvider $provider): AiResult
	{
		$model = $provider->models()->where('active', 1)->orderBy('id')->first();
		
		if (!$model instanceof AiModel) {
			$result = new AiResult(status: AiResult::ERROR, errorType: 'no_model', errorMessage: trans('ai.test_add_model_first'));
		} else {
			$result = $this->testModel($model);
		}
		
		$provider->forceFill([
			'last_tested_at'  => now(),
			'last_test_ok'    => $result->ok(),
			'last_test_error' => $result->ok() ? null : $this->describe($result),
		])->save();
		
		return $result;
	}
	
	/**
	 * Short human-readable reason for a failed result.
	 */
	public function describe(AiResult $result): string
	{
		$message = $result->errorMessage ?: $result->errorType ?: $result->status;
		
		return (string)str($result->status . ': ' . $message)->limit(480);
	}
	
	/**
	 * Map a stop that is not a normal finish to a result. Refusals and safety blocks from every
	 * provider arrive as FinishReason::ContentFilter.
	 *
	 * @return array|null Result fields, or null for a normal finish
	 */
	public static function statusForFinishReason(?FinishReason $reason): ?array
	{
		return match ($reason) {
			FinishReason::ContentFilter => ['status' => AiResult::REFUSAL, 'errorType' => 'content_filter'],
			FinishReason::Length => ['status' => AiResult::TRUNCATED, 'errorType' => 'max_tokens'],
			FinishReason::Error, FinishReason::Cancelled => ['status' => AiResult::ERROR, 'errorType' => 'finish_' . $reason->value],
			default => null,
		};
	}
	
	/*
	|--------------------------------------------------------------------------
	| PRIVATE
	|--------------------------------------------------------------------------
	*/
	
	private function attempt(AiRequest $request, AiModel $model, bool $wasFallback): AiResult
	{
		$provider = $model->provider;
		$started = hrtime(true);
		
		$base = [
			'provider'    => $provider?->driver,
			'model'       => $model->model_id,
			'aiModelId'   => $model->id,
			'wasFallback' => $wasFallback,
		];
		
		if (!$provider instanceof AiProvider) {
			return $this->finish($request, $model, self::make(['status' => AiResult::ERROR, 'errorType' => 'no_provider'], $base));
		}
		
		if (!$provider->hasApiKey() && !in_array($provider->driver, ['ollama'], true)) {
			return $this->finish($request, $model, self::make(['status' => AiResult::ERROR, 'errorType' => 'missing_api_key', 'errorMessage' => trans('ai.key_missing')], $base));
		}
		
		try {
			Ai::build($provider->sdkConfig());
			
			$agent = new StructuredTaskAgent(
				instructionsText: $request->instructions,
				jsonSchema: $request->schema,
				maxOutputTokens: $request->maxTokens,
				options: $model->optionsArray(),
			);
			
			/** @var AgentResponse $response */
			$response = $agent->prompt(
				$request->prompt,
				attachments: array_values(array_filter($request->images, fn ($image) => $image instanceof Image)),
				provider: $provider->sdkName(),
				model: $model->model_id,
				timeout: $request->timeoutSeconds ?? (int)config('ai_features.timeout', 60),
			);
		} catch (Throwable $e) {
			return $this->finish($request, $model, self::make(['status' => AiResult::ERROR, 'errorType' => class_basename($e), 'errorMessage' => $this->safeMessage($e), 'retryable' => $e instanceof FailoverableException, 'latencyMs' => $this->elapsedMs($started)], $base));
		}
		
		$usage = $response->usage;
		$inputTokens = (int)($usage->inputTokens ?? 0);
		$outputTokens = (int)($usage->outputTokens ?? 0);
		
		$tokens = [
			'inputTokens'      => $inputTokens,
			'outputTokens'     => $outputTokens,
			'cacheReadTokens'  => (int)($usage->cacheReadInputTokens ?? 0),
			'cacheWriteTokens' => (int)($usage->cacheWriteInputTokens ?? 0),
			'costUsd'          => CostCalculator::cost($model, $inputTokens, $outputTokens),
			'latencyMs'        => $this->elapsedMs($started),
		];
		
		$stopped = self::statusForFinishReason($response->steps->last()?->finishReason);
		if (!is_null($stopped)) {
			return $this->finish($request, $model, self::make($stopped, $base, $tokens));
		}
		
		$data = property_exists($response, 'structured') && is_array($response->structured) ? $response->structured : [];
		$error = empty($data) ? 'empty or unreadable JSON' : SchemaRules::firstError($data, $request->schema);
		
		if (!is_null($error)) {
			return $this->finish($request, $model, self::make(['status' => AiResult::INVALID, 'errorType' => 'schema_mismatch', 'errorMessage' => $error], $base, $tokens));
		}
		
		return $this->finish($request, $model, self::make(['status' => AiResult::OK, 'data' => $data], $base, $tokens));
	}
	
	/**
	 * One second of 16 kHz mono silence as a WAV file, for testing speech-to-text models.
	 */
	private function testAudio(): string
	{
		$rate = 16000;
		$samples = str_repeat("\x00\x00", $rate);
		$wav = 'RIFF' . pack('V', 36 + strlen($samples)) . 'WAVE'
			. 'fmt ' . pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
			. 'data' . pack('V', strlen($samples)) . $samples;
		
		$path = tempnam(sys_get_temp_dir(), 'ai-test-') . '.wav';
		file_put_contents($path, $wav);
		
		return $path;
	}
	
	private static function make(array ...$parts): AiResult
	{
		return new AiResult(...array_merge(...$parts));
	}
	
	private function finish(AiRequest $request, ?AiModel $model, AiResult $result): AiResult
	{
		$logId = $this->log($request, $model, $result);
		
		if ($result->reachedProvider()) {
			$this->budget->record($result->costUsd);
		}
		
		return new AiResult(
			status: $result->status,
			data: $result->data,
			errorType: $result->errorType,
			errorMessage: $result->errorMessage,
			retryable: $result->retryable,
			inputTokens: $result->inputTokens,
			outputTokens: $result->outputTokens,
			cacheReadTokens: $result->cacheReadTokens,
			cacheWriteTokens: $result->cacheWriteTokens,
			costUsd: $result->costUsd,
			latencyMs: $result->latencyMs,
			provider: $result->provider,
			model: $result->model,
			aiModelId: $result->aiModelId,
			wasFallback: $result->wasFallback,
			usageLogId: $logId,
		);
	}
	
	private function log(AiRequest $request, ?AiModel $model, AiResult $result): ?int
	{
		try {
			$row = AiUsageLog::query()->create([
				'feature'            => $request->feature,
				'ai_model_id'        => $model?->id,
				'provider'           => $result->provider,
				'model'              => $result->model,
				'user_id'            => $request->userId,
				'ip'                 => $request->ip,
				'subject_type'       => $request->subjectType,
				'subject_id'         => is_null($request->subjectId) ? null : (string)$request->subjectId,
				'status'             => $result->status,
				'error_type'         => $result->errorType ? (string)str($result->errorType)->limit(95) : null,
				'error_message'      => $result->errorMessage ? (string)str($result->errorMessage)->limit(480) : null,
				'input_tokens'       => $result->inputTokens,
				'output_tokens'      => $result->outputTokens,
				'cache_read_tokens'  => $result->cacheReadTokens,
				'cache_write_tokens' => $result->cacheWriteTokens,
				'cost_usd'           => $result->costUsd,
				'latency_ms'         => $result->latencyMs,
				'was_fallback'       => $result->wasFallback,
				'meta'               => empty($request->meta) ? null : $request->meta,
			]);
			
			return (int)$row->id;
		} catch (Throwable $e) {
			report($e);
			
			return null;
		}
	}
	
	/**
	 * Error text without anything that looks like a credential.
	 */
	private function safeMessage(Throwable $e): string
	{
		return SecretRedactor::redact($e->getMessage());
	}
	
	private function elapsedMs(int|float $started): int
	{
		return (int)round((hrtime(true) - $started) / 1_000_000);
	}
	
	/**
	 * A small solid red PNG, generated in memory for the vision part of "Test model".
	 */
	private function testImage(): ?Image
	{
		if (!function_exists('imagecreatetruecolor')) {
			return null;
		}
		
		$gd = imagecreatetruecolor(96, 96);
		imagefill($gd, 0, 0, imagecolorallocate($gd, 220, 20, 20));
		ob_start();
		imagepng($gd);
		$png = (string)ob_get_clean();
		imagedestroy($gd);
		
		return Image::fromBase64(base64_encode($png), 'image/png');
	}
}
