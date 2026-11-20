<?php

namespace Tests\Feature\Ai;

use App\Models\PostDraft;
use App\Services\Ai\AiResult;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * Phase 7: photos in the quick post composer (adniro:ai).
 */

class QuickPostPhotosTest extends TestCase
{
	use RefreshDatabase, QuickPostFixtures;
	
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpQuickPost();
		
		Storage::fake(config('filesystems.default', 'public'));
		config([
			'settings.listing_form.pictures_limit' => 5,
			'settings.upload.max_image_size'       => 2500,
			'settings.upload.image_types'          => 'jpg,jpeg,png,webp',
		]);
	}
	
	public function test_a_photo_is_stored_where_the_photos_step_expects_it(): void
	{
		$response = $this->actingAs($this->user(7))
			->post('/posts/create/quick/photos', ['photo' => UploadedFile::fake()->image('phone.jpg', 2000, 1500)], ['Accept' => 'application/json']);
		
		$response->assertOk()->assertJson(['ok' => true, 'count' => 1, 'photo' => ['key' => 0]]);
		$photos = session('picturesInput');
		$this->assertCount(1, $photos);
		$this->assertStringStartsWith('temporary/' . session('uid') . '/', $photos[0]);
		Storage::disk(config('filesystems.default', 'public'))->assertExists($photos[0]);
	}
	
	public function test_the_photo_limit_unsupported_formats_and_non_images_are_refused(): void
	{
		config(['settings.listing_form.pictures_limit' => 2]);
		$user = $this->user(7);
		$upload = fn ($file) => $this->actingAs($user)->post('/posts/create/quick/photos', ['photo' => $file], ['Accept' => 'application/json']);
		
		$upload(UploadedFile::fake()->image('a.jpg'))->assertOk();
		$upload(UploadedFile::fake()->image('b.png'))->assertOk();
		$upload(UploadedFile::fake()->image('c.jpg'))->assertStatus(422)->assertJson(['ok' => false, 'message' => 'You can add up to 2 photos.']);
		
		session()->forget('picturesInput');
		$heic = $upload(UploadedFile::fake()->create('d.heic', 100, 'image/heic'));
		$heic->assertStatus(422);
		$this->assertStringContainsString('HEIC', $heic->json('message'));
		
		$upload(UploadedFile::fake()->create('e.pdf', 100, 'application/pdf'))->assertStatus(422);
		$this->assertNull(session('picturesInput'));
	}
	
	public function test_photo_uploads_need_quick_post(): void
	{
		$this->setAi(['quickpost_enabled' => '0']);
		$this->app->forgetInstance(QuickPostService::class);
		
		$this->actingAs($this->user(7))
			->post('/posts/create/quick/photos', ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
			->assertForbidden();
	}
	
	public function test_a_photo_alone_is_enough_and_is_sent_to_the_model(): void
	{
		$user = $this->user(7);
		$this->actingAs($user)->post('/posts/create/quick/photos', ['photo' => UploadedFile::fake()->image('phone.jpg', 1200, 900)], ['Accept' => 'application/json'])->assertOk();
		$this->ai->answer($this->draftAnswer());
		
		$response = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => '']);
		
		$response->assertOk()->assertJson(['ok' => true]);
		$request = $this->ai->lastRequest;
		$this->assertCount(1, $request->images);
		$this->assertStringContainsString('<photos>1</photos>', $request->prompt);
		$this->assertStringContainsString('attached photo(s) show the item', $request->prompt);
		$this->assertSame(session('picturesInput'), PostDraft::query()->sole()->picture_paths);
		
		// Applying the draft leaves the photos where the photos step reads them
		$photos = session('picturesInput');
		$this->actingAs($user)->get($response->json('redirect'))->assertRedirect(urlGen()->addPost());
		$this->assertSame($photos, session('picturesInput'));
		$this->assertSame(2, session('postInput')['category_id']);
	}
	
	public function test_at_most_four_photos_go_to_the_model(): void
	{
		$user = $this->user(7);
		for ($i = 0; $i < 5; $i++) {
			$this->actingAs($user)->post('/posts/create/quick/photos', ['photo' => UploadedFile::fake()->image("p{$i}.jpg", 600, 600)], ['Accept' => 'application/json'])->assertOk();
		}
		$this->ai->answer($this->draftAnswer());
		
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'phone'])->assertJson(['ok' => true]);
		
		$this->assertCount(4, $this->ai->lastRequest->images);
		$this->assertCount(5, session('picturesInput'), 'all five stay for the listing');
	}
	
	public function test_without_photos_short_text_is_still_refused(): void
	{
		$this->actingAs($this->user(7))->postJson('/posts/create/quick/draft', ['text' => 'phone'])->assertJson(['ok' => false, 'reason' => 'too_short']);
		$this->assertSame(0, $this->ai->calls);
	}
	
	public function test_when_the_ai_fails_the_photos_are_kept_for_the_normal_form(): void
	{
		$user = $this->user(7);
		$this->actingAs($user)->post('/posts/create/quick/photos', ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
		$photos = session('picturesInput');
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR));
		
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => ''])
			->assertJson(['ok' => false, 'reason' => 'ai_unavailable', 'redirect' => urlGen()->addPost()]);
		
		$this->assertSame($photos, session('picturesInput'));
		$this->assertArrayNotHasKey('description', (array)session('postInput', []));
	}
	
	public function test_a_blurry_photo_stays_on_the_composer(): void
	{
		$user = $this->user(7);
		$this->actingAs($user)->post('/posts/create/quick/photos', ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
		$answer = $this->draftAnswer();
		$answer['is_listable'] = false;
		$answer['unusable_reason'] = 'The photo is too blurry. Please add a clearer photo or one sentence.';
		$this->ai->answer($answer);
		
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => ''])
			->assertJson(['ok' => false, 'reason' => 'unusable', 'message' => 'The photo is too blurry. Please add a clearer photo or one sentence.']);
		$this->assertFalse(PostDraft::query()->sole()->counted);
	}
	
	public function test_the_composer_shows_photos_already_added(): void
	{
		$html = view('front.post.quick.partials.form', [
			'limit' => app(QuickPostService::class)->limitStatus(null, '127.0.0.1'), 'limitText' => null,
			'formUrl' => urlGen()->addPost(), 'draftUrl' => '/d', 'minText' => 10, 'maxText' => 2000, 'timeoutMs' => 30000,
			'photos' => [['key' => 0, 'url' => '/thumb/a.jpg'], ['key' => 3, 'url' => '/thumb/b.jpg']], 'photoLimit' => 2,
		])->render();
		
		$this->assertStringContainsString('data-key="3"', $html);
		$this->assertStringContainsString('/thumb/b.jpg', $html);
		$this->assertMatchesRegularExpression('/id="qpPhotoTile"\s+class="[^"]*d-none/', $html, 'the add tile hides at the limit');
	}
	
	public function test_the_composer_page_includes_its_form_and_scripts(): void
	{
		/* The full page needs the site layout, which a test cannot render: check the compiled template */
		$compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/front/post/quick/composer.blade.php')));
		
		$this->assertStringContainsString('front.post.quick.partials.form', $compiled);
		$this->assertStringContainsString('front.post.quick.partials.overlay', $compiled);
		foreach (['qpPhotoInput', 'shrink', 'qp-photo-remove', 'redraft_of', 'AbortController'] as $hook) {
			$this->assertStringContainsString($hook, $compiled, $hook);
		}
		$this->assertStringNotContainsString('//', preg_replace('~https?://~', '', $compiled), 'no line comments: the HTML minifier removes line breaks');
	}
	
	public function test_the_purge_removes_expired_drafts_and_old_temporary_uploads(): void
	{
		$disk = Storage::disk(config('filesystems.default', 'public'));
		$disk->put('temporary/old1/a.jpg', 'x');
		$disk->put('temporary/new1/b.jpg', 'x');
		touch($disk->path('temporary/old1/a.jpg'), now()->subHours(50)->getTimestamp());
		
		$expired = PostDraft::query()->create(['token' => str_repeat('a', 32), 'status' => 'drafted', 'expires_at' => now()->subHour()]);
		$fresh = PostDraft::query()->create(['token' => str_repeat('b', 32), 'status' => 'drafted', 'expires_at' => now()->addHour()]);
		
		$this->artisan('ai:quickpost-purge', ['--dry-run' => true])->expectsOutputToContain('Would delete: 1 expired drafts, 1 temporary files')->assertSuccessful();
		$disk->assertExists('temporary/old1/a.jpg');
		
		$this->artisan('ai:quickpost-purge')->assertSuccessful();
		
		$disk->assertMissing('temporary/old1/a.jpg');
		$this->assertFalse($disk->exists('temporary/old1'), 'the empty folder is removed');
		$disk->assertExists('temporary/new1/b.jpg');
		$this->assertNull(PostDraft::query()->find($expired->id));
		$this->assertNotNull(PostDraft::query()->find($fresh->id));
	}
}
