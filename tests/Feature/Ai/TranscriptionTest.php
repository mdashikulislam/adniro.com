<?php

namespace Tests\Feature\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiUsageLog;
use App\Models\Setting;
use App\Services\Ai\AiResult;
use App\Services\Ai\LaravelAiClient;
use App\Services\ListingAssistant\ListingAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Transcription;
use Tests\TestCase;

/*
 * Phase 10: server-side speech-to-text through an assigned model (adniro:ai).
 */

class TranscriptionTest extends TestCase
{
	use RefreshDatabase;
	
	private AiModel $speech;
	
	protected function setUp(): void
	{
		parent::setUp();
		Cache::flush();
		
		$provider = AiProvider::query()->create(['name' => 'Speech', 'driver' => 'openai', 'api_key' => 'sk-test-5678', 'active' => true]);
		$this->speech = AiModel::query()->forceCreate([
			'ai_provider_id' => $provider->id, 'model_id' => 'gpt-4o-transcribe', 'supports_vision' => 0,
			'supports_structured_output' => 0, 'supports_transcription' => 1, 'active' => 1,
			'input_price_per_mtok' => 2.5, 'output_price_per_mtok' => 10,
		]);
		Setting::query()->updateOrCreate(['name' => 'ai'], ['label' => 'AI', 'active' => 1, 'field_values' => [
			'enabled' => '1', 'transcription_model_id' => (string)$this->speech->id, 'daily_call_limit' => '500',
		]]);
	}
	
	public function test_a_recording_becomes_text_and_is_logged(): void
	{
		Transcription::fake(['Samsung Galaxy, like new, 300 dollars']);
		
		$result = app(LaravelAiClient::class)->transcribe($this->wav(), 'audio/wav', 'bn', 7, '10.0.0.1');
		
		$this->assertTrue($result->ok());
		$this->assertSame('Samsung Galaxy, like new, 300 dollars', $result->data['text']);
		$this->assertSame('gpt-4o-transcribe', $result->model);
		Transcription::assertGenerated(fn ($prompt) => $prompt->language === 'bn');
		
		$log = AiUsageLog::query()->sole();
		$this->assertSame('transcription', $log->feature);
		$this->assertSame('ok', $log->status);
		$this->assertSame(7, (int)$log->user_id);
	}
	
	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function recordedTypes(): array
	{
		return [
			'iPhone (seen as video)' => ['video/mp4', 'voice.m4a', 'audio/mp4'],
			'iPhone'                 => ['audio/mp4', 'voice.m4a', 'audio/mp4'],
			'Chrome'                 => ['video/webm', 'voice.webm', 'audio/webm'],
			'Firefox'                => ['audio/ogg', 'voice.ogg', 'audio/ogg'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('recordedTypes')]
	public function test_an_upload_without_an_extension_reaches_openai_named_by_its_audio_type(string $detected, string $name, string $sent): void
	{
		// Arrange: PHP keeps uploads in temporary files without an extension, and OpenAI reads the format from the name
		\Illuminate\Support\Facades\Http::fake([
			'api.openai.com/*' => \Illuminate\Support\Facades\Http::response(['text' => 'iPhone 13, 128 GB', 'usage' => ['input_tokens' => 20, 'output_tokens' => 6]]),
		]);
		$upload = tempnam(sys_get_temp_dir(), 'php');
		file_put_contents($upload, 'recorded audio');

		// Act
		$result = app(LaravelAiClient::class)->transcribe($upload, $detected, 'en', 7, '10.0.0.1');

		// Assert
		$this->assertTrue($result->ok(), (string)$result->errorMessage);
		\Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($name, $sent) {
			$body = (string)$request->toPsrRequest()->getBody();

			return str_contains($body, 'filename="' . $name . '"') && str_contains($body, 'Content-Type: ' . $sent);
		});
	}

	public function test_without_an_assigned_model_or_with_ai_off_nothing_is_sent(): void
	{
		Transcription::fake(['x']);
		Setting::query()->where('name', 'ai')->update(['field_values' => json_encode(['enabled' => '1'])]);
		$this->assertSame(AiResult::DISABLED, app(LaravelAiClient::class)->transcribe($this->wav())->status);
		
		Setting::query()->where('name', 'ai')->update(['field_values' => json_encode(['enabled' => '0', 'transcription_model_id' => (string)$this->speech->id])]);
		$this->assertSame(AiResult::DISABLED, app(LaravelAiClient::class)->transcribe($this->wav())->status);
		$this->assertSame(0, AiUsageLog::query()->count());
	}
	
	public function test_test_model_works_for_speech_models(): void
	{
		Transcription::fake(['']);
		
		$result = app(LaravelAiClient::class)->testModel($this->speech);
		
		$this->assertTrue($result->ok());
		$this->assertTrue((bool)$this->speech->fresh()->last_test_ok);
		Transcription::assertGenerated(fn ($prompt) => true);
	}
	
	public function test_speech_models_are_offered_for_transcription_only(): void
	{
		$this->assertTrue($this->speech->isEligibleFor('transcription'));
		$this->assertFalse($this->speech->isEligibleFor('moderation'));
		$this->assertSame('ElevenLabs (speech-to-text only)', config('ai_features.drivers.eleven.label'));
	}
	
	private function wav(): string
	{
		$samples = str_repeat("\x00\x00", 16000);
		$path = tempnam(sys_get_temp_dir(), 'wav') . '.wav';
		file_put_contents($path, 'RIFF' . pack('V', 36 + strlen($samples)) . 'WAVE' . 'fmt ' . pack('VvvVVvv', 16, 1, 1, 16000, 32000, 2, 16) . 'data' . pack('V', strlen($samples)) . $samples);
		
		return $path;
	}
}
