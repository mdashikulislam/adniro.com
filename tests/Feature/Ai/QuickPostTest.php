<?php

namespace Tests\Feature\Ai;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\Category;
use App\Models\Permission;
use App\Models\PostDraft;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\QuickPost\CatalogBuilder;
use App\Services\QuickPost\DraftToPostInput;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/*
 * Phase 6: quick post from text, the draft placed into the normal form, and the AI post limit (adniro:ai).
 */

class QuickPostTest extends TestCase
{
	use RefreshDatabase, QuickPostFixtures;
	
	
	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpQuickPost();
	}
	
	// --- Availability and pages -------------------------------------------
	
	public function test_quick_post_is_only_offered_when_switched_on_and_allowed(): void
	{
		$service = fn () => tap(app(QuickPostService::class), fn () => $this->app->forgetInstance(QuickPostService::class));
		$member = $this->user(7);
		
		$this->assertTrue($service()->available($member));
		$this->assertFalse($service()->available(null), 'guests are off by default');
		
		$this->setAi(['quickpost_guests' => '1']);
		$this->assertTrue($service()->available(null));
		
		config(['settings.listing_form.guest_can_submit_listings' => '0']);
		$this->assertFalse($service()->available(null), 'guests who cannot post at all');
		
		config(['settings.listing_form.publication_form_type' => 'single-step-form']);
		$this->assertFalse($service()->available($member), 'single-step form');
		
		config(['settings.listing_form.publication_form_type' => 'multi-steps-form']);
		$this->setAi(['quickpost_enabled' => '0']);
		$this->assertFalse($service()->available($member));
	}
	
	public function test_post_an_ad_opens_the_composer_when_available(): void
	{
		$this->actingAs($this->user(7));
		[$url] = getCreateListingLinkInfo();
		$this->assertSame(url('posts/create/quick'), $url);
		
		$this->setAi(['quickpost_enabled' => '0']);
		$this->app->forgetInstance(QuickPostService::class);
		[$url] = getCreateListingLinkInfo();
		$this->assertSame(urlGen()->addPost(), $url);
	}
	
	public function test_the_composer_shows_the_remaining_ai_posts(): void
	{
		$this->actingAs($this->user(7));
		$limit = app(QuickPostService::class)->limitStatus(auth()->user(), '127.0.0.1');
		
		$html = view('front.post.quick.partials.form', [
			'limit' => $limit, 'limitText' => '20 of 20 AI posts left today', 'formUrl' => urlGen()->addPost(),
			'draftUrl' => url('posts/create/quick/draft'), 'minText' => 10, 'maxText' => 2000, 'timeoutMs' => 30000,
		])->render();
		
		$this->assertStringContainsString('Describe what you&#039;re selling', $html);
		$this->assertStringContainsString('20 of 20 AI posts left today', $html);
		$this->assertStringContainsString('Fill the form myself', $html);
		$this->assertSame(20, $limit->remaining());
	}
	
	public function test_the_composer_sends_people_to_the_normal_form_when_quick_post_is_off(): void
	{
		$this->setAi(['quickpost_enabled' => '0']);
		
		$this->actingAs($this->user(7))->get('/posts/create/quick')->assertRedirect(urlGen()->addPost());
	}
	
	// --- Drafting ----------------------------------------------------------
	
	public function test_a_sentence_becomes_a_prefilled_form(): void
	{
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		
		$response = $this->actingAs($user)->postJson('/posts/create/quick/draft', [
			'text' => 'iPhone 13, 128 GB, blue, with box, 420 dollars, call me on +1 555 123 4567',
			'lat'  => 39.80,
			'lon'  => -89.60,
		]);
		
		$response->assertOk()->assertJson(['ok' => true, 'limit' => ['remaining' => 19]]);
		$draft = PostDraft::query()->sole();
		$this->assertTrue($draft->counted);
		$this->assertSame('drafted', $draft->status);
		$this->assertStringContainsString('iPhone 13', $this->ai->lastRequest->prompt);
		$this->assertStringContainsString('2 | Phones > Mobile phones', $this->ai->lastRequest->instructions);
		$this->assertSame('quickpost_draft', $this->ai->lastRequest->feature);
		
		$this->actingAs($user)->get($response->json('redirect'))->assertRedirect(urlGen()->addPost());
		
		$input = session('postInput');
		$this->assertSame(2, $input['category_id']);
		$this->assertSame('classified', $input['category_type']);
		$this->assertSame('Apple iPhone 13 128GB Blue', $input['title']);
		$this->assertSame('<p>Blue iPhone 13 with box.<br>Small scratch on the back. Call</p>', $input['description']);
		$this->assertStringNotContainsString('555', $input['description'], 'contact details are removed');
		$this->assertEquals(420, $input['price']);
		$this->assertSame(101, $input['cf'][10], 'select: option id');
		$this->assertSame('128', $input['cf'][11], 'number: whole number');
		$this->assertSame([121 => 121], $input['cf'][12], 'checkbox list: id => id');
		$this->assertArrayNotHasKey(15, $input['cf'], 'url fields are never filled');
		$this->assertArrayNotHasKey(99, $input['cf'], 'fields of other categories are ignored');
		$this->assertSame(600, $input['city_id'], 'the nearest city to the position');
		$this->assertSame('US', $input['country_code']);
		
		$marks = session(DraftToPostInput::MARKS_KEY);
		$this->assertSame($draft->token, $marks['draft']);
		$this->assertSame('check', $marks['fields']['description'], 'medium confidence');
		$this->assertSame('check', $marks['fields']['cf.11']);
		$this->assertArrayNotHasKey('title', $marks['fields']);
		$this->assertArrayNotHasKey('city_id', $marks['fields'], 'a city from the position needs no check');
	}
	
	public function test_the_sanitizer_never_trusts_the_model(): void
	{
		$answer = $this->draftAnswer();
		$answer['category'] = ['id' => 1, 'confidence' => 'high', 'alternatives' => [999, 3]]; // 1 is not a leaf, 999 does not exist
		$answer['price'] = ['stated' => false, 'amount' => 500, 'currency' => 'USD'];
		$answer['custom_fields'] = [['field_id' => 10, 'value' => 'Samsung', 'option_ids' => [], 'confidence' => 'high']];
		$answer['location'] = 'Chicago';
		$this->ai->answer($answer);
		
		$this->draftAndApply($this->user(7), 'Samsung laptop, nice condition');
		
		$input = session('postInput');
		$this->assertSame(3, $input['category_id'], 'the first valid alternative');
		$this->assertArrayNotHasKey('price', $input, 'a price the person did not state is dropped');
		$this->assertArrayNotHasKey('cf', $input, 'Brand does not belong to Laptops');
		$this->assertSame(700, $input['city_id'], 'the city named in the text');
		$marks = session(DraftToPostInput::MARKS_KEY)['fields'];
		$this->assertSame('check', $marks['category_id']);
		$this->assertSame('check', $marks['city_id']);
	}
	
	public function test_a_missing_category_and_required_fields_are_marked(): void
	{
		$answer = $this->draftAnswer();
		$answer['custom_fields'] = [];
		$answer['category']['confidence'] = 'low';
		$answer['price'] = ['stated' => true, 'amount' => 30000, 'currency' => 'BDT'];
		$answer['location'] = '';
		$this->ai->answer($answer);
		
		$this->draftAndApply($this->user(7), 'iPhone 13 blue, 30000 taka');
		
		$marks = session(DraftToPostInput::MARKS_KEY)['fields'];
		$this->assertSame('check', $marks['category_id']);
		$this->assertSame('missing', $marks['cf.10'], 'Brand is required');
		$this->assertSame('check', $marks['price'], 'a foreign currency');
		$this->assertSame('missing', $marks['city_id']);
		$this->assertEquals(30000, session('postInput')['price']);
	}
	
	public function test_contact_details_typed_earlier_are_kept(): void
	{
		$this->ai->answer($this->draftAnswer());
		session()->put('postInput', ['email' => 'me@example.test', 'contact_name' => 'Sam', 'title' => 'Old title']);
		
		$this->draftAndApply($this->user(7), 'iPhone 13 blue with box');
		
		$input = session('postInput');
		$this->assertSame('me@example.test', $input['email']);
		$this->assertSame('Sam', $input['contact_name']);
		$this->assertSame('Apple iPhone 13 128GB Blue', $input['title']);
	}
	
	public function test_a_guest_draft_belongs_to_the_browser_session_that_made_it(): void
	{
		$this->setAi(['quickpost_guests' => '1']);
		$this->ai->answer($this->draftAnswer());
		$service = app(QuickPostService::class);
		
		$outcome = $service->draft('iPhone 13 blue with box', null, '10.0.0.9', 'en', ['session_id' => 'session-a']);
		$this->assertTrue($outcome['ok']);
		$token = $outcome['draft']->token;
		
		$this->assertFalse($service->apply($token, null, 'session-b'), 'another browser');
		$this->assertFalse($service->apply($token, $this->user(7), 'session-a'), 'a member is not the guest');
		$this->assertTrue($service->apply($token, null, 'session-a'));
		$this->assertSame(2, session('postInput')['category_id']);
	}
	
	public function test_input_that_is_not_a_listing_stays_on_the_composer_and_does_not_count(): void
	{
		$answer = $this->draftAnswer();
		$answer['is_listable'] = false;
		$answer['unusable_reason'] = 'Please describe the item you want to sell.';
		$this->ai->answer($answer);
		
		$this->actingAs($this->user(7))->postJson('/posts/create/quick/draft', ['text' => 'hello how are you today'])
			->assertOk()
			->assertJson(['ok' => false, 'reason' => 'unusable', 'message' => 'Please describe the item you want to sell.'])
			->assertJsonMissingPath('redirect');
		
		$this->assertFalse(PostDraft::query()->sole()->counted);
	}
	
	public function test_when_the_ai_fails_the_text_goes_into_the_normal_form_and_does_not_count(): void
	{
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR, errorType: 'Timeout'));
		
		$this->actingAs($this->user(7))->postJson('/posts/create/quick/draft', ['text' => 'Sofa, grey, three seats, 150'])
			->assertOk()
			->assertJson(['ok' => false, 'reason' => 'ai_unavailable', 'redirect' => urlGen()->addPost()]);
		
		$this->assertSame('<p>Sofa, grey, three seats, 150</p>', session('postInput')['description']);
		$this->assertFalse(PostDraft::query()->sole()->counted);
		$this->assertSame('failed', PostDraft::query()->sole()->status);
	}
	
	public function test_after_an_earlier_draft_the_fallback_does_not_keep_its_fields(): void
	{
		$user = $this->user(7);
		$this->ai->answer($this->draftAnswer());
		$this->draftAndApply($user, 'iPhone 13 blue with box');
		session()->put('postInput', array_merge(session('postInput'), ['email' => 'me@example.test']));
		
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR));
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'Grey sofa, three seats, 150'])->assertJson(['reason' => 'ai_unavailable']);
		
		$input = session('postInput');
		$this->assertSame('<p>Grey sofa, three seats, 150</p>', $input['description']);
		$this->assertSame('me@example.test', $input['email']);
		$this->assertArrayNotHasKey('title', $input);
		$this->assertArrayNotHasKey('category_id', $input);
		$this->assertArrayNotHasKey('cf', $input);
		$this->assertNull(session(DraftToPostInput::MARKS_KEY));
	}
	
	public function test_too_short_text_is_refused_without_an_ai_call(): void
	{
		$this->actingAs($this->user(7))->postJson('/posts/create/quick/draft', ['text' => 'phone'])
			->assertJson(['ok' => false, 'reason' => 'too_short']);
		
		$this->assertSame(0, $this->ai->calls);
	}
	
	public function test_a_draft_can_only_be_applied_by_its_owner(): void
	{
		$this->ai->answer($this->draftAnswer());
		$owner = $this->user(7);
		$response = $this->actingAs($owner)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box']);
		$applyUrl = $response->json('redirect');
		session()->forget('postInput');
		
		$this->actingAs($this->user(8))->get($applyUrl)->assertRedirect(url('posts/create/quick'));
		$this->assertNull(session('postInput'));
		
		PostDraft::query()->update(['expires_at' => now()->subMinute()]);
		$this->actingAs($owner)->get($applyUrl)->assertRedirect(url('posts/create/quick'));
	}
	
	// --- The AI post limit -------------------------------------------------
	
	public function test_members_are_stopped_at_their_limit_and_told_when_it_resets(): void
	{
		$this->setAi(['quickpost_member_limit' => '2', 'quickpost_member_period' => 'week']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true, 'limit' => ['remaining' => 1]]);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 red with box'])->assertJson(['ok' => true, 'limit' => ['remaining' => 0]]);
		
		$response = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 11 black with box']);
		$response->assertStatus(429)->assertJson(['ok' => false, 'reason' => 'limit']);
		$this->assertStringContainsString('You have used all your AI posts', $response->json('message'));
		$this->assertSame(2, $this->ai->calls);
		$monday = now()->startOfWeek()->addWeek();
		$this->assertSame($monday->toIso8601String(), $response->json('limit.resets_at'), 'weeks reset on Monday');
		
		$this->travelTo($monday->copy()->addMinute());
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 11 black with box'])->assertJson(['ok' => true]);
	}
	
	public function test_failed_calls_and_free_redrafts_do_not_count(): void
	{
		$this->setAi(['quickpost_member_limit' => '1']);
		$user = $this->user(7);
		
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR));
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box']);
		
		$this->ai->answer($this->draftAnswer());
		$first = $this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->json('draft');
		$this->assertNotNull($first);
		
		// Two re-drafts of the same draft are free, the third is not
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with the box', 'redraft_of' => $first])->assertJson(['ok' => true]);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13, blue, box', 'redraft_of' => $first])->assertJson(['ok' => true]);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue box', 'redraft_of' => $first])->assertStatus(429);
		
		$this->assertSame(1, PostDraft::query()->where('counted', true)->count());
	}
	
	public function test_a_changed_limit_applies_at_once_and_no_limit_means_unlimited(): void
	{
		$this->setAi(['quickpost_member_limit' => '1']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box']);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 blue with box'])->assertStatus(429);
		
		$this->setAi(['quickpost_member_limit' => '3']);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 blue with box'])->assertJson(['ok' => true, 'limit' => ['remaining' => 1]]);
		
		$this->setAi(['quickpost_member_period' => 'none']);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 11 blue with box'])->assertJson(['ok' => true, 'limit' => ['limit' => null, 'remaining' => null]]);
	}
	
	public function test_a_user_override_and_reset_from_the_admin(): void
	{
		$this->setAi(['quickpost_member_limit' => '1']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box']);
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 blue with box'])->assertStatus(429);
		
		$admin = $this->admin();
		$this->actingAs($admin)->get('/admin/users/7/ai-post-limit')->assertOk()->assertSee('1 of 1 AI posts used this day');
		$this->actingAs($admin)->post('/admin/users/7/ai-post-limit', ['action' => 'reset'])->assertRedirect();
		$this->travel(2)->seconds();
		$this->actingAs($user->fresh())->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 blue with box'])->assertJson(['ok' => true]);
		
		$this->actingAs($admin)->post('/admin/users/7/ai-post-limit', ['action' => 'save', 'ai_post_limit' => 5]);
		$this->assertSame(5, (int)$user->fresh()->ai_post_limit);
		$this->actingAs($user->fresh())->postJson('/posts/create/quick/draft', ['text' => 'iPhone 11 blue with box'])->assertJson(['ok' => true, 'limit' => ['limit' => 5, 'remaining' => 3]]);
		
		$staff = $this->user(9);
		$staff->givePermissionTo(Permission::getStaffPermissions());
		// The app answers a forbidden admin page with a redirect to the login page
		$denied = $this->actingAs($staff)->get('/admin/users/7/ai-post-limit');
		$this->assertTrue($denied->isForbidden() || str_contains((string)$denied->headers->get('Location'), 'permission=forbidden'));
		$this->actingAs($staff)->post('/admin/users/7/ai-post-limit', ['action' => 'save', 'ai_post_limit' => 99]);
		$this->assertSame(5, (int)$user->fresh()->ai_post_limit);
	}
	
	public function test_guests_are_counted_by_ip_address(): void
	{
		$this->setAi(['quickpost_guests' => '1', 'quickpost_guest_limit' => '1']);
		$this->ai->answer($this->draftAnswer());
		
		$this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);
		$this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 blue with box'])->assertStatus(429);
		$this->withServerVariables(['REMOTE_ADDR' => '10.0.0.6'])->postJson('/posts/create/quick/draft', ['text' => 'iPhone 12 blue with box'])->assertJson(['ok' => true]);
	}
	
	public function test_bursts_are_rate_limited(): void
	{
		$this->setAi(['quickpost_member_period' => 'none']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		RateLimiter::clear('quickpost:user:7');
		
		for ($i = 0; $i < 10; $i++) {
			$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box ' . $i])->assertOk();
		}
		$this->actingAs($user)->postJson('/posts/create/quick/draft', ['text' => 'iPhone 13 blue with box'])->assertStatus(429)->assertJson(['reason' => 'rate']);
	}
	
	// --- Catalog -----------------------------------------------------------
	
	public function test_the_catalog_lists_active_leaves_and_usable_fields_and_is_rebuilt_on_change(): void
	{
		$text = app(CatalogBuilder::class)->text();
		
		$this->assertStringContainsString('2 | Phones > Mobile phones | classified | 10,11,12,14', $text, 'fields inherited from the parent');
		$this->assertStringContainsString('3 | Computers > Laptops | classified | -', $text);
		$this->assertStringNotContainsString("\n1 | Phones", $text, 'not a leaf');
		$this->assertStringNotContainsString('Invoice', $text, 'file fields are left out');
		$this->assertStringNotContainsString('Website', $text, 'url fields are left out');
		$this->assertStringContainsString('10 | Brand | select | required | 101=Apple; 102=Samsung', $text);
		$this->assertSame($text, app(CatalogBuilder::class)->text(), 'byte-identical between calls');
		
		Category::query()->withoutGlobalScopes()->find(3)->forceFill(['name' => ['en' => 'Notebooks']])->save();
		$this->assertStringContainsString('3 | Computers > Notebooks', app(CatalogBuilder::class)->text());
	}
	
	// ----------------------------------------------------------------------
	
	private function draftAndApply(?User $user, string $text, array $settings = []): void
	{
		if ($settings) {
			$this->setAi($settings);
			$this->app->forgetInstance(QuickPostService::class);
		}
		$request = $user ? $this->actingAs($user) : $this;
		$response = $request->postJson('/posts/create/quick/draft', ['text' => $text]);
		$response->assertJson(['ok' => true]);
		$request->get($response->json('redirect'))->assertRedirect(urlGen()->addPost());
	}
	
	
	
	
	
}
