<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\ChatEvent;
use App\Models\Chat\UserBlock;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatPrivacy;
use App\Services\Chat\ChatTools;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/*
 * Privacy choices in the chat (adniro): showing that I am online, showing what I have read, and the list of
 * people I blocked. Both choices work both ways.
 */
class ChatPrivacyTest extends ChatTestCase
{
	public function test_both_choices_are_on_until_a_member_turns_them_off(): void
	{
		// Act
		$before = $this->actingAs($this->buyer)->getJson('/chat/api/privacy');
		$saved = $this->actingAs($this->buyer)->putJson('/chat/api/privacy', ['online' => false, 'seen' => false]);

		// Assert
		$before->assertOk()->assertExactJson(['privacy' => ['online' => true, 'seen' => true], 'blocked' => []]);
		$saved->assertOk()->assertExactJson(['privacy' => ['online' => false, 'seen' => false]]);
		$this->assertSame(['online' => false, 'seen' => false], ChatPrivacy::of(self::BUYER_ID));
		$this->assertSame(['online' => true, 'seen' => true], ChatPrivacy::of(self::SELLER_ID));
	}

	public function test_a_privacy_change_is_told_to_my_own_tabs_and_to_nobody_else(): void
	{
		// Arrange
		$this->conversation();
		Event::fake([ChatEvent::class]);

		// Act
		$this->actingAs($this->buyer)->putJson('/chat/api/privacy', ['online' => false, 'seen' => true])->assertOk();

		// Assert: so a second tab stops showing the member online at once
		Event::assertDispatchedTimes(ChatEvent::class, 1);
		Event::assertDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::PRIVACY
			&& $event->userId === self::BUYER_ID && $event->payload === ['privacy' => ['online' => false, 'seen' => true]]);
	}

	public function test_privacy_choices_and_chat_tools_do_not_overwrite_each_other(): void
	{
		// Arrange
		ChatTools::save(self::SELLER_ID, true, 'Back on Monday.', ['Pick-up after 6 pm.']);

		// Act
		ChatPrivacy::save(self::SELLER_ID, false, true);
		ChatTools::save(self::SELLER_ID, false, 'Back on Monday.', []);

		// Assert
		$this->assertSame(['online' => false, 'seen' => true], ChatPrivacy::of(self::SELLER_ID));
		$this->assertSame('Back on Monday.', ChatTools::of(self::SELLER_ID)['away_message']);
		$this->assertSame(1, DB::table('chat_preferences')->count());
	}

	public function test_privacy_needs_both_answers_as_yes_or_no(): void
	{
		$this->putJson('/chat/api/privacy', ['online' => true, 'seen' => true])->assertUnauthorized();
		$this->actingAs($this->buyer)->putJson('/chat/api/privacy', ['online' => false])->assertStatus(422);
		$this->actingAs($this->buyer)->putJson('/chat/api/privacy', ['online' => 'sometimes', 'seen' => true])->assertStatus(422);
		$this->assertSame(0, DB::table('chat_preferences')->count());
	}

	public function test_a_member_who_hides_online_is_not_let_into_the_presence_channel(): void
	{
		// Arrange
		$thread = $this->conversation();
		$presence = fn ($user) => Broadcast::driver()->getChannels()['chat.thread.{threadId}']($user, $thread->id);

		// Act
		ChatPrivacy::save(self::BUYER_ID, false, true);

		// Assert: the buyer is not shown and sees nobody; the seller's own choice is untouched
		$this->assertFalse($presence($this->buyer));
		$this->assertSame(['id' => self::SELLER_ID], $presence($this->seller));

		ChatPrivacy::save(self::BUYER_ID, true, true);
		$this->assertSame(['id' => self::BUYER_ID], $presence($this->buyer));
	}

	public function test_with_seen_off_neither_person_learns_what_the_other_has_read(): void
	{
		// Arrange: each has read the other's messages
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.']);
		ChatMessages::markRead($thread, self::BUYER_ID, (int)$thread->last_message_id);
		$api = '/chat/api/threads/' . $thread->id;
		$seenBy = fn ($viewer) => $this->actingAs($viewer)->getJson($api)->json('thread.read_up_to');
		$this->assertGreaterThan(0, $seenBy($this->buyer));
		$this->assertGreaterThan(0, $seenBy($this->seller));

		// Act: the seller turns "seen" off
		ChatPrivacy::save(self::SELLER_ID, true, false);

		// Assert: on every route that tells it
		$this->assertSame(0, $seenBy($this->buyer));
		$this->assertSame(0, $seenBy($this->seller));
		$this->actingAs($this->buyer)->getJson('/chat/api/threads')->assertJsonPath('threads.0.read_up_to', 0);
		$this->actingAs($this->buyer)->getJson($api . '/messages?after=1')->assertJsonPath('read_up_to', 0);
		$this->actingAs($this->seller)->getJson($api . '/messages?after=1')->assertJsonPath('read_up_to', 0);

		// Turned back on: what was read in the meantime shows
		ChatPrivacy::save(self::SELLER_ID, true, true);
		$this->assertSame((int)$thread->last_message_id, $seenBy($this->seller));
	}

	public function test_with_seen_off_reading_still_clears_my_unread_count_and_tells_only_my_own_tabs(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?']);
		ChatPrivacy::save(self::SELLER_ID, true, false);
		Event::fake([ChatEvent::class]);

		// Act: the seller opens the chat
		$response = $this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/read', ['message_id' => $thread->last_message_id]);

		// Assert
		$response->assertOk()->assertJsonPath('unread_total', 0);
		$this->assertSame(0, $this->unreadOf($thread->id, self::SELLER_ID));
		Event::assertDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::READ && $event->userId === self::SELLER_ID);
		Event::assertNotDispatched(ChatEvent::class, fn (ChatEvent $event) => $event->name === ChatEvent::READ && $event->userId === self::BUYER_ID);
		// A new message tells the buyer nothing either
		$sent = $this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello?']);
		$sent->assertCreated()->assertJsonPath('thread.read_up_to', 0);
	}

	public function test_the_people_i_blocked_are_listed_and_can_be_unblocked(): void
	{
		// Arrange: the buyer blocked two people; somebody blocked the buyer
		UserBlock::query()->create(['blocker_id' => self::BUYER_ID, 'blocked_id' => self::SELLER_ID]);
		UserBlock::query()->create(['blocker_id' => self::BUYER_ID, 'blocked_id' => self::OUTSIDER_ID]);
		$stranger = $this->stockMember(84);
		UserBlock::query()->create(['blocker_id' => $stranger->id, 'blocked_id' => self::BUYER_ID]);

		// Act
		$list = $this->actingAs($this->buyer)->getJson('/chat/api/privacy');

		// Assert: latest first, only who I blocked, and nothing but a name and a photo
		$list->assertOk()->assertJsonCount(2, 'blocked')
			->assertJsonPath('blocked.0', ['id' => self::OUTSIDER_ID, 'name' => 'Member ' . self::OUTSIDER_ID, 'photo' => null, 'verified' => true])
			->assertJsonPath('blocked.1.id', self::SELLER_ID);
		$this->assertStringNotContainsString('@example.test', $list->getContent());

		$this->actingAs($this->buyer)->deleteJson('/chat/api/users/' . self::OUTSIDER_ID . '/block')->assertOk();
		$this->actingAs($this->buyer)->getJson('/chat/api/privacy')->assertJsonCount(1, 'blocked')->assertJsonPath('blocked.0.id', self::SELLER_ID);
	}

	public function test_a_deleted_members_privacy_choices_go_with_the_account(): void
	{
		// Arrange
		ChatPrivacy::save(self::BUYER_ID, false, false);

		// Act
		ChatTools::forget(self::BUYER_ID);

		// Assert
		$this->assertSame(['online' => true, 'seen' => true], ChatPrivacy::of(self::BUYER_ID));
	}
}
