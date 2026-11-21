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
	use RefreshDatabase, ListingAssistantFixtures;

	private const FILL_URL = '/posts/create/assistant/fill';

	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpListingAssistant();
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
		$this->setAi(['listing_assistant_member_limit' => '1']);
		$user = $this->user(7);
		$first = $this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue with box'])->json('draft');

		// Act
		$second = $this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue, box, 400', 'redraft_of' => $first]);

		// Assert
		$second->assertOk()->assertJson(['ok' => true]);
	}

	public function test_photos_are_read_by_the_ai_and_put_into_the_photos_step(): void
	{
		// Arrange
		$disk = \Illuminate\Support\Facades\Storage::fake(\App\Helpers\Common\Files\Storage\StorageDisk::getDiskName());
		$photos = [UploadedFile::fake()->image('front.jpg', 800, 600), UploadedFile::fake()->image('back.jpg', 800, 600)];
		
		// Act: photos alone are enough, no text needed
		$response = $this->actingAs($this->user(7))->post(self::FILL_URL, ['photos' => $photos], ['Accept' => 'application/json']);
		
		// Assert
		$response->assertOk()->assertJson(['ok' => true, 'photos_added' => 2]);
		$this->assertCount(2, $this->ai->lastRequest->images);
		$pictures = session('picturesInput');
		$this->assertCount(2, $pictures);
		foreach ($pictures as $path) {
			$this->assertStringStartsWith('temporary/' . session('uid') . '/', $path);
			$disk->assertExists($path);
		}
	}
	
	public function test_a_photo_fill_lets_the_ai_name_the_product_from_its_design(): void
	{
		// Act: a photo with no text, like a seller who only adds a picture of their phone
		$this->actingAs($this->user(7))->post(self::FILL_URL, ['photos' => [UploadedFile::fake()->image('phone.jpg', 800, 600)]], ['Accept' => 'application/json'])
			->assertOk();

		// Assert: the photo counts as information, products are named from their design, gaps are left out
		$request = $this->ai->lastRequest;
		$this->assertCount(1, $request->images);
		$this->assertStringContainsString('Use only what the person said and what their photos show.', $request->instructions);
		$this->assertStringContainsString('from its design, not only from printed words or logos', $request->instructions);
		$this->assertStringContainsString('Never mention what is missing or unknown', $request->instructions);
		$this->assertStringContainsString('Never guess what a photo cannot show', $request->instructions);
		$this->assertStringNotContainsString('Use only what the person said. Do not invent', $request->instructions);
		$this->assertSame(\App\Services\ListingAssistant\DraftPrompt::VERSION, $request->meta['prompt']);
		$this->assertSame('2026-11-21.1', \App\Services\ListingAssistant\DraftPrompt::VERSION);
	}

	public function test_a_new_fill_replaces_the_box_photos_but_keeps_the_sellers_own(): void
	{
		// Arrange: one photo the seller uploaded in step 2, then a first fill with two photos
		$disk = \Illuminate\Support\Facades\Storage::fake(\App\Helpers\Common\Files\Storage\StorageDisk::getDiskName());
		$disk->put('temporary/own/mine.jpg', 'x');
		session(['picturesInput' => ['temporary/own/mine.jpg']]);
		$user = $this->user(7);
		$this->actingAs($user)->post(self::FILL_URL, ['text' => 'iPhone 13 blue with box', 'photos' => [
			UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'),
		]], ['Accept' => 'application/json'])->assertOk();
		$first = array_slice(session('picturesInput'), 1);
		
		// Act
		$response = $this->actingAs($user)->post(self::FILL_URL, ['text' => 'iPhone 13 blue with box', 'photos' => [
			UploadedFile::fake()->image('c.jpg'),
		]], ['Accept' => 'application/json']);
		
		// Assert
		$response->assertOk()->assertJson(['photos_added' => 1]);
		$pictures = session('picturesInput');
		$this->assertCount(2, $pictures);
		$this->assertSame('temporary/own/mine.jpg', $pictures[0]);
		foreach ($first as $path) {
			$disk->assertMissing($path);
		}
	}
	
	public function test_photos_respect_the_sites_photo_limit_and_are_kept_only_after_a_draft(): void
	{
		// Arrange
		\Illuminate\Support\Facades\Storage::fake(\App\Helpers\Common\Files\Storage\StorageDisk::getDiskName());
		config(['settings.listing_form.pictures_limit' => 1]);
		$user = $this->user(7);
		
		// Act & Assert: over the limit, only what fits is kept
		$this->actingAs($user)->post(self::FILL_URL, ['photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]], ['Accept' => 'application/json'])
			->assertOk()->assertJson(['photos_added' => 1]);
		
		// Act & Assert: a failed draft keeps nothing new
		session()->forget(['picturesInput', \App\Services\ListingAssistant\FormPhotos::SESSION_KEY]);
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR));
		$this->actingAs($user)->post(self::FILL_URL, ['photos' => [UploadedFile::fake()->image('c.jpg')]], ['Accept' => 'application/json'])
			->assertStatus(503);
		$this->assertEmpty(session('picturesInput'));
	}
	
	public function test_too_many_or_wrong_files_are_refused(): void
	{
		// Arrange
		$user = $this->user(7);
		$five = array_map(fn ($i) => UploadedFile::fake()->image($i . '.jpg'), range(1, 5));
		
		// Act & Assert
		$this->actingAs($user)->post(self::FILL_URL, ['photos' => $five], ['Accept' => 'application/json'])->assertStatus(422);
		$this->actingAs($user)->post(self::FILL_URL, ['photos' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]], ['Accept' => 'application/json'])->assertStatus(422);
		$this->assertSame(0, $this->ai->calls);
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
		$this->setAi(['listing_assistant_member_limit' => '1']);
		$user = $this->user(7);

		// Act & Assert: too short, used up, switched off
		$this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'phone'])->assertStatus(422)->assertJson(['ok' => false, 'reason' => 'too_short']);
		$this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 13 blue with box'])->assertOk();
		$this->actingAs($user)->postJson(self::FILL_URL, ['text' => 'iPhone 12 red with box'])->assertStatus(429)->assertJson(['reason' => 'limit']);
		$this->setAi(['listing_assistant_enabled' => '0']);
		$this->app->forgetInstance(\App\Services\ListingAssistant\ListingAssistantService::class);
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
		$this->assertStringContainsString('id="aiAssistant"', $html);
		$this->assertStringContainsString('data-url="' . url('posts/create/assistant/fill') . '"', $html);
		$this->assertStringContainsString('20 of 20 AI posts left today', $html);
		$this->assertStringContainsString('ai.formbox.js', $html);
		// Both create pages use the shared form page, whose body shows the box on new listings
		$body = file_get_contents(resource_path('views/front/post/createOrEdit/form/body.blade.php'));
		$this->assertStringContainsString("@includeWhen(!\$listingForm->isEdit, 'front.post.assistant.form-box')", $body);
		foreach (['multiSteps/create/post.blade.php', 'singleStep/create.blade.php'] as $form) {
			$this->assertStringContainsString("@include('front.post.createOrEdit.form.page'", file_get_contents(resource_path('views/front/post/createOrEdit/' . $form)), $form);
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
	
	public function test_the_redesigned_form_gives_guests_one_line_instead_of_the_box(): void
	{
		// Act: guests are off by default
		$html = view('front.post.assistant.form-box', ['lfV2' => true])->render();
		
		// Assert
		$this->assertStringContainsString('data-lf-ai-banner', $html);
		$this->assertStringContainsString('#quickLogin', $html);
		$this->assertStringNotContainsString('id="aiAssistant"', $html);
	}
	
	public function test_the_box_is_hidden_when_ai_posting_is_off(): void
	{
		// Arrange
		$this->setAi(['listing_assistant_enabled' => '0']);
		$this->actingAs($this->user(7));
		
		// Act
		$html = view('front.post.assistant.form-box')->render();
		
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

	public function test_the_box_has_a_message_for_each_kind_of_microphone_problem(): void
	{
		// Arrange
		$this->actingAs($this->user(7));

		// Act
		$html = $this->renderBox();

		// Assert: blocked, missing and busy microphones each get their own advice
		foreach (['assistant_voice_denied', 'assistant_voice_no_mic', 'assistant_voice_mic_busy'] as $key) {
			$this->assertStringContainsString(e(json_encode(trans('ai.' . $key), JSON_UNESCAPED_UNICODE)), $html, $key);
		}
	}

	public function test_every_translation_explains_a_missing_or_busy_microphone(): void
	{
		// Arrange
		$english = require lang_path('en/ai.php');

		foreach (glob(lang_path('*/ai.php')) as $file) {
			// Act
			$strings = require $file;

			// Assert
			foreach (['assistant_voice_no_mic', 'assistant_voice_mic_busy'] as $key) {
				$this->assertNotEmpty($strings[$key] ?? null, "{$file}: {$key}");
				if ($file !== lang_path('en/ai.php')) {
					$this->assertNotSame($english[$key], $strings[$key], "{$file}: {$key} is translated");
				}
			}
		}
	}

	public function test_only_a_refused_microphone_is_reported_as_blocked(): void
	{
		// Arrange
		$script = file_get_contents(public_path('assets/js/app/ai.formbox.js'));

		// Assert: the browser's error names decide the message, for recording and for dictation
		foreach (['NotAllowedError', 'NotFoundError', 'mic_missing', 'mic_busy', "'audio-capture'"] as $hook) {
			$this->assertStringContainsString($hook, $script, $hook);
		}
		$this->assertStringNotContainsString("}).catch(function () {\n\t\t\t\tsetMessage(plain('mic_denied')", $script, 'not every failure means blocked');
	}

	public function test_the_post_a_listing_link_opens_the_normal_form(): void
	{
		// Act & Assert
		[$url] = getCreateListingLinkInfo();
		$this->assertSame(urlGen()->addPost(), $url);
	}

	/** The box with the script stack it pushes to, as the layout prints it. */
	private function renderBox(): string
	{
		return \Illuminate\Support\Facades\Blade::render("@include('front.post.assistant.form-box')\n@stack('after_scripts_stack')");
	}
	
	private function recording(): UploadedFile
	{
		$samples = str_repeat("\x00\x00", 8000);
		$wav = 'RIFF' . pack('V', 36 + strlen($samples)) . 'WAVE' . 'fmt ' . pack('VvvVVvv', 16, 1, 1, 16000, 32000, 2, 16) . 'data' . pack('V', strlen($samples)) . $samples;
		
		return UploadedFile::fake()->createWithContent('voice.wav', $wav);
	}
}
