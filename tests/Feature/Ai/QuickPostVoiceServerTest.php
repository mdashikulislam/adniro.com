<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiResult;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/*
 * Phase 10: the composer's recorder fallback and the API, through the quick post service (adniro:ai).
 */

class QuickPostVoiceServerTest extends TestCase
{
	use RefreshDatabase, QuickPostFixtures;
	
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpQuickPost();
		config(['larapen.core.api.token' => 'test-api-token']);
	}
	
	public function test_a_recording_is_turned_into_text_in_the_composer(): void
	{
		$this->ai->transcribeWith(fn () => new AiResult(AiResult::OK, ['text' => 'iPhone 13 blue, call 01711-123456, 420 dollars']));
		
		$response = $this->actingAs($this->user(7))->post('/posts/create/quick/transcribe', [
			'audio' => $this->recording(), 'language' => 'bn-BD',
		], ['Accept' => 'application/json']);
		
		$response->assertOk()->assertJson(['ok' => true]);
		$this->assertStringContainsString('iPhone 13 blue', $response->json('text'));
		$this->assertStringNotContainsString('01711', $response->json('text'), 'contact details are removed');
		$this->assertSame('bn', $this->ai->lastTranscription['language']);
		$this->assertSame(7, $this->ai->lastTranscription['user']);
		$this->assertSame(0, \App\Models\PostDraft::query()->count(), 'not counted as an AI post');
	}
	
	public function test_silence_bad_files_and_failures_get_a_clear_answer(): void
	{
		$user = $this->user(7);
		
		$this->ai->transcribeWith(fn () => new AiResult(AiResult::OK, ['text' => '']));
		$this->actingAs($user)->post('/posts/create/quick/transcribe', ['audio' => $this->recording()], ['Accept' => 'application/json'])
			->assertOk()->assertJson(['ok' => false, 'message' => 'We did not hear anything. Tap the microphone and try again.']);
		
		$this->ai->transcribeWith(fn () => new AiResult(AiResult::ERROR, errorType: 'Timeout'));
		$this->actingAs($user)->post('/posts/create/quick/transcribe', ['audio' => $this->recording()], ['Accept' => 'application/json'])
			->assertOk()->assertJson(['ok' => false]);
		
		$this->actingAs($user)->post('/posts/create/quick/transcribe', ['audio' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
			->assertStatus(422);
		$this->assertSame(2, $this->ai->transcriptions);
	}
	
	public function test_the_recorder_needs_quick_post_and_a_speech_model(): void
	{
		$this->setAi(['quickpost_enabled' => '0']);
		$this->app->forgetInstance(QuickPostService::class);
		
		$this->actingAs($this->user(7))->post('/posts/create/quick/transcribe', ['audio' => $this->recording()], ['Accept' => 'application/json'])
			->assertForbidden();
		$this->assertSame(0, $this->ai->transcriptions);
	}
	
	public function test_recordings_are_rate_limited(): void
	{
		$this->ai->transcribeWith(fn () => new AiResult(AiResult::OK, ['text' => 'hello there']));
		$user = $this->user(7);
		RateLimiter::clear('quickpost-voice:user:7');
		
		for ($i = 0; $i < 10; $i++) {
			$this->actingAs($user)->post('/posts/create/quick/transcribe', ['audio' => $this->recording()], ['Accept' => 'application/json'])->assertOk();
		}
		$this->actingAs($user)->post('/posts/create/quick/transcribe', ['audio' => $this->recording()], ['Accept' => 'application/json'])->assertStatus(429);
	}
	
	public function test_the_api_transcribes_and_reports_voice_availability(): void
	{
		$this->ai->transcribeWith(fn () => new AiResult(AiResult::OK, ['text' => 'Grey sofa, three seats']));
		$headers = ['Accept' => 'application/json', 'X-AppApiToken' => 'test-api-token'];
		$user = $this->user(7);
		
		$this->actingAs($user, 'sanctum')->getJson('/api/quickpost/limit', $headers)->assertJson(['result' => ['voice' => true]]);
		$this->actingAs($user, 'sanctum')->post('/api/quickpost/transcriptions', ['audio' => $this->recording(), 'language' => 'en'], $headers)
			->assertOk()->assertJson(['success' => true, 'result' => ['text' => 'Grey sofa, three seats']]);
	}
	
	public function test_the_composer_offers_the_recorder_only_where_the_browser_cannot_listen(): void
	{
		$compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/front/post/quick/composer.blade.php')));
		token_get_all($compiled, TOKEN_PARSE);
		foreach (['MediaRecorder', 'toggleRecording', '!voiceSupported', "voiceBox.dataset.server === '1'", 'getUserMedia'] as $hook) {
			$this->assertStringContainsString($hook, $compiled, $hook);
		}
		
		$html = view('front.post.quick.partials.form', [
			'limit' => app(QuickPostService::class)->limitStatus(null, '127.0.0.1'), 'limitText' => null,
			'formUrl' => '/f', 'draftUrl' => '/d', 'minText' => 10, 'maxText' => 2000, 'timeoutMs' => 30000,
			'voice' => ['default' => 'en-US', 'languages' => [], 'server' => true, 'serverUrl' => '/t', 'maxSeconds' => 60],
		])->render();
		$this->assertStringContainsString('data-server="1"', $html);
		$this->assertStringContainsString('data-server-url="/t"', $html);
		$this->assertStringContainsString('turn it into text', $html);
	}
	
	private function recording(): UploadedFile
	{
		$samples = str_repeat("\x00\x00", 8000);
		$path = tempnam(sys_get_temp_dir(), 'rec') . '.wav';
		file_put_contents($path, 'RIFF' . pack('V', 36 + strlen($samples)) . 'WAVE' . 'fmt ' . pack('VvvVVvv', 16, 1, 1, 16000, 32000, 2, 16) . 'data' . pack('V', strlen($samples)) . $samples);
		
		return new UploadedFile($path, 'voice.wav', 'audio/wav', null, true);
	}
}
