<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\ChatUnread;
use Illuminate\Support\Facades\DB;

/*
 * What is left of a conversation when a listing or a member is deleted (adniro).
 */
class ChatLifecycleTest extends ChatTestCase
{
	public function test_deleting_a_listing_keeps_the_conversation_and_its_messages(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);

		// Act
		$this->listing->delete();

		// Assert
		$this->assertSame(1, DB::table('threads')->where('id', $thread->id)->whereNull('deleted_at')->count());
		$this->assertSame(2, DB::table('threads_messages')->where('thread_id', $thread->id)->count());
		$this->assertSame(0, DB::table('threads_messages')->whereNotNull('post_id')->count());
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)
			->assertOk()
			->assertJsonCount(2, 'messages')
			->assertJsonPath('thread.post.title', 'Grey corner sofa')
			->assertJsonPath('thread.post.available', false)
			->assertJsonPath('thread.post.url', null)
			->assertJsonPath('thread.can_send', true);
	}

	public function test_deleting_one_listing_keeps_what_was_said_about_another(): void
	{
		// Arrange: the same two people talked about the sofa, then about a table
		$thread = $this->conversation(['Is the sofa available?']);
		$table = $this->stockListing('Oak dining table', ['user_id' => self::SELLER_ID]);
		$this->actingAs($this->buyer)->postJson('/chat/listings/' . $table->id, ['body' => 'And the table?'])->assertCreated();

		// Act: the seller deletes the sold table
		$table->delete();

		// Assert
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)
			->assertOk()
			->assertJsonCount(2, 'messages')
			->assertJsonPath('messages.0.post.title', 'Grey corner sofa')
			->assertJsonPath('messages.1.post', null)
			->assertJsonPath('thread.post.title', 'Oak dining table')
			->assertJsonPath('thread.post.available', false);
	}

	public function test_a_report_survives_the_listing_it_was_about(): void
	{
		// Arrange
		$thread = $this->conversation(['Pay me a deposit first']);
		$this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/report', ['reason' => 'scam'])->assertSuccessful();

		// Act
		$this->listing->delete();

		// Assert
		$this->assertSame(1, DB::table('thread_reports')->where('thread_id', $thread->id)->count());
	}

	public function test_deleting_a_member_leaves_the_other_person_nothing_unread(): void
	{
		// Arrange: the seller has two unread messages from the buyer
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.', 'Great.']);
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Can I come today?'])->assertCreated();
		$this->assertSame(2, ChatUnread::total(self::SELLER_ID));

		// Act
		$this->buyer->delete();

		// Assert: the seller keeps their own message, with nothing left to read
		$this->assertSame(0, $this->unreadOf($thread->id, self::SELLER_ID));
		$this->assertSame(0, ChatUnread::total(self::SELLER_ID));
		$kept = DB::table('threads_messages')->where('thread_id', $thread->id)->get();
		$this->assertSame(['Yes, it is.'], $kept->pluck('body')->all());
		$this->assertSame((int)$kept->first()->id, (int)DB::table('threads')->where('id', $thread->id)->value('last_message_id'));
		$this->actingAs($this->seller)->getJson('/chat/api/threads')
			->assertOk()
			->assertJsonCount(1, 'threads')
			->assertJsonPath('threads.0.unread', 0)
			->assertJsonPath('threads.0.last.text', 'Yes, it is.')
			->assertJsonPath('threads.0.can_send', false);
	}

	public function test_a_conversation_with_only_the_deleted_members_messages_leaves_the_inbox(): void
	{
		// Arrange: only the buyer wrote
		$thread = $this->conversation(['Is this still available?']);

		// Act
		$this->buyer->delete();

		// Assert
		$this->assertNull(DB::table('threads')->where('id', $thread->id)->value('last_message_id'));
		$this->assertSame(0, ChatUnread::total(self::SELLER_ID));
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertOk()->assertJsonCount(0, 'threads');
	}

	public function test_a_conversation_hidden_by_a_member_leaves_their_list_and_badge_until_a_new_message(): void
	{
		// Arrange: the seller has an unread message, and has removed the conversation from their list
		$thread = $this->conversation(['Is this still available?']);
		DB::table('threads_participants')->where('thread_id', $thread->id)->where('user_id', self::SELLER_ID)->update(['deleted_at' => now()]);
		ChatUnread::forget(self::SELLER_ID);

		// Act & Assert: hidden for the seller only
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertOk()->assertJsonCount(0, 'threads');
		$this->assertSame(0, ChatUnread::total(self::SELLER_ID));
		$this->actingAs($this->buyer)->getJson('/chat/api/threads')->assertOk()->assertJsonCount(1, 'threads');

		// Act & Assert: the buyer writes again, and it is back
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello?'])->assertCreated();
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertOk()->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.unread', 2);
		$this->assertSame(2, ChatUnread::total(self::SELLER_ID));
	}
}
