<?php

namespace Tests\Feature\Ai;

use App\Models\PostDraft;
use App\Services\Ai\AiResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
 * The AI box inside the listing form (adniro:ai), ported from classifedz.com: the seller describes
 * the item once and the form is filled in place, through the same draft service and limits.
 */

class AiFormBoxTest extends TestCase
{
	use RefreshDatabase, QuickPostFixtures;

	private const FILL_URL = '/posts/create/quick/fill';

	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpQuickPost();
		$this->ai->answer($this->draftAnswer());
	}

	public function test_a_description_comes_back_as_form_fields(): void
	{
		// Arrange
		$user = $this->user(7);

		// Act
		$response = $this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue with box, 420 dollars, Springfield']);

		// Assert
		$response->assertOk()->assertJson([
			'ok'     => true,
			'fields' => [
				'category' => ['id' => 2, 'name' => 'Mobile phones', 'parent_id' => 1, 'type' => 'classified', 'path' => 'Phones › Mobile phones'],
				'title'    => 'Apple iPhone 13 128GB Blue',
				'price'    => 420,
				'cf'       => [10 => 101, 11 => '128', 12 => [121]],
			],
		]);
		$fields = $response->json('fields');
		$this->assertSame(['iphone', 'apple'], $fields['tags']);
		$this->assertSame(['Brand', 'Storage', 'Extras'], $fields['cf_labels']);
		$this->assertStringContainsString('<p>Blue iPhone 13 with box.', $fields['html']);
		$this->assertStringNotContainsString('555', $fields['text'], 'contact details are removed');
		$this->assertNotEmpty($response->json('draft'));
		$this->assertSame('19 of 20 AI posts left today', $response->json('limit_text'));
		$this->assertSame(1, PostDraft::query()->where('counted', true)->count());
	}

	public function test_the_city_is_given_with_the_keys_the_location_picker_needs(): void
	{
		// Arrange: the model names a city the site knows
		$this->ai->answer(array_merge($this->draftAnswer(), ['location' => 'Chicago']));

		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue, Chicago']);

		// Assert
		$response->assertJson(['fields' => ['city' => ['id' => 700, 'name' => 'Chicago']]]);
		$this->assertArrayHasKey('admin_code', $response->json('fields.city'));
	}

	public function test_filling_does_not_touch_the_session_form(): void
	{
		// Act
		$this->actingAs($this->user(7))->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue with box']);

		// Assert
		$this->assertNull(session('postInput'));
	}

	public function test_refilling_the_same_item_is_free_twice(): void
	{
		// Arrange
		$this->setAi(['quickpost_member_limit' => '1']);
		$user = $this->user(7);
		$first = $this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue with box'])->json('draft');

		// Act
		$second = $this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue, box, 400', 'redraft_of' => $first]);

		// Assert
		$second->assertOk()->assertJson(['ok' => true]);
	}

	public function test_a_recording_is_turned_into_text_and_then_into_fields(): void
	{
		// Arrange
		$this->ai->transcribeWith(fn () => new AiResult(AiResult::OK, ['text' => 'iPhone 13 blue, call 01711-123456, 420 dollars']));

		// Act
		$response = $this->actingAs($this->user(7))->post(self::FILL_URL, ['audio' => $this->recording()], ['Accept' => 'application/json']);

		// Assert
		$response->assertOk()->assertJson(['ok' => true, 'fields' => ['title' => 'Apple iPhone 13 128GB Blue']]);
		$this->assertStringNotContainsString('01711', $response->json('transcript'));
		$this->assertSame(1, $this->ai->transcriptions);
	}

	public function test_a_recording_needs_a_speech_model(): void
	{
		// Arrange
		$this->ai->disabled = ['transcription'];
		
		// Act
		$response = $this->actingAs($this->user(7))->post(self::FILL_URL, ['audio' => $this->recording()], ['Accept' => 'application/json']);

		// Assert
		$response->assertStatus(403);
		$this->assertSame(0, $this->ai->calls);
	}

	public function test_clear_answers_when_the_box_cannot_help(): void
	{
		// Arrange
		$this->setAi(['quickpost_member_limit' => '1']);
		$user = $this->user(7);

		// Act & Assert: too short, used up, switched off
		$this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'phone'])->assertStatus(422)->assertJson(['ok' => false, 'reason' => 'too_short']);
		$this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue with box'])->assertOk();
		$this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 12 red with box'])->assertStatus(429)->assertJson(['reason' => 'limit']);
		$this->setAi(['quickpost_enabled' => '0']);
		$this->app->forgetInstance(\App\Services\QuickPost\QuickPostService::class);
		$this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 12 red with box'])->assertStatus(403)->assertJson(['reason' => 'off']);
		$this->assertSame(1, $this->ai->calls);
	}

	public function test_an_ai_failure_is_reported_and_not_counted(): void
	{
		// Arrange
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR));

		// Act
		$response = $this->actingAs($this->user(7))->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue with box']);

		// Assert
		$response->assertStatus(503)->assertJson(['ok' => false, 'reason' => 'ai_unavailable']);
		$this->assertSame(0, PostDraft::query()->where('counted', true)->count());
		$this->assertNull(session('postInput'), 'the form keeps what the seller typed');
	}

	public function test_the_box_is_in_the_listing_form_when_available(): void
	{
		// Arrange
		$this->actingAs($this->user(7));
		
		// Act
		$html = $this->renderBox();
		
		// Assert
		$this->assertStringContainsString('id="aiQuick"', $html);
		$this->assertStringContainsString('data-url="' . url('posts/create/quick/fill') . '"', $html);
		$this->assertStringContainsString('20 of 20 AI posts left today', $html);
		$this->assertStringContainsString('ai.formbox.js', $html);
		foreach (['resources/views/front/post/createOrEdit/multiSteps/create/post.blade.php', 'resources/views/front/post/createOrEdit/singleStep/create.blade.php'] as $form) {
			$this->assertStringContainsString("@include('front.post.quick.form-box')", file_get_contents(base_path($form)), $form);
		}
	}
	
	public function test_guests_are_asked_to_log_in_when_guests_cannot_use_ai(): void
	{
		// Act: guests are off by default
		$html = $this->renderBox();
		
		// Assert
		$this->assertStringContainsString('#quickLogin', $html);
		$this->assertStringNotContainsString('ai.formbox.js', $html);
	}
	
	public function test_the_box_is_hidden_when_ai_posting_is_off(): void
	{
		// Arrange
		$this->setAi(['quickpost_enabled' => '0']);
		$this->actingAs($this->user(7));
		
		// Act
		$html = view('front.post.quick.form-box')->render();
		
		// Assert
		$this->assertSame('', trim($html));
	}
	
	public function test_the_script_fills_the_form_through_its_own_functions(): void
	{
		// Arrange
		$script = file_get_contents(public_path('assets/js/app/ai.formbox.js'));

		// Assert: category and fields via the form's own code, undo, and voice with a fallback
		foreach (['appendSelectedCategory', 'cfContainer', 'selectCity', 'takeSnapshot', 'restore', 'redraft_of', 'MediaRecorder', 'SpeechRecognition'] as $hook) {
			$this->assertStringContainsString($hook, $script, $hook);
		}
	}

	public function test_the_old_composer_address_opens_the_normal_form(): void
	{
		// Act & Assert
		$this->actingAs($this->user(7))->get('/posts/create/quick')->assertRedirect(urlGen()->addPost());
		[$url] = getCreateListingLinkInfo();
		$this->assertSame(urlGen()->addPost(), $url);
	}

	/** The box with the script stack it pushes to, as the layout prints it. */
	private function renderBox(): string
	{
		return \Illuminate\Support\Facades\Blade::render("@include('front.post.quick.form-box')\n@stack('after_scripts_stack')");
	}
	
	private function recording(): UploadedFile
	{
		$samples = str_repeat("\x00\x00", 8000);
		$wav = 'RIFF' . pack('V', 36 + strlen($samples)) . 'WAVE' . 'fmt ' . pack('VvvVVvv', 16, 1, 1, 16000, 32000, 2, 16) . 'data' . pack('V', strlen($samples)) . $samples;
		
		return UploadedFile::fake()->createWithContent('voice.wav', $wav);
	}
}
