<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
 * Speech-to-text in the mobile API, through the quick post service (adniro:ai). The form's AI box
 * sends recordings to posts/create/quick/fill (see AiFormBoxTest).
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
	
	public function test_the_api_transcribes_and_reports_voice_availability(): void
	{
		$this->ai->transcribeWith(fn () => new AiResult(AiResult::OK, ['text' => 'Grey sofa, three seats']));
		$headers = ['Accept' => 'application/json', 'X-AppApiToken' => 'test-api-token'];
		$user = $this->user(7);
		
		$this->actingAs($user, 'sanctum')->getJson('/api/quickpost/limit', $headers)->assertJson(['result' => ['voice' => true]]);
		$this->actingAs($user, 'sanctum')->post('/api/quickpost/transcriptions', ['audio' => $this->recording(), 'language' => 'en'], $headers)
			->assertOk()->assertJson(['success' => true, 'result' => ['text' => 'Grey sofa, three seats']]);
	}
	
	private function recording(): UploadedFile
	{
		$samples = str_repeat("\x00\x00", 8000);
		$path = tempnam(sys_get_temp_dir(), 'rec') . '.wav';
		file_put_contents($path, 'RIFF' . pack('V', 36 + strlen($samples)) . 'WAVE' . 'fmt ' . pack('VvvVVvv', 16, 1, 1, 16000, 32000, 2, 16) . 'data' . pack('V', strlen($samples)) . $samples);
		
		return new UploadedFile($path, 'voice.wav', 'audio/wav', null, true);
	}
}
