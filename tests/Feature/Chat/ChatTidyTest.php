<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\ChatEvent;
use App\Models\Chat\ChatParticipant;
use App\Notifications\ChatUnreadDigest;
use App\Services\Chat\ChatConversations;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatUnread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/*
 * Finding and tidying the inbox (adniro): search, archive, mute, mark as unread. All of it is the member's
 * own: the other person's list and "seen" never change.
 */
class ChatTidyTest extends ChatTestCase
{
	public function test_the_inbox_is_searched_by_the_other_persons_name_and_by_listing(): void
	{
		// Arrange: the seller talks to two people, about two listings
		$sofa = $this->conversation();
		$table = $this->stockListing('Oak dining table', ['user_id' => self::SELLER_ID]);
		$other = ChatConversations::findOrCreate($table, $this->outsider);
		ChatMessages::send($other, self::OUTSIDER_ID, 'Is the table solid oak?', null, null, $table);
		DB::table('users')->where('id', self::OUTSIDER_ID)->update(['name' => 'Rahim 100%_real']);
		$search = fn (string $words) => $this->actingAs($this->seller)->getJson('/chat/api/threads?q=' . urlencode($words));

		// Act & Assert: by listing, by name, in any letter case
		$search('corner SOFA')->assertOk()->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.id', $sofa->id);
		$search('rahim')->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.id', $other->id);
		$search('Member ' . self::BUYER_ID)->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.id', $sofa->id);
		$search('piano')->assertJsonCount(0, 'threads');

		// "%" and "_" are looked for as written
		$search('100%_real')->assertJsonCount(1, 'threads');
		$search('%%')->assertJsonCount(0, 'threads');
		$search('__')->assertJsonCount(0, 'threads');

		// Too short to search with: the whole inbox
		$search('o')->assertJsonCount(2, 'threads');
	}

	public function test_a_search_never_finds_other_peoples_conversations_or_my_own_name(): void
	{
		// Arrange
		$this->conversation();

		// Act & Assert: the outsider is in no conversation; the buyer's own name is not what is searched
		$this->actingAs($this->outsider)->getJson('/chat/api/threads?q=sofa')->assertOk()->assertJsonCount(0, 'threads');
		$this->actingAs($this->buyer)->getJson('/chat/api/threads?q=' . urlencode('Member ' . self::BUYER_ID))->assertJsonCount(0, 'threads');
	}

	public function test_an_archived_chat_leaves_the_list_for_me_only_and_is_still_found(): void
	{
		// Arrange
		$thread = $this->conversation();
		$tidy = '/chat/api/threads/' . $thread->id . '/tidy';

		// Act
		$response = $this->actingAs($this->seller)->putJson($tidy, ['archived' => true]);

		// Assert
		$response->assertOk()->assertJsonPath('thread.archived', true)->assertJsonPath('thread.id', $thread->id);
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertJsonCount(0, 'threads');
		$this->actingAs($this->seller)->getJson('/chat/api/threads?filter=archived')->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.archived', true);
		$this->actingAs($this->seller)->getJson('/chat/api/threads?q=sofa')->assertJsonCount(1, 'threads');
		// Its unread message still counts, and the unread filter still shows it
		$this->assertSame(1, ChatUnread::total(self::SELLER_ID));
		$this->actingAs($this->seller)->getJson('/chat/api/threads?filter=unread')->assertJsonCount(1, 'threads');
		// The other person's list is untouched
		$this->actingAs($this->buyer)->getJson('/chat/api/threads')->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.archived', false);
		$this->actingAs($this->buyer)->getJson('/chat/api/threads?filter=archived')->assertJsonCount(0, 'threads');

		// Taken out of the archive again
		$this->actingAs($this->seller)->putJson($tidy, ['archived' => false])->assertJsonPath('thread.archived', false);
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertJsonCount(1, 'threads');
	}

	public function test_a_new_message_brings_an_archived_chat_back_for_both(): void
	{
		// Arrange: both archived it
		$thread = $this->conversation();
		$tidy = '/chat/api/threads/' . $thread->id . '/tidy';
		$this->actingAs($this->seller)->putJson($tidy, ['archived' => true])->assertOk();
		$this->actingAs($this->buyer)->putJson($tidy, ['archived' => true])->assertOk();

		// Act
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello again'])
			->assertCreated()->assertJsonPath('thread.archived', false);

		// Assert
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertJsonCount(1, 'threads');
		$this->actingAs($this->buyer)->getJson('/chat/api/threads')->assertJsonCount(1, 'threads');
		$this->assertSame(0, DB::table('threads_participants')->where('thread_id', $thread->id)->whereNotNull('archived_at')->count());
	}

	public function test_a_muted_chat_says_so_to_me_only_and_gets_no_email(): void
	{
		// Arrange
		Notification::fake();
		Event::fake([ChatEvent::class]);
		$thread = $this->conversation();
		$tidy = '/chat/api/threads/' . $thread->id . '/tidy';

		// Act
		$this->actingAs($this->seller)->putJson($tidy, ['muted' => true])->assertOk()->assertJsonPath('thread.muted', true);
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello?'])->assertCreated();
		DB::table('threads')->where('id', $thread->id)->update(['last_message_at' => now()->subMinutes(30)]);
		$this->artisan('chat:unread-digest')->assertSuccessful();

		// Assert: the live message tells each person their own state, so the muted one's browser stays quiet
		$mutedFor = fn (int $userId) => fn (ChatEvent $event) => $event->name === ChatEvent::MESSAGE
			&& $event->userId === $userId && $event->payload['thread']['muted'] === ($userId === self::SELLER_ID);
		Event::assertDispatched(ChatEvent::class, $mutedFor(self::SELLER_ID));
		Event::assertDispatched(ChatEvent::class, $mutedFor(self::BUYER_ID));
		Notification::assertNothingSent();
		// It still counts as unread, and stays in the list
		$this->assertSame(2, ChatUnread::total(self::SELLER_ID));
		$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertJsonCount(1, 'threads')->assertJsonPath('threads.0.muted', true);

		// Unmuted: the email comes
		$this->actingAs($this->seller)->putJson($tidy, ['muted' => false])->assertJsonPath('thread.muted', false);
		$this->artisan('chat:unread-digest');
		Notification::assertSentToTimes($this->seller, ChatUnreadDigest::class, 1);
	}

	public function test_marking_a_chat_unread_is_private_and_ends_when_it_is_opened(): void
	{
		// Arrange: the seller has read and answered
		Notification::fake();
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);
		$tidy = '/chat/api/threads/' . $thread->id . '/tidy';
		$seenBefore = $this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->json('thread.read_up_to');
		$this->assertGreaterThan(0, $seenBefore);
		DB::table('threads')->where('id', $thread->id)->update(['last_message_at' => now()->subMinutes(30)]);
		DB::table('threads_participants')->where('thread_id', $thread->id)->where('user_id', self::BUYER_ID)->update(['unread_count' => 0]);

		// Act
		$response = $this->actingAs($this->seller)->putJson($tidy, ['unread' => true]);
		$this->artisan('chat:unread-digest');

		// Assert: one unread for the seller, on the badge too; "seen" is as it was for the buyer; no email
		$response->assertOk()->assertJsonPath('thread.unread', 1)->assertJsonPath('unread_total', 1);
		$this->assertSame(1, ChatUnread::total(self::SELLER_ID));
		$this->actingAs($this->seller)->getJson('/chat/api/threads?filter=unread')->assertJsonCount(1, 'threads');
		$this->assertSame($seenBefore, $this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)->json('thread.read_up_to'));
		Notification::assertNothingSent();

		// Marking it twice does not count twice
		$this->actingAs($this->seller)->putJson($tidy, ['unread' => true])->assertJsonPath('thread.unread', 1);

		// Opening the chat (or "Mark as read") ends it
		$this->actingAs($this->seller)->putJson($tidy, ['unread' => false])->assertJsonPath('thread.unread', 0)->assertJsonPath('unread_total', 0);
		$this->assertSame(0, $this->unreadOf($thread->id, self::SELLER_ID));
	}

	public function test_a_chat_i_deleted_cannot_be_marked_unread(): void
	{
		// Arrange: nothing of it is shown to the buyer any more
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);
		ChatMessages::markRead($thread, self::BUYER_ID, (int)$thread->last_message_id);
		ChatMessages::hideFor($thread->fresh('participants'), self::BUYER_ID);

		// Act
		$this->actingAs($this->buyer)->putJson('/chat/api/threads/' . $thread->id . '/tidy', ['unread' => true])->assertOk();

		// Assert
		$this->assertSame(0, $this->unreadOf($thread->id, self::BUYER_ID));
		$this->assertSame(0, ChatUnread::total(self::BUYER_ID));
	}

	public function test_only_a_participant_can_tidy_a_chat_and_only_with_yes_or_no(): void
	{
		// Arrange
		$thread = $this->conversation();
		$tidy = '/chat/api/threads/' . $thread->id . '/tidy';

		// Act & Assert
		$this->putJson($tidy, ['archived' => true])->assertUnauthorized();
		$this->actingAs($this->outsider)->putJson($tidy, ['archived' => true, 'muted' => true, 'unread' => true])->assertNotFound();
		$this->actingAs($this->seller)->putJson($tidy, ['archived' => 'maybe'])->assertStatus(422);
		$this->actingAs($this->seller)->putJson($tidy, ['muted' => ['yes']])->assertStatus(422);
		$this->actingAs($this->seller)->putJson($tidy, [])->assertOk()->assertJsonPath('thread.archived', false)->assertJsonPath('thread.muted', false);

		$untouched = ChatParticipant::query()->where('thread_id', $thread->id)->whereNull('archived_at')->whereNull('muted_until')->count();
		$this->assertSame(2, $untouched);
	}

	public function test_the_chat_page_opens_on_the_archived_filter(): void
	{
		// Arrange
		$thread = $this->conversation();
		$this->actingAs($this->seller)->putJson('/chat/api/threads/' . $thread->id . '/tidy', ['archived' => true])->assertOk();
		$state = function (string $url): array {
			preg_match('~<script type="application/json" id="adnChatState">(.*?)</script>~s', $this->actingAs($this->seller)->get($url)->assertOk()->getContent(), $found);

			return json_decode($found[1], true);
		};

		// Act
		$all = $state('/chat');
		$archived = $state('/chat?filter=archived');

		// Assert
		$this->assertSame([], $all['inbox']['threads']);
		$this->assertSame('archived', $archived['filter']);
		$this->assertSame($thread->id, $archived['inbox']['threads'][0]['id']);
		$this->assertStringContainsString('/chat/api/threads/__ID__/tidy', $archived['urls']['tidy']);
	}
}
