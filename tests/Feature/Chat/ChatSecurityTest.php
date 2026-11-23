<?php

namespace Tests\Feature\Chat;

use App\Models\Chat\UserBlock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
 * Who may see and write in a conversation (adniro): its two participants only, nobody across a block,
 * and photos only through the chat's own route.
 */
class ChatSecurityTest extends ChatTestCase
{
	public function test_guests_are_sent_to_sign_in(): void
	{
		// Arrange
		$thread = $this->conversation();
		auth()->logout();

		// Act & Assert
		$this->get('/chat')->assertRedirect();
		$this->get('/chat/' . $thread->id)->assertRedirect();
		$this->postJson('/chat/api/threads/' . $thread->id . '/messages', ['body' => 'Hello'])->assertUnauthorized();
		$this->assertSame(1, DB::table('threads_messages')->where('thread_id', $thread->id)->count());
	}

	public function test_a_member_outside_a_conversation_cannot_see_or_write_in_it(): void
	{
		// Arrange
		$thread = $this->conversation();
		$messageId = (int)DB::table('threads_messages')->where('thread_id', $thread->id)->value('id');
		$api = '/chat/api/threads/' . $thread->id;

		// Act & Assert: as if it did not exist
		$this->actingAs($this->outsider);
		$this->get('/chat/' . $thread->id)->assertNotFound();
		$this->getJson($api)->assertNotFound();
		$this->getJson($api . '/messages')->assertNotFound();
		$this->postJson($api . '/messages', ['body' => 'Send the money first.'])->assertNotFound();
		$this->postJson($api . '/read', ['message_id' => $messageId])->assertNotFound();
		$this->postJson($api . '/report', ['reason' => 'spam'])->assertNotFound();
		$this->getJson('/chat/api/threads')->assertJsonCount(0, 'threads');
		$this->assertSame(1, DB::table('threads_messages')->where('thread_id', $thread->id)->count());
		$this->assertSame(2, DB::table('threads_participants')->where('thread_id', $thread->id)->count());
	}

	public function test_the_live_channels_are_open_to_the_right_people_only(): void
	{
		// Arrange
		$thread = $this->conversation();
		$own = fn ($user, int $id) => (bool)$this->channelRule('chat.user.{userId}')($user, $id);
		$presence = fn ($user) => $this->channelRule('chat.thread.{threadId}')($user, $thread->id);

		// Act & Assert
		$this->assertTrue($own($this->buyer, self::BUYER_ID));
		$this->assertFalse($own($this->outsider, self::BUYER_ID));
		$this->assertSame(['id' => self::BUYER_ID], $presence($this->buyer));
		$this->assertSame(['id' => self::SELLER_ID], $presence($this->seller));
		$this->assertFalse($presence($this->outsider));

		UserBlock::query()->create(['blocker_id' => self::SELLER_ID, 'blocked_id' => self::BUYER_ID]);
		$this->assertFalse($presence($this->buyer), 'A blocked person no longer sees the other online.');
	}

	public function test_the_live_channel_sign_in_route_needs_a_fully_signed_in_member(): void
	{
		// Act
		$response = $this->postJson('/broadcasting/auth', ['channel_name' => 'private-chat.user.' . self::BUYER_ID, 'socket_id' => '1.1']);
		$middleware = \Illuminate\Support\Facades\Route::getRoutes()->match(\Illuminate\Http\Request::create('/broadcasting/auth', 'POST'))->gatherMiddleware();

		// Assert: the same gates as the chat pages, two-factor included
		$response->assertUnauthorized();
		foreach (['web', 'auth', 'twoFactor', 'banned.user'] as $gate) {
			$this->assertContains($gate, $middleware);
		}
	}

	public function test_only_a_real_member_can_be_blocked(): void
	{
		$this->actingAs($this->buyer)->postJson('/chat/api/users/999999/block')->assertNotFound();
		$this->assertSame(0, DB::table('user_blocks')->count());
	}

	public function test_nobody_can_start_a_conversation_about_their_own_listing(): void
	{
		// Act
		$response = $this->actingAs($this->seller)->postJson('/chat/listings/' . $this->listing->id, ['body' => 'Hello me']);

		// Assert
		$response->assertForbidden();
		$this->assertSame(0, DB::table('threads')->count());
		$this->actingAs($this->seller)->get('/chat/new/' . $this->listing->id)->assertRedirect();
	}

	public function test_a_block_stops_messages_both_ways_and_unblocking_restores_them(): void
	{
		// Arrange
		$thread = $this->conversation();
		$url = '/chat/api/threads/' . $thread->id . '/messages';

		// Act
		$this->actingAs($this->seller)->postJson('/chat/api/users/' . self::BUYER_ID . '/block')->assertOk();

		// Assert
		$this->actingAs($this->buyer)->postJson($url, ['body' => 'Hello?'])->assertForbidden();
		$this->actingAs($this->seller)->postJson($url, ['body' => 'Hello?'])->assertForbidden();
		$this->actingAs($this->buyer)->postJson('/chat/listings/' . $this->stockListing('Lamp', ['user_id' => self::SELLER_ID])->id, ['body' => 'Hello?'])->assertForbidden();
		$this->actingAs($this->seller)->getJson('/chat/api/threads/' . $thread->id)
			->assertJsonPath('thread.blocked_by_me', true)->assertJsonPath('thread.can_send', false);
		$this->actingAs($this->buyer)->getJson('/chat/api/threads/' . $thread->id)
			->assertJsonPath('thread.blocked_by_me', false)->assertJsonPath('thread.can_send', false);

		$this->actingAs($this->seller)->deleteJson('/chat/api/users/' . self::BUYER_ID . '/block')->assertOk();
		$this->actingAs($this->buyer)->postJson($url, ['body' => 'Hello again'])->assertCreated();
	}

	public function test_a_member_cannot_block_themselves(): void
	{
		$this->actingAs($this->buyer)->postJson('/chat/api/users/' . self::BUYER_ID . '/block')->assertStatus(422);
	}

	public function test_a_photo_is_kept_private_and_opens_for_participants_only(): void
	{
		// Arrange
		Storage::fake('private');
		$thread = $this->conversation();

		// Act
		$response = $this->actingAs($this->buyer)->post('/chat/api/threads/' . $thread->id . '/messages', [
			'photo' => UploadedFile::fake()->image('sofa.png', 2400, 1200),
		], ['Accept' => 'application/json']);

		// Assert: saved as a JPEG no larger than 1600px, with a thumbnail, on the private disk
		$response->assertCreated()->assertJsonPath('message.type', 'image');
		$path = DB::table('threads_messages')->where('id', $response->json('message.id'))->value('file_path');
		$this->assertStringStartsWith('chat/' . $thread->id . '/', $path);
		Storage::disk('private')->assertExists($path);
		Storage::disk('private')->assertExists(str_replace('.jpg', '_t.jpg', $path));
		[$width, $height] = getimagesizefromstring(Storage::disk('private')->get($path));
		$this->assertSame([1600, 800], [$width, $height]);

		$fileUrl = $response->json('message.image.url');
		$this->actingAs($this->seller)->get($fileUrl)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
		$this->actingAs($this->seller)->get($response->json('message.image.thumb'))->assertOk();
		$this->actingAs($this->outsider)->get($fileUrl)->assertNotFound();

		// Never through the site's public file route, on any disk, however the path is spelled
		foreach ([$path, './' . $path, '/' . $path, 'x/../' . $path, str_replace('chat/', 'chat//', $path)] as $spelling) {
			$this->actingAs($this->buyer)->get('/common/file?disk=private&path=' . urlencode($spelling))->assertForbidden();
		}
	}

	public function test_a_photo_far_larger_than_the_browser_sends_is_refused_before_it_is_decoded(): void
	{
		// Arrange
		Storage::fake('private');
		$thread = $this->conversation();

		// Act
		$response = $this->actingAs($this->buyer)->post('/chat/api/threads/' . $thread->id . '/messages', [
			'photo' => UploadedFile::fake()->image('huge.png', 5000, 100),
		], ['Accept' => 'application/json']);

		// Assert
		$response->assertStatus(422);
		$this->assertSame([], Storage::disk('private')->allFiles());
	}

	public function test_only_real_photos_are_accepted(): void
	{
		// Arrange
		Storage::fake('private');
		$thread = $this->conversation();

		// Act
		$response = $this->actingAs($this->buyer)->post('/chat/api/threads/' . $thread->id . '/messages', [
			'photo' => UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf'),
		], ['Accept' => 'application/json']);

		// Assert
		$response->assertStatus(422);
		$this->assertSame([], Storage::disk('private')->allFiles());
	}

	public function test_an_old_messenger_attachment_still_opens_for_its_participants_only(): void
	{
		// Arrange
		$thread = $this->conversation();
		$attachment = 'files/us/' . $this->listing->id . '/applications/resume.pdf';
		Storage::fake(config('filesystems.default'));
		Storage::disk(config('filesystems.default'))->put($attachment, '%PDF-1.4 test');
		DB::table('threads_messages')->where('thread_id', $thread->id)->update(['file_path' => $attachment]);
		$url = '/common/file?path=' . $attachment;
		auth()->logout();

		// Act & Assert
		$this->get($url)->assertForbidden();
		$this->actingAs($this->outsider)->get($url)->assertForbidden();
		$this->actingAs($this->buyer)->get($url)->assertOk();
	}

	public function test_sending_is_rate_limited_more_tightly_on_an_accounts_first_day(): void
	{
		// Arrange
		$thread = $this->conversation();

		// Act
		$newAccount = $this->statusesOfSending($thread->id, 10);
		$this->travel(2)->minutes();
		DB::table('users')->where('id', self::BUYER_ID)->update(['created_at' => now()->subDays(3)]);
		$this->buyer->refresh();
		$member = $this->statusesOfSending($thread->id, 22);

		// Assert: 8 a minute on the first day, then 20
		$this->assertSame(8, count(array_keys($newAccount, 201)));
		$this->assertSame(429, end($newAccount));
		$this->assertSame(20, count(array_keys($member, 201)));
		$this->assertSame(429, end($member));
	}

	/** @return array<int, int> */
	private function statusesOfSending(int $threadId, int $times): array
	{
		$statuses = [];
		for ($i = 0; $i < $times; $i++) {
			$statuses[] = $this->actingAs($this->buyer)->postJson('/chat/api/threads/' . $threadId . '/messages', ['body' => 'Message ' . $i])->status();
		}

		return $statuses;
	}

	private function channelRule(string $channel): callable
	{
		return Broadcast::driver()->getChannels()[$channel];
	}
}
