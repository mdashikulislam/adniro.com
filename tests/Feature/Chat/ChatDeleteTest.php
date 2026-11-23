<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\ChatEvent;
use App\Services\Chat\ChatUnread;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/*
 * Deleting in the chat (adniro): taking back a message I sent, and deleting a conversation from my own list.
 */
class ChatDeleteTest extends ChatTestCase
{
	public function test_a_sender_takes_a_message_back_and_both_people_see_only_its_place(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?', 'Yes, call me on 07700 900123']);
		$messageId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->max('id');
		Event::fake([ChatEvent::class]);

		// Act: the seller takes the phone number back
		$response = $this->actingAs($this->seller)->deleteJson('/chat/api/threads/' . $thread->id . '/messages/' . $messageId);

		// Assert
		$response->assertOk()->assertJsonPath('message.unsent', true)->assertJsonPath('message.body', '');
		foreach ([$this->buyer, $this->seller] as $member) {
			$this->actingAs($member)->getJson('/chat/api/threads/' . $thread->id)
				->assertJsonPath('messages.1.unsent', true)
				->assertJsonPath('messages.1.body', '')
				->assertJsonPath('messages.1.caution', false)
				->assertJsonPath('thread.last.type', 'unsent')
				->assertJsonPath('thread.last.text', '');
			Event::assertDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::UNSENT
				&& $event->userId === (int)$member->id && $event->payload['message']['id'] === $messageId && $event->payload['message']['body'] === '');
		}
		$this->assertSame('Yes, call me on 07700 900123', DB::table('threads_messages')->where('id', $messageId)->value('body'), 'The text is kept for reports.');
		// A browser without a live connection learns about it from its next check
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id . '/messages?after=' . $messageId)
			->assertJsonCount(0, 'messages')->assertJsonPath('unsent', [$messageId]);
	}

	public function test_only_the_sender_can_take_a_message_back_and_only_for_a_while(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?']);
		$messageId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->max('id');
		$url = '/chat/api/threads/' . $thread->id . '/messages/' . $messageId;

		// Act & Assert: not the other person, not an outsider
		$this->actingAs($this->seller)->deleteJson($url)->assertNotFound();
		$this->actingAs($this->outsider)->deleteJson($url)->assertNotFound();

		// Act & Assert: not after the hour
		$this->travel(61)->minutes();
		$this->actingAs($this->buyer)->deleteJson($url)->assertStatus(422);
		$this->assertNull(DB::table('threads_messages')->where('id', $messageId)->value('unsent_at'));
	}

	public function test_a_photo_that_was_taken_back_no_longer_opens_and_cannot_be_quoted(): void
	{
		// Arrange
		Storage::fake('private');
		$thread = $this->conversation(['Can you send a photo?']);
		$sent = $this->actingAs($this->seller)->post('/chat/api/threads/' . $thread->id . '/messages', [
			'photo' => UploadedFile::fake()->image('sofa.jpg', 800, 600),
		], ['Accept' => 'application/json']);
		$messageId = (int)$sent->json('message.id');
		$this->actingAs($this->buyer)->get('/chat/files/' . $messageId)->assertOk();

		// Act
		$this->actingAs($this->seller)->deleteJson('/chat/api/threads/' . $thread->id . '/messages/' . $messageId)->assertOk();

		// Assert
		$this->actingAs($this->buyer)->get('/chat/files/' . $messageId)->assertNotFound();
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->assertJsonPath('messages.1.image', null);
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Nice', 'reply_to' => $messageId])
			->assertCreated()->assertJsonPath('message.reply_to', null);
	}

	public function test_deleting_a_chat_removes_it_from_my_list_only(): void
	{
		// Arrange: the seller has an unread message
		$thread = $this->conversation(['Is this still available?']);
		$this->assertSame(1, ChatUnread::total(self::SELLER_ID));

		// Act
		$response = $this->actingAs($this->seller)->deleteJson('/chat/api/threads/' . $thread->id);

		// Assert
		$response->assertOk()->assertJsonPath('deleted', true)->assertJsonPath('unread_total', 0);
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertJsonCount(0, 'threads');
		$this->actingAs($this->buyer)->getJson('/chat/api/threads')->assertJsonCount(1, 'threads');
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->assertJsonCount(1, 'messages');
		$this->assertSame(1, DB::table('threads_messages')->where('thread_id', $thread->id)->count());
		$this->actingAs($this->outsider)->deleteJson('/chat/api/threads/' . $thread->id)->assertNotFound();
	}

	public function test_a_deleted_chat_comes_back_with_the_next_message_but_without_the_old_ones(): void
	{
		// Arrange
		$thread = $this->conversation(['Does it turn into a sofa bed?', 'Yes, it does.']);
		$this->actingAs($this->seller)->deleteJson('/chat/api/threads/' . $thread->id)->assertOk();

		// Act: the buyer writes again
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Can I come today?'])->assertCreated();

		// Assert: the seller sees the chat again, with the new message only
		$this->actingAs($this->seller)->getJson('/chat/api/threads')
			->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.unread', 1)->assertJsonPath('threads.0.last.text', 'Can I come today?');
		$this->actingAs($this->seller)->getJson('/chat/api/threads/' . $thread->id)
			->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Can I come today?');
		$this->actingAs($this->seller)->getJson('/chat/api/threads/' . $thread->id . '/messages?after=0')
			->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Can I come today?');
		$this->actingAs($this->seller)->get('/chat/' . $thread->id)->assertOk()->assertDontSee('sofa bed')->assertSee('Can I come today?');
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->assertJsonCount(3, 'messages');
	}

	public function test_a_message_taken_back_before_it_was_read_no_longer_counts_as_unread(): void
	{
		// Arrange: the seller has two unread messages
		$thread = $this->conversation(['Is this still available?']);
		$second = $this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Wrong chat, sorry'])->json('message.id');
		$this->assertSame(2, ChatUnread::total(self::SELLER_ID));

		// Act
		$this->actingAs($this->buyer)->deleteJson('/chat/api/threads/' . $thread->id . '/messages/' . $second)->assertOk();

		// Assert
		$this->assertSame(1, $this->unreadOf($thread->id, self::SELLER_ID));
		$this->assertSame(1, ChatUnread::total(self::SELLER_ID));
	}

	public function test_what_i_deleted_with_a_chat_stays_out_of_reach_photos_and_quotes_too(): void
	{
		// Arrange: the seller sent a photo and a text, then the buyer deleted the chat
		Storage::fake('private');
		$thread = $this->conversation(['Can you send a photo?', 'It has a small scratch on the left arm']);
		$quotedId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->max('id');
		$photoId = (int)$this->actingAs($this->seller)->post('/chat/api/threads/' . $thread->id . '/messages', [
			'photo' => UploadedFile::fake()->image('sofa.jpg', 800, 600),
		], ['Accept' => 'application/json'])->json('message.id');
		$this->actingAs($this->buyer)->deleteJson('/chat/api/threads/' . $thread->id)->assertOk();

		// Act: the seller replies to their own old message
		$this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Still interested?', 'reply_to' => $quotedId])
			->assertCreated()->assertJsonPath('message.reply_to.id', $quotedId);

		// Assert: the buyer gets the new message without the old quote, and the old photo does not open
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)
			->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Still interested?')->assertJsonPath('messages.0.reply_to', null);
		$this->actingAs($this->buyer)->get('/chat/' . $thread->id)->assertOk()->assertDontSee('small scratch');
		$this->actingAs($this->buyer)->get('/chat/files/' . $photoId)->assertNotFound();
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id . '/messages?before=' . ($photoId + 1))->assertJsonCount(0, 'messages');
		// The seller keeps everything
		$this->actingAs($this->seller)->get('/chat/files/' . $photoId)->assertOk();
		$this->actingAs($this->seller)->getJson('/chat/api/threads/' . $thread->id)->assertJsonCount(4, 'messages');
		// And reading the new message leaves the buyer nothing unread
		$newId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->max('id');
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/read', ['message_id' => $newId])->assertJsonPath('unread_total', 0);
	}
}
