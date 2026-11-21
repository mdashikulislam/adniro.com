<?php

namespace Tests\Feature\Ai;

use App\Models\Category;
use App\Models\Permission;
use App\Models\PostDraft;
use App\Services\Ai\AiResult;
use App\Services\QuickPost\CatalogBuilder;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
	
	public function test_the_old_composer_address_sends_people_to_the_normal_form(): void
	{
		$this->actingAs($this->user(7))->get('/posts/create/quick')->assertRedirect(urlGen()->addPost());
	}
	
	// --- Drafting ----------------------------------------------------------
	
	public function test_a_sentence_becomes_the_fields_of_the_form(): void
	{
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		
		$response = $this->actingAs($user)->postJson('/posts/create/quick/fill', [
			'text' => 'iPhone 13, 128 GB, blue, with box, 420 dollars, call me on +1 555 123 4567',
		]);
		
		$response->assertOk()->assertJson(['ok' => true, 'limit' => ['remaining' => 19]]);
		$draft = PostDraft::query()->sole();
		$this->assertTrue($draft->counted);
		$this->assertSame('drafted', $draft->status);
		$this->assertStringContainsString('iPhone 13', $this->ai->lastRequest->prompt);
		$this->assertStringContainsString('2 | Phones > Mobile phones', $this->ai->lastRequest->instructions);
		$this->assertSame('quickpost_draft', $this->ai->lastRequest->feature);
		
		$fields = $response->json('fields');
		$this->assertSame(2, $fields['category']['id']);
		$this->assertSame('classified', $fields['category']['type']);
		$this->assertSame('Apple iPhone 13 128GB Blue', $fields['title']);
		$this->assertSame('<p>Blue iPhone 13 with box.<br>Small scratch on the back. Call</p>', $fields['html']);
		$this->assertStringNotContainsString('555', $fields['text'], 'contact details are removed');
		$this->assertEquals(420, $fields['price']);
		$this->assertSame(101, $fields['cf'][10], 'select: option id');
		$this->assertSame('128', $fields['cf'][11], 'number: whole number');
		$this->assertSame([121], $fields['cf'][12], 'checkbox list: the ticked option ids');
		$this->assertArrayNotHasKey(15, $fields['cf'], 'url fields are never filled');
		$this->assertArrayNotHasKey(99, $fields['cf'], 'fields of other categories are ignored');
		$this->assertSame('check', $fields['marks']['description'], 'medium confidence');
		$this->assertSame('check', $fields['marks']['cf.11']);
		$this->assertArrayNotHasKey('title', $fields['marks']);
	}
	
	public function test_the_city_nearest_to_a_given_position_is_chosen(): void
	{
		$this->ai->answer($this->draftAnswer());
		
		$outcome = app(QuickPostService::class)->draft('iPhone 13 blue with box', $this->user(7), '127.0.0.1', 'en', ['lat' => 39.80, 'lon' => -89.60]);
		
		$this->assertSame(600, $outcome['draft']->draft['city_id']);
		$this->assertArrayNotHasKey('city_id', $outcome['draft']->draft['marks'], 'a city from the position needs no check');
	}
	
	public function test_the_sanitizer_never_trusts_the_model(): void
	{
		$answer = $this->draftAnswer();
		$answer['category'] = ['id' => 1, 'confidence' => 'high', 'alternatives' => [999, 3]]; // 1 is not a leaf, 999 does not exist
		$answer['price'] = ['stated' => false, 'amount' => 500, 'currency' => 'USD'];
		$answer['custom_fields'] = [['field_id' => 10, 'value' => 'Samsung', 'option_ids' => [], 'confidence' => 'high']];
		$answer['location'] = 'Chicago';
		$this->ai->answer($answer);
		
		$fields = $this->actingAs($this->user(7))->postJson('/posts/create/quick/fill', ['text' => 'Samsung laptop, nice condition'])->json('fields');
		
		$this->assertSame(3, $fields['category']['id'], 'the first valid alternative');
		$this->assertNull($fields['price'], 'a price the person did not state is dropped');
		$this->assertSame([], $fields['cf'], 'Brand does not belong to Laptops');
		$this->assertSame(700, $fields['city']['id'], 'the city named in the text');
		$this->assertSame('check', $fields['marks']['category_id']);
		$this->assertSame('check', $fields['marks']['city_id']);
	}
	
	public function test_a_missing_category_and_required_fields_are_marked(): void
	{
		$answer = $this->draftAnswer();
		$answer['custom_fields'] = [];
		$answer['category']['confidence'] = 'low';
		$answer['price'] = ['stated' => true, 'amount' => 30000, 'currency' => 'BDT'];
		$answer['location'] = '';
		$this->ai->answer($answer);
		
		$fields = $this->actingAs($this->user(7))->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue, 30000 taka'])->json('fields');
		
		$this->assertSame('check', $fields['marks']['category_id']);
		$this->assertSame('missing', $fields['marks']['cf.10'], 'Brand is required');
		$this->assertSame('check', $fields['marks']['price'], 'a foreign currency');
		$this->assertNull($fields['city']);
		$this->assertEquals(30000, $fields['price']);
	}
	
	public function test_a_guest_draft_belongs_to_the_browser_session_that_made_it(): void
	{
		$this->setAi(['quickpost_guests' => '1']);
		$this->ai->answer($this->draftAnswer());
		$service = app(QuickPostService::class);
		
		$outcome = $service->draft('iPhone 13 blue with box', null, '10.0.0.9', 'en', ['session_id' => 'session-a']);
		$this->assertTrue($outcome['ok']);
		$token = $outcome['draft']->token;
		
		$this->assertNull($service->ownedDraft($token, null, 'session-b'), 'another browser');
		$this->assertNull($service->ownedDraft($token, $this->user(7), 'session-a'), 'a member is not the guest');
		$this->assertSame($outcome['draft']->id, $service->ownedDraft($token, null, 'session-a')?->id);
	}
	
	public function test_input_that_is_not_a_listing_is_explained_and_does_not_count(): void
	{
		$answer = $this->draftAnswer();
		$answer['is_listable'] = false;
		$answer['unusable_reason'] = 'Please describe the item you want to sell.';
		$this->ai->answer($answer);
		
		$this->actingAs($this->user(7))->postJson('/posts/create/quick/fill', ['text' => 'hello how are you today'])
			->assertStatus(422)
			->assertJson(['ok' => false, 'reason' => 'unusable', 'message' => 'Please describe the item you want to sell.'])
			->assertJsonMissingPath('fields');
		
		$this->assertFalse(PostDraft::query()->sole()->counted);
	}
	
	public function test_when_the_ai_fails_nothing_is_counted(): void
	{
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR, errorType: 'Timeout'));
		
		$this->actingAs($this->user(7))->postJson('/posts/create/quick/fill', ['text' => 'Sofa, grey, three seats, 150'])
			->assertStatus(503)
			->assertJson(['ok' => false, 'reason' => 'ai_unavailable']);
		
		$this->assertFalse(PostDraft::query()->sole()->counted);
		$this->assertSame('failed', PostDraft::query()->sole()->status);
	}
	
	public function test_too_short_text_is_refused_without_an_ai_call(): void
	{
		$this->actingAs($this->user(7))->postJson('/posts/create/quick/fill', ['text' => 'phone'])
			->assertJson(['ok' => false, 'reason' => 'too_short']);
		
		$this->assertSame(0, $this->ai->calls);
	}
	
	// --- The AI post limit -------------------------------------------------
	
	public function test_members_are_stopped_at_their_limit_and_told_when_it_resets(): void
	{
		$this->setAi(['quickpost_member_limit' => '2', 'quickpost_member_period' => 'week']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true, 'limit' => ['remaining' => 1]]);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 12 red with box'])->assertJson(['ok' => true, 'limit' => ['remaining' => 0]]);
		
		$response = $this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 11 black with box']);
		$response->assertStatus(429)->assertJson(['ok' => false, 'reason' => 'limit']);
		$this->assertStringContainsString('You have used all your AI posts', $response->json('message'));
		$this->assertSame(2, $this->ai->calls);
		$monday = now()->startOfWeek()->addWeek();
		$this->assertSame($monday->toIso8601String(), $response->json('limit.resets_at'), 'weeks reset on Monday');
		
		$this->travelTo($monday->copy()->addMinute());
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 11 black with box'])->assertJson(['ok' => true]);
	}
	
	public function test_failed_calls_and_free_redrafts_do_not_count(): void
	{
		$this->setAi(['quickpost_member_limit' => '1']);
		$user = $this->user(7);
		
		$this->ai->respond(fn () => new AiResult(AiResult::ERROR));
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box']);
		
		$this->ai->answer($this->draftAnswer());
		$first = $this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box'])->json('draft');
		$this->assertNotNull($first);
		
		// Two re-drafts of the same draft are free, the third is not
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with the box', 'redraft_of' => $first])->assertJson(['ok' => true]);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13, blue, box', 'redraft_of' => $first])->assertJson(['ok' => true]);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue box', 'redraft_of' => $first])->assertStatus(429);
		
		$this->assertSame(1, PostDraft::query()->where('counted', true)->count());
	}
	
	public function test_a_changed_limit_applies_at_once_and_no_limit_means_unlimited(): void
	{
		$this->setAi(['quickpost_member_limit' => '1']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box']);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 12 blue with box'])->assertStatus(429);
		
		$this->setAi(['quickpost_member_limit' => '3']);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 12 blue with box'])->assertJson(['ok' => true, 'limit' => ['remaining' => 1]]);
		
		$this->setAi(['quickpost_member_period' => 'none']);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 11 blue with box'])->assertJson(['ok' => true, 'limit' => ['limit' => null, 'remaining' => null]]);
	}
	
	public function test_a_user_override_and_reset_from_the_admin(): void
	{
		$this->setAi(['quickpost_member_limit' => '1']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box']);
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 12 blue with box'])->assertStatus(429);
		
		$admin = $this->admin();
		$this->actingAs($admin)->get('/admin/users/7/ai-post-limit')->assertOk()->assertSee('1 of 1 AI posts used this day');
		$this->actingAs($admin)->post('/admin/users/7/ai-post-limit', ['action' => 'reset'])->assertRedirect();
		$this->travel(2)->seconds();
		$this->actingAs($user->fresh())->postJson('/posts/create/quick/fill', ['text' => 'iPhone 12 blue with box'])->assertJson(['ok' => true]);
		
		$this->actingAs($admin)->post('/admin/users/7/ai-post-limit', ['action' => 'save', 'ai_post_limit' => 5]);
		$this->assertSame(5, (int)$user->fresh()->ai_post_limit);
		$this->actingAs($user->fresh())->postJson('/posts/create/quick/fill', ['text' => 'iPhone 11 blue with box'])->assertJson(['ok' => true, 'limit' => ['limit' => 5, 'remaining' => 3]]);
		
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
		
		$this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box'])->assertJson(['ok' => true]);
		$this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])->postJson('/posts/create/quick/fill', ['text' => 'iPhone 12 blue with box'])->assertStatus(429);
		$this->withServerVariables(['REMOTE_ADDR' => '10.0.0.6'])->postJson('/posts/create/quick/fill', ['text' => 'iPhone 12 blue with box'])->assertJson(['ok' => true]);
	}
	
	public function test_bursts_are_rate_limited(): void
	{
		$this->setAi(['quickpost_member_period' => 'none']);
		$this->ai->answer($this->draftAnswer());
		$user = $this->user(7);
		RateLimiter::clear('quickpost:user:7');
		
		for ($i = 0; $i < 10; $i++) {
			$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box ' . $i])->assertOk();
		}
		$this->actingAs($user)->postJson('/posts/create/quick/fill', ['text' => 'iPhone 13 blue with box'])->assertStatus(429)->assertJson(['reason' => 'rate']);
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
}
