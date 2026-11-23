<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\ChatMessages;
use Illuminate\Support\Facades\DB;

/*
 * The chat pages and the places that lead to them (adniro): the inbox, a conversation, a new conversation,
 * the listing's "Chat with seller", the unread badge, the phone app bar, and the old Messenger's links.
 */
class ChatPagesTest extends ChatTestCase
{
	public function test_the_inbox_page_carries_my_conversations_and_loads_the_chat(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?']);

		// Act
		$response = $this->actingAs($this->seller)->get('/chat');

		// Assert
		$response->assertOk()->assertSee('adn-chat-page', false)->assertDontSee('is-chat-open', false);
		$state = $this->chatState($response->getContent());
		$this->assertSame($thread->id, $state['inbox']['threads'][0]['id']);
		$this->assertSame(1, $state['inbox']['threads'][0]['unread']);
		$this->assertSame(1, $state['unread_total']);
		$this->assertNull($state['open']);
		$this->assertSame(['id' => self::SELLER_ID, 'name' => 'Member ' . self::SELLER_ID], $state['me']);
		$this->assertCount(4, $state['quick_replies']);
		foreach (['chat-kit', 'chat-inbox', 'chat-log', 'chat-safety', 'chat-photo', 'chat'] as $script) {
			$response->assertSee('assets/js/app/' . $script . '.js', false);
		}
		$response->assertSee('assets/css/chat.css', false);
	}

	public function test_a_conversation_page_opens_with_its_latest_messages(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);

		// Act
		$response = $this->actingAs($this->buyer)->get('/chat/' . $thread->id);

		// Assert
		$response->assertOk()->assertSee('is-chat-open', false);
		$state = $this->chatState($response->getContent());
		$this->assertSame($thread->id, $state['open']['thread']['id']);
		$this->assertSame(['Is this still available?', 'Yes, it is.'], array_column($state['open']['messages'], 'body'));
		$this->assertSame('Member ' . self::SELLER_ID, $state['open']['thread']['other']['name']);
		$this->assertSame('Grey corner sofa', $state['open']['thread']['post']['title']);
		$this->assertTrue($state['open']['thread']['post']['available']);
		$this->assertArrayNotHasKey('email', $state['open']['thread']['other']);
		// The pinned listing says why it is there
		$response->assertSee('data-chat-listing-label', false);
		$this->assertSame(['Last enquiry', 'Asking about'], [$state['text']['last_enquiry'], $state['text']['asking_about']]);
	}

	public function test_text_in_the_page_data_cannot_break_out_of_it(): void
	{
		// Arrange
		$thread = $this->conversation(['Hello']);
		ChatMessages::send($thread, self::SELLER_ID, '</script><img src=x onerror=alert(1)>');

		// Act
		$html = $this->actingAs($this->buyer)->get('/chat/' . $thread->id)->assertOk()->getContent();

		// Assert
		$this->assertStringNotContainsString('</script><img src=x', $html);
		$this->assertSame('</script><img src=x onerror=alert(1)>', $this->chatState($html)['open']['messages'][1]['body']);
	}

	public function test_a_new_conversation_page_saves_nothing_until_the_first_message(): void
	{
		// Act
		$response = $this->actingAs($this->buyer)->get('/chat/new/' . $this->listing->id);

		// Assert
		$response->assertOk()->assertSee('is-chat-open', false);
		$state = $this->chatState($response->getContent());
		$this->assertSame('Grey corner sofa', $state['draft']['post']['title']);
		$this->assertSame(self::SELLER_ID, $state['draft']['other']['id']);
		$this->assertStringEndsWith('/chat/listings/' . $this->listing->id, $state['draft']['start_url']);
		$this->assertSame(0, DB::table('threads')->count());
	}

	public function test_chat_with_seller_goes_to_the_conversation_that_already_exists(): void
	{
		// Arrange
		$thread = $this->conversation();

		// Act & Assert
		$this->actingAs($this->buyer)->get('/chat/new/' . $this->listing->id)->assertRedirect('/chat/' . $thread->id);
	}

	public function test_chat_with_seller_on_another_listing_opens_the_same_conversation_about_that_listing(): void
	{
		// Arrange
		$thread = $this->conversation();
		$table = $this->stockListing('Oak dining table', ['user_id' => self::SELLER_ID]);

		// Act
		$response = $this->actingAs($this->buyer)->get('/chat/new/' . $table->id);

		// Assert: the existing conversation, with the new listing ready for the next message; nothing saved yet
		$response->assertOk();
		$state = $this->chatState($response->getContent());
		$this->assertSame($thread->id, $state['open']['thread']['id']);
		$this->assertSame('Oak dining table', $state['open']['about']['post']['title']);
		$this->assertStringEndsWith('/chat/listings/' . $table->id, $state['open']['about']['start_url']);
		$this->assertCount(1, $state['open']['messages']);
		$this->assertSame(1, DB::table('threads')->count());
		$this->assertSame($this->listing->id, (int)DB::table('threads')->value('post_id'));
	}

	public function test_a_listing_that_is_gone_is_shown_as_no_longer_available(): void
	{
		// Arrange
		$thread = $this->conversation();
		DB::table('posts')->where('id', $this->listing->id)->update(['archived_at' => now()]);

		// Act
		$post = $this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->json('thread.post');

		// Assert
		$this->assertFalse($post['available']);
		$this->assertNull($post['url']);
		$this->actingAs($this->outsider)->get('/chat/new/' . $this->listing->id)->assertNotFound();
	}

	public function test_the_listing_offers_chat_to_members_sign_in_to_guests_and_nothing_to_its_owner(): void
	{
		// Act
		$forGuest = chatStartUrl($this->listing);
		$this->actingAs($this->buyer);
		$forMember = chatStartUrl($this->listing);
		$button = genEmailContactBtn($this->listing, true);
		$this->actingAs($this->seller);
		$forOwner = chatStartUrl($this->listing);

		// Assert
		$this->assertSame('#quickLogin', $forGuest);
		$this->assertStringEndsWith('/chat/new/' . $this->listing->id, $forMember);
		$this->assertStringContainsString('Chat with seller', $button);
		$this->assertStringContainsString('/chat/new/' . $this->listing->id, $button);
		$this->assertNull($forOwner);
		$this->assertSame('', genEmailContactBtn($this->listing, true));
	}

	public function test_a_conversation_shows_a_safety_reminder_linking_to_the_tips_page(): void
	{
		// Arrange
		DB::table('pages')->insert([
			'name' => json_encode(['en' => 'Safety']), 'slug' => 'anti-scam', 'title' => json_encode(['en' => 'Safety']),
			'content' => json_encode(['en' => 'Stay safe']), 'type' => 'tips', 'active' => 1,
		]);

		// Act
		$html = $this->actingAs($this->buyer)->get('/chat/new/' . $this->listing->id)->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('Stay safe:', $html);
		$this->assertMatchesRegularExpression('/adn-chat-safety.*anti-scam/s', $html);
	}

	public function test_guests_get_no_chat_code(): void
	{
		// Act
		$html = $this->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringNotContainsString('adnChatLive', $html);
		$this->assertStringNotContainsString('chat-live.js', $html);
		$this->assertStringNotContainsString('pusher', $html);
		$this->assertStringNotContainsString('data-adn-chat-badge', $html);
	}

	public function test_members_get_the_unread_badge_and_live_settings_on_every_page(): void
	{
		// Arrange
		$this->conversation(['Is this still available?']);

		// Act
		$html = $this->actingAs($this->seller)->get('/')->assertOk()->getContent();

		// Assert
		$this->assertStringContainsString('assets/js/app/chat-live.js', $html);
		$live = $this->jsonFrom($html, 'adnChatLive');
		$this->assertSame(self::SELLER_ID, $live['user_id']);
		$this->assertSame(1, $live['unread_total']);
		$this->assertNull($live['live'], 'No live server is configured in tests: nothing to connect to.');
		$this->assertStringContainsString('<span class="badge rounded-pill text-bg-danger" data-adn-chat-badge>1</span>', $html);
		$this->assertStringNotContainsString('timerNewMessagesChecking', $html);
	}

	public function test_with_a_live_server_configured_members_are_told_where_to_connect(): void
	{
		// Arrange
		config([
			'broadcasting.default' => 'reverb',
			'broadcasting.connections.reverb.key' => 'test-key',
			'broadcasting.connections.reverb.secret' => 'test-secret',
			'broadcasting.connections.reverb.app_id' => 'test-app',
		]);

		// Act
		$live = $this->jsonFrom($this->actingAs($this->buyer)->get('/chat')->assertOk()->getContent(), 'adnChatLive')['live'];

		// Assert: the site's own address on plain http (local), never the secret
		$this->assertSame('test-key', $live['key']);
		$this->assertSame([request()->getHost(), 8080, 'http'], [$live['host'], $live['port'], $live['scheme']]);
		$this->assertStringEndsWith('/broadcasting/auth', $live['auth_url']);
		$this->assertStringContainsString('pusher.min.js', $live['pusher_src']);
		$this->assertStringNotContainsString('test-secret', json_encode($live));
	}

	public function test_the_phone_app_bar_has_a_chats_tab_with_the_unread_badge(): void
	{
		// Arrange
		$this->conversation(['Is this still available?']);

		// Act
		$html = $this->actingAs($this->seller)->get('/')->assertOk()->getContent();
		$bar = substr($html, strpos($html, 'data-adn-appbar'));
		$bar = substr($bar, 0, strpos($bar, '</nav>'));

		// Assert
		$this->assertMatchesRegularExpression('/href="[^"]*\/chat"[^>]*class="adn-appbar-link/', $bar);
		$this->assertStringContainsString('<span class="adn-appbar-badge" data-adn-chat-badge>1</span>', $bar);
		$this->assertStringContainsString('>Chats</span>', $bar);
		$this->assertStringNotContainsString('saved-posts', $bar);
	}

	public function test_the_old_messenger_links_go_to_the_chat(): void
	{
		$this->actingAs($this->buyer)->get('/account/messages')->assertRedirect('/chat');
		$this->actingAs($this->buyer)->get('/account/messages/12')->assertRedirect('/chat/12');
		$this->actingAs($this->buyer)->postJson('/account/messages/check-new')->assertNotFound();
		$this->assertFalse(\Illuminate\Support\Facades\Route::has('api.threads.index'), 'The unused app API for threads is gone.');
		$this->assertFalse(\Illuminate\Support\Facades\Route::has('account.messages.checkNew'), 'The 60-second poll is gone.');
	}

	private function chatState(string $html): array
	{
		return $this->jsonFrom($html, 'adnChatState');
	}

	private function jsonFrom(string $html, string $id): array
	{
		$this->assertSame(1, preg_match('/<script type="application\/json" id="' . $id . '">(.*?)<\/script>/s', $html, $match), "No #{$id} data in the page.");

		return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
	}
}
