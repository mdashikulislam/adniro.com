<?php

namespace Tests\Feature\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiUsageLog;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\Ai\Contracts\AiClientContract;
use Closure;

/*
 * Answers moderation requests without any network call.
 */
class FakeAiClient implements AiClientContract
{
	public int $calls = 0;
	public ?AiRequest $lastRequest = null;
	private ?Closure $responder = null;
	
	public function answer(array $data): void
	{
		$this->respond(fn () => new AiResult(AiResult::OK, $data, provider: 'test', model: 'fake-model'));
	}
	
	public function respond(Closure $responder): void
	{
		$this->responder = $responder;
	}
	
	public function enabled(string $feature): bool
	{
		return true;
	}
	
	public function structured(AiRequest $request): AiResult
	{
		$this->calls++;
		$this->lastRequest = $request;
		
		$result = $this->responder ? ($this->responder)($request) : AiResult::disabled('no_model');
		
		// Like the real client: an answer that reached the provider is logged with tokens
		if ($result->reachedProvider()) {
			$log = AiUsageLog::query()->create([
				'feature'    => $request->feature,
				'user_id'    => $request->userId,
				'ip'         => $request->ip,
				'status'     => $result->status,
				'provider'   => 'test',
				'model'      => 'fake-model',
				'created_at' => now(),
			]);
			
			return new AiResult(
				$result->status, $result->data, $result->errorType, $result->errorMessage, $result->retryable,
				provider: $result->provider ?? 'test', model: $result->model ?? 'fake-model', usageLogId: $log->id,
			);
		}
		
		return $result;
	}
	
	public int $transcriptions = 0;
	public ?array $lastTranscription = null;
	private ?Closure $transcriber = null;
	
	public function transcribeWith(Closure $transcriber): void
	{
		$this->transcriber = $transcriber;
	}
	
	public function transcribe(string $audioPath, ?string $mimeType = null, ?string $language = null, ?int $userId = null, ?string $ip = null, ?AiModel $model = null): AiResult
	{
		$this->transcriptions++;
		$this->lastTranscription = ['path' => $audioPath, 'mime' => $mimeType, 'language' => $language, 'user' => $userId, 'ip' => $ip, 'bytes' => is_file($audioPath) ? filesize($audioPath) : 0];
		
		return $this->transcriber ? ($this->transcriber)($audioPath, $language) : AiResult::disabled('no_model');
	}
	
	public function testModel(AiModel $model): AiResult
	{
		return new AiResult(AiResult::OK);
	}
	
	public function testProvider(AiProvider $provider): AiResult
	{
		return new AiResult(AiResult::OK);
	}
}
