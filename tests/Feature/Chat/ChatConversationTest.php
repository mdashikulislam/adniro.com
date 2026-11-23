<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\ChatEvent;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatUnread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/*
 * Starting a conversation, sending, the inbox, history and read receipts (adniro).
 */
class ChatConversationTest extends ChatTestCase
{
	public function test_the_first_message_about_a_listing_starts_one_conversation_with_the_seller(): void
	{
		// Act
		$response = $this->actingAs($this->buyer)->postJson('/chat/listings/' . $this->listing->id, ['body' => 'Is this still available?']);

		// Assert
		$response->assertCreated()
			->assertJsonPath('message.body', 'Is this still available?')
			->assertJsonPath('thread.other.id', self::SELLER_ID)
			->assertJsonPath('thread.post.title', 'Grey corner sofa')
			->assertJsonPath('message.post.title', 'Grey corner sofa');
		$threadId = $response->json('thread.id');
		$this->assertSame([self::SELLER_ID, self::BUYER_ID], DB::table('threads_participants')->where('thread_id', $threadId)->orderBy('user_id')->pluck('user_id')->all());
		$this->assertSame(1, $this->unreadOf($threadId, self::SELLER_ID));
		$this->assertSame(0, $this->unreadOf($threadId, self::BUYER_ID));
	}

	public function test_a_second_first_message_reuses_the_same_conversation(): void
	{
		// Arrange
		$url = '/chat/listings/' . $this->listing->id;

		// Act
		$first = $this->actingAs($this->buyer)->postJson($url, ['body' => 'Is this still available?'])->json('thread.id');
		$second = $this->actingAs($this->buyer)->postJson($url, ['body' => 'Where can we meet?'])->json('thread.id');

		// Assert
		$this->assertSame($first, $second);
		$this->assertSame(1, DB::table('threads')->where('post_id', $this->listing->id)->count());
		$this->assertSame(2, DB::table('threads_messages')->where('thread_id', $first)->count());
	}

	public function test_two_people_keep_one_conversation_whatever_the_listing(): void
	{
		// Arrange: the seller has a second listing
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);
		$table = $this->stockListing('Oak dining table', ['user_id' => self::SELLER_ID]);

		// Act: "Chat with seller" on the second listing, then a normal reply
		$fromListing = $this->actingAs($this->buyer)->postJson('/chat/listings/' . $table->id, ['body' => 'And is the table still for sale?']);
		$reply = $this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'It is.']);

		// Assert: the same conversation, now about the table; only the message sent from the listing carries it
		$fromListing->assertCreated()
			->assertJsonPath('thread.id', $thread->id)
			->assertJsonPath('thread.post.title', 'Oak dining table')
			->assertJsonPath('message.post.title', 'Oak dining table');
		$reply->assertCreated()->assertJsonPath('message.post', null)->assertJsonPath('thread.post.title', 'Oak dining table');
		$this->assertSame(1, DB::table('threads')->count());
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertJsonCount(1, 'threads');

		$history = $this->actingAs($this->seller)->getJson('/chat/api/threads/' . $thread->id . '/messages')->json('messages');
		$this->assertSame(['Grey corner sofa', null, 'Oak dining table', null], array_map(fn ($message) => $message['post']['title'] ?? null, $history));
	}

	public function test_the_seller_writing_about_the_buyers_listing_uses_the_same_conversation(): void
	{
		// Arrange
		$thread = $this->conversation();
		$bike = $this->stockListing('Road bike', ['user_id' => self::BUYER_ID]);

		// Act
		$response = $this->actingAs($this->seller)->postJson('/chat/listings/' . $bike->id, ['body' => 'Is the bike still available?']);

		// Assert
		$response->assertCreated()->assertJsonPath('thread.id', $thread->id)->assertJsonPath('thread.post.title', 'Road bike');
		$this->assertSame(1, DB::table('threads')->count());
	}

	public function test_a_reply_carries_a_short_quote_of_the_message_it_answers(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);
		$questionId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->min('id');
		Event::fake([ChatEvent::class]);

		// Act
		$response = $this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/messages', [
			'body' => 'It can be collected today.', 'reply_to' => $questionId,
		]);

		// Assert: in the reply, in the history and in what is pushed live
		$quote = ['id' => $questionId, 'user_id' => self::BUYER_ID, 'type' => 'text', 'text' => 'Is this still available?'];
		$response->assertCreated()->assertJsonPath('message.reply_to', $quote);
		$history = $this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id . '/messages')->json('messages');
		$this->assertSame([null, null, $quote], array_column($history, 'reply_to'));
		Event::assertDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::MESSAGE && $event->payload['message']['reply_to'] === $quote);
	}

	public function test_a_reply_cannot_quote_a_message_of_another_conversation(): void
	{
		// Arrange: a private message between the seller and someone else
		$thread = $this->conversation();
		$this->actingAs($this->outsider)->postJson('/chat/listings/' . $this->listing->id, ['body' => 'My card number is 1234']);
		$privateId = (int)DB::table('threads_messages')->where('body', 'My card number is 1234')->value('id');

		// Act
		$response = $this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello', 'reply_to' => $privateId]);

		// Assert: sent as a normal message, quoting nothing
		$response->assertCreated()->assertJsonPath('message.reply_to', null);
		$this->assertNull(DB::table('threads_messages')->where('id', $response->json('message.id'))->value('reply_to_id'));
	}

	public function test_the_same_tap_sent_twice_is_saved_once(): void
	{
		// Arrange
		$thread = $this->conversation();
		$payload = ['body' => 'Could I see it tomorrow?', 'client_uuid' => (string)Str::uuid()];
		$url = '/chat/api/threads/' . $thread->id . '/messages';

		// Act
		$first = $this->actingAs($this->buyer)->postJson($url, $payload);
		$second = $this->actingAs($this->buyer)->postJson($url, $payload);

		// Assert
		$first->assertCreated();
		$second->assertOk();
		$this->assertSame($first->json('message.id'), $second->json('message.id'));
		$this->assertSame(1, DB::table('threads_messages')->where('client_uuid', $payload['client_uuid'])->count());
		$this->assertSame(2, $this->unreadOf($thread->id, self::SELLER_ID));
	}

	public function test_sending_does_not_mark_the_other_persons_messages_as_read(): void
	{
		// Arrange: the seller has not opened the buyer's message
		$thread = $this->conversation(['Is this still available?']);
		$firstId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->value('id');

		// Act: the seller writes without having read it (another tab, or the page had not caught up yet)
		$this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello'])->assertCreated();

		// Assert: still unread for the seller, and not "seen" for the buyer
		$this->assertSame(1, $this->unreadOf($thread->id, self::SELLER_ID));
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->assertJsonPath('thread.read_up_to', 0);
		$this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/read', ['message_id' => $firstId])->assertJsonPath('read_up_to', $firstId);
		$this->assertSame(0, $this->unreadOf($thread->id, self::SELLER_ID));
	}

	public function test_a_deleted_conversation_comes_back_when_the_buyer_writes_again(): void
	{
		// Arrange
		$thread = $this->conversation();
		DB::table('threads')->where('id', $thread->id)->update(['deleted_at' => now()]);

		// Act
		$response = $this->actingAs($this->buyer)->postJson('/chat/listings/' . $this->listing->id, ['body' => 'Hello again']);

		// Assert
		$response->assertCreated()->assertJsonPath('thread.id', $thread->id);
		$this->assertNull(DB::table('threads')->where('id', $thread->id)->value('deleted_at'));
		$this->assertSame(1, DB::table('threads')->count());
	}

	public function test_a_message_keeps_every_character_that_was_written(): void
	{
		// Arrange
		$thread = $this->conversation();
		$url = '/chat/api/threads/' . $thread->id . '/messages';

		// Act
		$price = $this->actingAs($this->buyer)->postJson($url, ['body' => '  Can you do <500 for both? I <3 this & that  ']);
		$markup = $this->actingAs($this->buyer)->postJson($url, ['body' => '<script>alert(1)</script><b>Hello</b>']);

		// Assert: nothing is cut. It is shown as text, never as HTML (the pages below prove the escaping)
		$price->assertCreated()->assertJsonPath('message.body', 'Can you do <500 for both? I <3 this & that');
		$markup->assertCreated()->assertJsonPath('message.body', '<script>alert(1)</script><b>Hello</b>');
		$this->actingAs($this->seller)->get($this->chatPage($thread))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
	}

	public function test_an_empty_or_too_long_message_is_refused(): void
	{
		// Arrange
		$thread = $this->conversation();
		$url = '/chat/api/threads/' . $thread->id . '/messages';

		// Act & Assert
		$this->actingAs($this->buyer)->postJson($url, ['body' => '   '])->assertStatus(422);
		$this->actingAs($this->buyer)->postJson($url, ['body' => str_repeat('a', 2001)])->assertStatus(422);
	}

	public function test_a_new_message_is_pushed_to_both_people(): void
	{
		// Arrange
		$thread = $this->conversation();
		Event::fake([ChatEvent::class]);

		// Act
		$this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Yes, it is.'])->assertCreated();

		// Assert
		foreach ([self::SELLER_ID, self::BUYER_ID] as $userId) {
			Event::assertDispatched(ChatEvent::class, function (ChatEvent $event) use ($userId) {
				return $event->name === ChatEvent::MESSAGE
					&& $event->userId === $userId
					&& $event->broadcastOn()[0]->name === 'private-chat.user.' . $userId
					&& $event->payload['message']['body'] === 'Yes, it is.';
			});
		}
	}

	public function test_the_inbox_lists_my_conversations_newest_first_with_unread_counts(): void
	{
		// Arrange
		$older = $this->conversation(['Is this still available?']);
		$otherListing = $this->stockListing('Oak dining table', ['user_id' => self::SELLER_ID]);
		$this->actingAs($this->outsider)->postJson('/chat/listings/' . $otherListing->id, ['body' => 'What is your best price?']);
		DB::table('threads')->where('id', $older->id)->update(['last_message_at' => now()->subHour()]);

		// Act
		$response = $this->actingAs($this->seller)->getJson('/chat/api/threads');

		// Assert
		$response->assertOk()->assertJsonCount(2, 'threads')
			->assertJsonPath('threads.0.post.title', 'Oak dining table')
			->assertJsonPath('threads.0.unread', 1)
			->assertJsonPath('threads.0.last.text', 'What is your best price?')
			->assertJsonPath('threads.0.last.mine', false)
			->assertJsonPath('threads.1.post.title', 'Grey corner sofa');
		$this->assertSame(2, ChatUnread::total(self::SELLER_ID));
		$this->actingAs($this->seller)->getJson('/chat/api/unread')->assertJsonPath('unread_total', 2);
	}

	public function test_the_unread_filter_only_lists_conversations_with_unread_messages(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);

		// Act
		$forSeller = $this->actingAs($this->seller)->getJson('/chat/api/threads?filter=unread');
		$forBuyer = $this->actingAs($this->buyer)->getJson('/chat/api/threads?filter=unread');

		// Assert: the seller has read (they replied), the buyer has one waiting
		$forSeller->assertJsonCount(0, 'threads');
		$forBuyer->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.id', $thread->id);
	}

	public function test_history_comes_in_pages_oldest_first(): void
	{
		// Arrange
		$thread = $this->conversation(array_map(fn ($number) => 'Message ' . $number, range(1, 35)));
		$url = '/chat/api/threads/' . $thread->id . '/messages';

		// Act
		$latest = $this->actingAs($this->buyer)->getJson($url);
		$older = $this->actingAs($this->buyer)->getJson($url . '?before=' . $latest->json('messages.0.id'));

		// Assert
		$latest->assertOk()->assertJsonCount(30, 'messages')->assertJsonPath('has_more', true)
			->assertJsonPath('messages.0.body', 'Message 6')->assertJsonPath('messages.29.body', 'Message 35');
		$older->assertOk()->assertJsonCount(5, 'messages')->assertJsonPath('has_more', false)
			->assertJsonPath('messages.0.body', 'Message 1');
	}

	public function test_missed_messages_are_returned_after_the_last_one_the_browser_has(): void
	{
		// Arrange
		$thread = $this->conversation(['One', 'Two', 'Three']);
		$firstId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->min('id');

		// Act
		$response = $this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id . '/messages?after=' . $firstId);

		// Assert
		$response->assertOk()->assertJsonCount(2, 'messages')->assertJsonPath('messages.0.body', 'Two')->assertJsonPath('has_more', false);
	}

	public function test_a_long_absence_is_caught_up_in_parts_until_nothing_is_missed(): void
	{
		// Arrange: more missed messages than one answer carries
		$thread = $this->conversation();
		$firstId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->min('id');
		$missed = ChatMessages::CATCH_UP_MAX + 5;
		DB::table('threads_messages')->insert(array_map(fn (int $number) => [
			'thread_id' => $thread->id, 'user_id' => self::SELLER_ID, 'type' => 'text', 'body' => 'Message ' . $number, 'created_at' => now(),
		], range(1, $missed)));
		$url = '/chat/api/threads/' . $thread->id . '/messages?after=';

		// Act
		$firstPart = $this->actingAs($this->buyer)->getJson($url . $firstId);
		$secondPart = $this->actingAs($this->buyer)->getJson($url . $firstPart->json('messages.' . (ChatMessages::CATCH_UP_MAX - 1) . '.id'));

		// Assert
		$firstPart->assertOk()->assertJsonCount(ChatMessages::CATCH_UP_MAX, 'messages')->assertJsonPath('has_more', true);
		$secondPart->assertOk()->assertJsonCount(5, 'messages')->assertJsonPath('has_more', false)
			->assertJsonPath('messages.4.body', 'Message ' . $missed);
	}

	public function test_reading_clears_my_unread_count_and_tells_the_other_person(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.', 'Great.']);
		$lastId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->max('id');
		Event::fake([ChatEvent::class]);

		// Act
		$response = $this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/read', ['message_id' => $lastId]);

		// Assert
		$response->assertOk()->assertJsonPath('read_up_to', $lastId)->assertJsonPath('unread_total', 0);
		$this->assertSame(0, $this->unreadOf($thread->id, self::SELLER_ID));
		Event::assertDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::READ
			&& $event->userId === self::BUYER_ID && $event->payload['message_id'] === $lastId && $event->payload['reader_id'] === self::SELLER_ID);
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->assertJsonPath('thread.read_up_to', $lastId);
	}

	public function test_read_only_moves_forward(): void
	{
		// Arrange
		$thread = $this->conversation(['One', 'Two', 'Three']);
		$ids = DB::table('threads_messages')->where('thread_id', $thread->id)->orderBy('id')->pluck('id')->all();
		$this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/read', ['message_id' => $ids[2]]);

		// Act
		$response = $this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/read', ['message_id' => $ids[0]]);

		// Assert
		$response->assertOk()->assertJsonPath('read_up_to', $ids[2]);
	}
}
