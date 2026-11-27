<?php

namespace Tests\Feature\Ai;

use App\Models\Post;
use App\Models\PostDraft;
use App\Services\ListingAssistant\DraftUse;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Listings filled in by the AI listing assistant are marked, and the admin's listings table says so
 * (adniro:ai): the form carries the draft it was filled from, and publishing ties the two together.
 */

class AiMadeListingTest extends TestCase
{
	use RefreshDatabase, ListingAssistantFixtures;

	private const TOKEN = 'draft-token-0123456789';

	protected function setUp(): void
	{
		parent::setUp();
		$this->setUpListingAssistant();
	}

	// --- The form carries the draft ----------------------------------------

	public function test_the_form_has_an_empty_field_for_the_draft_it_is_filled_from(): void
	{
		// Arrange
		$this->actingAs($this->user(7));

		// Act
		$html = $this->renderBox();

		// Assert
		$this->assertStringContainsString('<input type="hidden" name="ai_draft" id="aiDraft" value="">', $html);
	}

	public function test_the_draft_stays_in_the_form_when_the_seller_comes_back_to_it(): void
	{
		// Arrange
		$this->actingAs($this->user(7));
		session(['postInput' => ['title' => 'Blue iPhone', 'ai_draft' => self::TOKEN]]);

		// Act
		$html = $this->renderBox();

		// Assert
		$this->assertStringContainsString('name="ai_draft" id="aiDraft" value="' . self::TOKEN . '"', $html);
	}

	public function test_the_script_sets_the_draft_on_a_fill_and_takes_it_back_on_undo(): void
	{
		// Arrange
		$script = file_get_contents(public_path('assets/js/app/ai.formbox.js'));

		// Assert
		$this->assertStringContainsString("document.getElementById('aiDraft')", $script);
		$this->assertStringContainsString('setDraft(lastDraft)', $script, 'set when a draft fills the form');
		$this->assertStringContainsString('setDraft(s.draft)', $script, 'back to what it was on undo');
	}

	// --- Which draft counts ------------------------------------------------

	public function test_a_members_own_draft_counts_for_their_listing(): void
	{
		// Arrange
		$member = $this->user(7);
		$draft = $this->draft(['user_id' => 7]);

		// Act
		$found = app(DraftUse::class)->find(self::TOKEN, $member);

		// Assert
		$this->assertTrue($draft->is($found));
	}

	public function test_a_draft_made_before_signing_in_counts_too(): void
	{
		// Arrange
		$member = $this->user(7);
		$this->draft(['user_id' => null]);

		// Act & Assert
		$this->assertNotNull(app(DraftUse::class)->find(self::TOKEN, $member));
		$this->assertNotNull(app(DraftUse::class)->find(self::TOKEN, null));
	}

	public function test_someone_elses_draft_a_used_one_or_a_failed_one_does_not_count(): void
	{
		// Arrange
		$member = $this->user(7);
		$this->user(8);
		$this->draft(['user_id' => 8, 'token' => 'theirs']);
		$this->draft(['user_id' => 7, 'token' => 'used', 'post_id' => 55, 'status' => PostDraft::POSTED]);
		$this->draft(['user_id' => 7, 'token' => 'failed', 'status' => PostDraft::FAILED]);
		$uses = app(DraftUse::class);

		// Act & Assert
		$this->assertNull($uses->find('theirs', $member));
		$this->assertNull($uses->find('used', $member));
		$this->assertNull($uses->find('failed', $member));
		$this->assertNull($uses->find('unknown', $member));
		$this->assertNull($uses->find('', $member));
		$this->assertNull($uses->find(null, $member));
	}

	// --- Publishing --------------------------------------------------------

	public function test_a_listing_published_from_a_draft_is_marked_and_tied_to_it(): void
	{
		// Arrange
		$member = $this->user(7);
		$draft = $this->draft(['user_id' => 7]);

		// Act
		$post = $this->publish($member, ['ai_draft' => self::TOKEN]);

		// Assert
		$this->assertTrue((bool)$post->made_with_ai);
		$draft->refresh();
		$this->assertSame($post->id, (int)$draft->post_id);
		$this->assertSame(PostDraft::POSTED, $draft->status);
	}

	public function test_a_listing_typed_by_hand_is_not_marked(): void
	{
		// Arrange
		$member = $this->user(7);
		$this->draft(['user_id' => 7]);

		// Act: the member has a draft, but this form was not filled from it
		$post = $this->publish($member);

		// Assert
		$this->assertFalse((bool)$post->made_with_ai);
		$this->assertNull(PostDraft::query()->where('token', self::TOKEN)->value('post_id'));
	}

	public function test_the_mark_cannot_be_posted_as_a_form_field(): void
	{
		// Arrange
		$member = $this->user(7);

		// Act
		$post = $this->publish($member, ['made_with_ai' => 1, 'ai_draft' => 'not-a-draft']);

		// Assert
		$this->assertFalse((bool)$post->made_with_ai);
	}

	// --- Admin -------------------------------------------------------------

	public function test_the_admin_listings_table_labels_listings_made_with_ai(): void
	{
		// Arrange
		$member = $this->user(7);
		$this->draft(['user_id' => 7]);
		$withAi = $this->publish($member, ['ai_draft' => self::TOKEN]);
		$byHand = $this->publish($member, ['title' => 'Grey corner sofa, three seats']);

		// Act
		$withAiCell = $withAi->crudTitleColumn();
		$byHandCell = $byHand->crudTitleColumn();

		// Assert
		$this->assertStringContainsString('<span class="badge bg-info"', $withAiCell);
		$this->assertStringContainsString('Made with AI', $withAiCell);
		$this->assertStringNotContainsString('Made with AI', $byHandCell);
	}

	private function draft(array $overrides = []): PostDraft
	{
		return PostDraft::query()->create(array_merge([
			'token'   => self::TOKEN,
			'status'  => PostDraft::DRAFTED,
			'counted' => true,
			'draft'   => ['values' => ['title' => 'Apple iPhone 13 128GB Blue']],
		], $overrides));
	}

	/** Publishes a listing the way the listing form's last step and the API do */
	private function publish($member, array $input = []): Post
	{
		$this->actingAs($member);
		$request = Request::create('/posts/create', 'POST', array_merge([
			'category_id'  => 2,
			'post_type_id' => 1,
			'title'        => 'Apple iPhone 13 128GB Blue',
			'description'  => 'Blue iPhone 13 with its box. Small scratch on the back, works perfectly.',
			'price'        => 420,
			'contact_name' => $member->name,
			'auth_field'   => 'email',
			'email'        => $member->email,
			'city_id'      => 600,
			'country_code' => 'US',
			'accept_terms' => 1,
		], $input));
		$request->setLaravelSession(app('session.store'));

		$data = getServiceData(app(PostService::class)->store($request));
		$this->assertTrue((bool)data_get($data, 'success'), (string)data_get($data, 'message'));

		return Post::query()->withoutGlobalScopes()->findOrFail(data_get($data, 'result.id'));
	}

	private function renderBox(): string
	{
		return Blade::render("@include('front.post.assistant.form-box')");
	}
}
