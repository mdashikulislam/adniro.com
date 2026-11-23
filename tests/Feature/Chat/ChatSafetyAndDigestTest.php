<?php

namespace Tests\Feature\Chat;

use App\Models\Chat\UserBlock;
use App\Notifications\ChatReported;
use App\Notifications\ChatUnreadDigest;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatSafety;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/*
 * Reporting a conversation, the scam hint, and the unread-messages email (adniro).
 */
class ChatSafetyAndDigestTest extends ChatTestCase
{
	public function test_a_report_is_saved_and_emailed_to_the_admins(): void
	{
		// Arrange
		Notification::fake();
		config(['settings.app.email' => 'admin@example.test']);
		$thread = $this->conversation(['Is this still available?', 'Pay me by gift card first.']);

		// Act
		$response = $this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/report', [
			'reason' => 'scam', 'note' => 'Asked for <b>gift cards</b>.',
		]);

		// Assert
		$response->assertCreated();
		$this->assertDatabaseHas('thread_reports', [
			'thread_id' => $thread->id, 'reporter_id' => self::BUYER_ID, 'reason' => 'scam', 'note' => 'Asked for gift cards.', 'status' => 'open',
		]);
		Notification::assertSentTo(new AnonymousNotifiable(), ChatReported::class, function (ChatReported $mail, array $channels, $notifiable) {
			$lines = implode(' ', $mail->toMail($notifiable)->introLines);

			return $notifiable->routes['mail'] === 'admin@example.test' && str_contains($lines, 'gift card first');
		});
	}

	public function test_reporting_the_same_conversation_again_updates_the_open_report(): void
	{
		// Arrange
		Notification::fake();
		$thread = $this->conversation();
		$url = '/chat/api/threads/' . $thread->id . '/report';

		// Act
		$this->actingAs($this->buyer)->postJson($url, ['reason' => 'spam'])->assertCreated();
		$this->actingAs($this->buyer)->postJson($url, ['reason' => 'abuse'])->assertCreated();
		$this->actingAs($this->buyer)->postJson($url, ['reason' => 'because'])->assertStatus(422);

		// Assert
		$this->assertSame(['abuse'], DB::table('thread_reports')->where('thread_id', $thread->id)->pluck('reason')->all());
	}

	public function test_risky_messages_carry_a_caution_for_the_reader(): void
	{
		$this->assertTrue(ChatSafety::needsCaution('Send the deposit by Western Union'));
		$this->assertTrue(ChatSafety::needsCaution('Write me at sam@example.test'));
		$this->assertTrue(ChatSafety::needsCaution('Call +1 (555) 010-9999'));
		$this->assertTrue(ChatSafety::needsCaution('See www.cheap-sofas.example/pay'));
		$this->assertFalse(ChatSafety::needsCaution('Is 150 your best price? I can come at 6.'));
		$this->assertFalse(ChatSafety::needsCaution(null));
	}

	public function test_messages_left_unread_for_ten_minutes_get_one_email(): void
	{
		// Arrange
		Notification::fake();
		$thread = $this->conversation(['Is this still available?', 'Yes, it is.', 'And the price?']);
		DB::table('threads')->where('id', $thread->id)->update(['last_message_at' => now()->subMinutes(11)]);

		// Act
		$this->artisan('chat:unread-digest')->assertSuccessful();
		$this->artisan('chat:unread-digest')->assertSuccessful();

		// Assert: one email for the seller (one message waiting); none for the buyer, who wrote last
		Notification::assertSentToTimes($this->seller, ChatUnreadDigest::class, 1);
		Notification::assertSentTo($this->seller, ChatUnreadDigest::class, function (ChatUnreadDigest $mail) {
			$message = $mail->toMail($this->seller);

			return str_contains($message->subject, '1 unread message')
				&& str_contains(implode(' ', $message->introLines), 'Member ' . self::BUYER_ID)
				&& !str_contains(implode(' ', $message->introLines), 'And the price?');
		});
		Notification::assertNotSentTo($this->buyer, ChatUnreadDigest::class);
	}

	public function test_no_email_while_messages_are_fresh_read_muted_or_from_a_blocked_person(): void
	{
		// Arrange
		Notification::fake();
		$thread = $this->conversation(['Is this still available?']);

		// Act & Assert: sent a moment ago
		$this->artisan('chat:unread-digest');
		Notification::assertNothingSent();

		// Read in the meantime
		DB::table('threads')->where('id', $thread->id)->update(['last_message_at' => now()->subMinutes(30)]);
		ChatMessages::markRead($thread->fresh('participants'), self::SELLER_ID, (int)$thread->fresh()->last_message_id);
		$this->artisan('chat:unread-digest');
		Notification::assertNothingSent();

		// Muted
		ChatMessages::send($thread->fresh('participants'), self::BUYER_ID, 'Hello?');
		DB::table('threads')->where('id', $thread->id)->update(['last_message_at' => now()->subMinutes(30)]);
		DB::table('threads_participants')->where('thread_id', $thread->id)->where('user_id', self::SELLER_ID)->update(['muted_until' => now()->addDay()]);
		$this->artisan('chat:unread-digest');
		Notification::assertNothingSent();

		// Blocked
		DB::table('threads_participants')->where('thread_id', $thread->id)->update(['muted_until' => null]);
		UserBlock::query()->create(['blocker_id' => self::SELLER_ID, 'blocked_id' => self::BUYER_ID]);
		$this->artisan('chat:unread-digest');
		Notification::assertNothingSent();
	}

	public function test_a_conversation_is_mentioned_again_only_after_a_newer_message_and_an_hour(): void
	{
		// Arrange
		Notification::fake();
		$thread = $this->conversation(['Is this still available?']);
		DB::table('threads')->where('id', $thread->id)->update(['last_message_at' => now()->subMinutes(11)]);
		$this->artisan('chat:unread-digest');

		// Act: a newer message, but the last email was minutes ago
		$this->travel(20)->minutes();
		ChatMessages::send($thread->fresh('participants'), self::BUYER_ID, 'Hello?');
		$this->travel(15)->minutes();
		$this->artisan('chat:unread-digest');
		Notification::assertSentToTimes($this->seller, ChatUnreadDigest::class, 1);

		// Act: an hour after the first email
		$this->travel(30)->minutes();
		$this->artisan('chat:unread-digest');

		// Assert
		Notification::assertSentToTimes($this->seller, ChatUnreadDigest::class, 2);
	}
}
