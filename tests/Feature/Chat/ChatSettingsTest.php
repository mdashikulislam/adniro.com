<?php

namespace Tests\Feature\Chat;

use App\Notifications\ChatUnreadDigest;
use App\Services\Chat\ChatSafety;
use App\Services\Chat\ChatSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
 * Admin > Settings > Chat (adniro): the chat on or off, photos, send limits, the take-back time and the
 * unread-messages email.
 */
class ChatSettingsTest extends ChatTestCase
{
	public function test_with_the_chat_turned_off_nothing_is_sent_but_conversations_can_still_be_read(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?']);
		config(['settings.chat.enabled' => '0']);

		// Act & Assert
		$this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello?'])
			->assertStatus(422)->assertJsonPath('errors.body.0', trans('chat.error_paused'));
		$this->actingAs($this->outsider)->postJson('/chat/listings/' . $this->listing->id, ['body' => 'Hello'])->assertStatus(422);
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)
			->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('thread.can_send', false);
		$this->actingAs($this->buyer)->get('/chat')->assertOk()->assertSee('"paused":true', false);
		$this->actingAs($this->outsider)->get(urlGen()->post($this->listing))->assertOk()->assertDontSee('Chat with seller');
		$this->assertSame(1, DB::table('threads_messages')->count());
	}

	public function test_with_photos_turned_off_only_text_is_accepted(): void
	{
		// Arrange
		Storage::fake('private');
		$thread = $this->conversation(['Can you send a photo?']);
		config(['settings.chat.photos_enabled' => '0']);

		// Act
		$photo = $this->actingAs($this->seller)->post('/chat/api/threads/' . $thread->id . '/messages', [
			'photo' => UploadedFile::fake()->image('sofa.jpg', 800, 600),
		], ['Accept' => 'application/json']);
		$text = $this->actingAs($this->seller)->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Sorry, no photo.']);

		// Assert
		$photo->assertStatus(422)->assertJsonPath('errors.photo.0', trans('chat.error_photos_off'));
		$text->assertCreated();
		$this->actingAs($this->seller)->get('/chat')->assertSee('"photos":false', false);
	}

	public function test_the_send_limit_follows_the_setting(): void
	{
		// Arrange: members past their first day, allowed two messages a minute
		$thread = $this->conversation(['Is this still available?']);
		$this->travel(2)->days();
		config(['settings.chat.messages_per_minute' => '2']);
		$url = '/chat/api/threads/' . $thread->id . '/messages';

		// Act
		$statuses = array_map(fn (string $body) => $this->actingAs($this->seller)->postJson($url, ['body' => $body])->status(), ['One', 'Two', 'Three']);

		// Assert
		$this->assertSame([201, 201, 429], $statuses);
	}

	public function test_taking_a_message_back_can_be_turned_off(): void
	{
		// Arrange
		$thread = $this->conversation(['Is this still available?']);
		$messageId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->max('id');
		config(['settings.chat.unsend_minutes' => '0']);

		// Act & Assert
		$this->actingAs($this->buyer)->deleteJson('/chat/api/threads/' . $thread->id . '/messages/' . $messageId)->assertStatus(422);
	}

	public function test_the_unread_email_follows_its_delay_and_its_switch(): void
	{
		// Arrange: a message has waited 11 minutes; the delay is set to 30
		Notification::fake();
		$thread = $this->conversation(['Is this still available?']);
		DB::table('threads')->where('id', $thread->id)->update(['last_message_at' => now()->subMinutes(11)]);
		config(['settings.chat.unread_email_delay_minutes' => '30']);

		// Act & Assert: too early
		$this->artisan('chat:unread-digest')->assertSuccessful();
		Notification::assertNothingSent();

		// Act & Assert: late enough, but the email is turned off for the whole site
		$this->travel(20)->minutes();
		config(['settings.chat.unread_email_enabled' => '0']);
		$this->artisan('chat:unread-digest')->assertSuccessful();
		Notification::assertNothingSent();

		// Act & Assert: turned on again
		config(['settings.chat.unread_email_enabled' => '1']);
		$this->artisan('chat:unread-digest')->assertSuccessful();
		Notification::assertSentToTimes($this->seller, ChatUnreadDigest::class, 1);
	}

	public function test_a_wrong_value_in_the_admin_panel_cannot_stop_or_flood_the_chat(): void
	{
		// Arrange & Act & Assert: not a number, far too high, far too low
		config(['settings.chat.messages_per_minute' => 'many']);
		$this->assertSame(20, ChatSettings::messagesPerMinute());
		config(['settings.chat.messages_per_minute' => '999999']);
		$this->assertSame(120, ChatSettings::messagesPerMinute());
		config(['settings.chat.messages_per_day' => '0', 'settings.chat.unread_email_delay_minutes' => '-5']);
		$this->assertSame(10, ChatSettings::messagesPerDay());
		$this->assertSame(1, ChatSettings::unreadEmailDelayMinutes());
	}

	public function test_the_browser_gets_the_servers_own_patterns_to_ask_before_a_risky_message(): void
	{
		// Act
		$patterns = ChatSafety::browserPatterns();
		$page = $this->actingAs($this->buyer)->get('/chat');

		// Assert: contact details and "pay outside" wording, each one matching what the server flags
		$this->assertSame(['contact', 'contact', 'pay'], array_column($patterns, 'kind'));
		foreach ([['bob@example.com', 0], ['07700 900123', 1], ['pay by western union', 2]] as [$text, $index]) {
			$pattern = '~' . $patterns[$index]['source'] . '~' . $patterns[$index]['flags'];
			$this->assertSame(1, preg_match($pattern, $text));
			$this->assertTrue(ChatSafety::needsCaution($text));
		}
		$page->assertOk()->assertSee('"safety":[{"kind":"contact"', false)->assertSee(trans('chat.never_asks'));
	}
}
