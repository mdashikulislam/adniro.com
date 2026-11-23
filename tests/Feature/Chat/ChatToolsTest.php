<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatTools;
use App\Services\Chat\ChatUnread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/*
 * A member's own chat tools (adniro): saved replies, the away message, and "Mark as sold" from a chat.
 */
class ChatToolsTest extends ChatTestCase
{
	public function test_a_member_saves_replies_and_an_away_message_exactly_as_written(): void
	{
		// Act
		$response = $this->actingAs($this->seller)->putJson('/chat/api/tools', [
			'away_on'       => true,
			'away_message'  => '  Back on Monday. Offers <100 are not answered.  ',
			'saved_replies' => ['Pick-up after 6 pm.', '', '  Cash only & no delivery.  '],
		]);

		// Assert: trimmed, empty ones dropped, every character kept
		$expected = [
			'away_on'       => true,
			'away_message'  => 'Back on Monday. Offers <100 are not answered.',
			'saved_replies' => ['Pick-up after 6 pm.', 'Cash only & no delivery.'],
		];
		$response->assertOk()->assertExactJson(['tools' => $expected]);
		$this->assertSame($expected, ChatTools::of(self::SELLER_ID));
		$this->assertSame(['away_on' => false, 'away_message' => '', 'saved_replies' => []], ChatTools::of(self::BUYER_ID));
	}

	public function test_tools_that_cannot_be_used_are_refused(): void
	{
		// Arrange
		$valid = ['away_on' => false, 'away_message' => '', 'saved_replies' => []];
		$long = str_repeat('a', ChatTools::TEXT_MAX + 1);

		// Act & Assert
		$this->actingAs($this->seller)->putJson('/chat/api/tools', ['away_on' => true] + $valid)->assertStatus(422)->assertJsonValidationErrors('away_message');
		$this->actingAs($this->seller)->putJson('/chat/api/tools', ['away_message' => $long] + $valid)->assertStatus(422);
		$this->actingAs($this->seller)->putJson('/chat/api/tools', ['saved_replies' => [$long]] + $valid)->assertStatus(422);
		$this->actingAs($this->seller)->putJson('/chat/api/tools', ['saved_replies' => ['1', '2', '3', '4', '5', '6']] + $valid)->assertStatus(422);
		$this->actingAs($this->seller)->putJson('/chat/api/tools', ['saved_replies' => [['nested']]] + $valid)->assertStatus(422);
		$this->assertSame(0, DB::table('chat_preferences')->count());

		// The limits themselves are fine, and turning away off keeps the text for next time
		$this->actingAs($this->seller)->putJson('/chat/api/tools', ['away_message' => 'Back soon.', 'saved_replies' => ['1', '2', '3', '4', '5']] + $valid)
			->assertOk()->assertJsonPath('tools.away_on', false)->assertJsonPath('tools.away_message', 'Back soon.');
	}

	public function test_an_away_message_answers_once_and_not_again_for_twelve_hours(): void
	{
		// Arrange: the seller is away
		ChatTools::save(self::SELLER_ID, true, 'Back on Monday.', []);

		// Act: the buyer writes twice
		$thread = $this->conversation(['Is this still available?']);
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello?'])->assertCreated();

		// Assert: one automatic reply, from the seller, unread for the buyer
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)
			->assertJsonCount(3, 'messages')
			->assertJsonPath('messages.0.auto', false)
			->assertJsonPath('messages.1.auto', true)
			->assertJsonPath('messages.1.user_id', self::SELLER_ID)
			->assertJsonPath('messages.1.body', 'Back on Monday.')
			->assertJsonPath('messages.2.body', 'Hello?')
			->assertJsonPath('thread.unread', 1);
		// The seller has not read anything: the automatic reply is not their reading
		$this->assertSame(2, ChatUnread::total(self::SELLER_ID));

		// Act & Assert: the next day it answers again
		$this->travel(ChatTools::AWAY_AGAIN_AFTER_HOURS + 1)->hours();
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Still there?'])->assertCreated();
		$this->assertSame(2, DB::table('threads_messages')->where('is_auto', 1)->count());
	}

	public function test_two_members_who_are_both_away_do_not_answer_each_other_for_ever(): void
	{
		// Arrange
		ChatTools::save(self::SELLER_ID, true, 'Back on Monday.', []);
		ChatTools::save(self::BUYER_ID, true, 'On holiday.', []);

		// Act
		$thread = $this->conversation(['Is this still available?']);

		// Assert: the buyer's message, and the seller's away message. Nothing answers an automatic reply.
		$this->assertSame(2, DB::table('threads_messages')->where('thread_id', $thread->id)->count());
		$this->assertSame(1, DB::table('threads_messages')->where('is_auto', 1)->where('user_id', self::SELLER_ID)->count());
	}

	public function test_an_away_message_that_is_off_or_empty_sends_nothing(): void
	{
		// Arrange
		ChatTools::save(self::SELLER_ID, false, 'Back on Monday.', []);

		// Act
		$thread = $this->conversation(['Is this still available?']);

		// Assert
		$this->assertSame(1, DB::table('threads_messages')->where('thread_id', $thread->id)->count());
	}

	public function test_a_seller_marks_their_listing_as_sold_from_the_chat(): void
	{
		// Arrange
		Notification::fake();
		$thread = $this->conversation(['Is this still available?', 'Yes.']);
		$url = '/chat/api/listings/' . $this->listing->id . '/sold';

		// Act & Assert: only the owner
		$this->actingAs($this->buyer)->postJson($url)->assertNotFound();
		$this->assertNull(DB::table('posts')->where('id', $this->listing->id)->value('archived_at'));

		$this->actingAs($this->seller)->postJson($url)
			->assertOk()->assertJsonPath('post.available', false)->assertJsonPath('post.can_offer', false)->assertJsonPath('post.title', 'Grey corner sofa');
		$this->assertNotNull(DB::table('posts')->where('id', $this->listing->id)->value('archived_at'));
		// Both people now see it as gone, and can still talk
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)
			->assertJsonPath('thread.post.available', false)->assertJsonPath('thread.can_send', true);
		// Already offline: said so, not done twice
		$this->actingAs($this->seller)->postJson($url)->assertStatus(422);
	}

	public function test_the_chat_page_brings_the_members_tools_and_the_sellers_quick_replies(): void
	{
		// Arrange
		ChatTools::save(self::SELLER_ID, false, '', ['Pick-up after 6 pm.']);
		$thread = $this->conversation(['Is this still available?']);

		// Act
		$html = $this->actingAs($this->seller)->get($this->chatPage($thread))->assertOk()->getContent();

		// Assert
		preg_match('~<script type="application/json" id="adnChatState">(.*?)</script>~s', $html, $match);
		$state = json_decode($match[1], true);
		$this->assertSame(['Pick-up after 6 pm.'], $state['tools']['saved_replies']);
		$this->assertCount(4, $state['seller_quick_replies']);
		$this->assertTrue($state['limits']['offers']);
		$this->assertSame(self::SELLER_ID, $state['open']['thread']['post']['owner_id']);
	}

	public function test_a_deleted_members_tools_are_removed(): void
	{
		// Arrange
		ChatTools::save(self::BUYER_ID, true, 'On holiday.', ['Thanks!']);
		$this->conversation(['Is this still available?']);

		// Act
		$this->buyer->delete();

		// Assert
		$this->assertSame(0, DB::table('chat_preferences')->where('user_id', self::BUYER_ID)->count());
	}

	public function test_an_away_message_failure_never_fails_the_message_that_was_sent(): void
	{
		// Arrange: the away row cannot be claimed because the column is gone
		ChatTools::save(self::SELLER_ID, true, 'Back on Monday.', []);
		$thread = $this->conversation([]);
		DB::table('threads_participants')->where('thread_id', $thread->id)->where('user_id', self::SELLER_ID)->delete();

		// Act
		$message = ChatMessages::send($thread->fresh('participants'), self::BUYER_ID, 'Is this still available?');

		// Assert
		$this->assertTrue($message->exists);
		$this->assertSame(0, DB::table('threads_messages')->where('is_auto', 1)->count());
	}
}
