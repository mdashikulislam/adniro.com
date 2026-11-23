<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SeedsStockData;
use Tests\TestCase;

/*
 * The Messenger's two holes (adniro): replies into someone else's conversation, and attachments anyone could open.
 */

class MessengerSecurityTest extends TestCase
{
	use RefreshDatabase, SeedsStockData;

	private const SELLER_ID = 71;
	private const BUYER_ID = 72;
	private const OUTSIDER_ID = 73;
	private const ATTACHMENT = 'files/us/1/applications/resume.pdf';

	private int $threadId;

	protected function setUp(): void
	{
		parent::setUp();
		$this->seedStockData();
		$this->stockMember(self::SELLER_ID);
		$this->stockMember(self::BUYER_ID);
		$this->stockMember(self::OUTSIDER_ID);
		$post = $this->stockListing('Grey corner sofa', ['user_id' => self::SELLER_ID]);

		$this->threadId = DB::table('threads')->insertGetId([
			'post_id' => $post->id, 'subject' => 'Grey corner sofa', 'created_at' => now(), 'updated_at' => now(),
		]);
		foreach ([self::SELLER_ID, self::BUYER_ID] as $userId) {
			DB::table('threads_participants')->insert([
				'thread_id' => $this->threadId, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
			]);
		}
		DB::table('threads_messages')->insert([
			'thread_id' => $this->threadId, 'user_id' => self::BUYER_ID, 'body' => 'Is the sofa still available?',
			'file_path' => self::ATTACHMENT, 'created_at' => now(), 'updated_at' => now(),
		]);
	}

	public function test_a_member_cannot_reply_into_a_conversation_they_are_not_part_of(): void
	{
		// Act
		$response = $this->actingAs(\App\Models\User::find(self::OUTSIDER_ID))
			->withHeader('X-Requested-With', 'XMLHttpRequest')
			->put('/account/messages/' . $this->threadId, ['body' => 'Send me the money first, I will ship it.']);

		// Assert
		$response->assertJson(['success' => false]);
		$this->assertSame(1, DB::table('threads_messages')->where('thread_id', $this->threadId)->count());
		$this->assertSame(2, DB::table('threads_participants')->where('thread_id', $this->threadId)->count());
	}

	public function test_a_reply_cannot_add_other_people_to_the_conversation(): void
	{
		// Act
		$this->actingAs(\App\Models\User::find(self::BUYER_ID))
			->withHeader('X-Requested-With', 'XMLHttpRequest')
			->put('/account/messages/' . $this->threadId, ['body' => 'Could I come and see it tomorrow?', 'recipients' => [self::OUTSIDER_ID]]);

		// Assert
		$this->assertFalse(DB::table('threads_participants')->where('thread_id', $this->threadId)->where('user_id', self::OUTSIDER_ID)->exists());
	}

	public function test_an_attachment_opens_only_for_the_people_in_its_conversation(): void
	{
		// Arrange
		Storage::fake(config('filesystems.default'));
		Storage::disk(config('filesystems.default'))->put(self::ATTACHMENT, '%PDF-1.4 test');
		$url = '/common/file?path=' . self::ATTACHMENT;

		// Act & Assert
		$this->get($url)->assertForbidden();
		$this->actingAs(\App\Models\User::find(self::OUTSIDER_ID))->get($url)->assertForbidden();
		$this->actingAs(\App\Models\User::find(self::BUYER_ID))->get($url)->assertOk();
	}
}
