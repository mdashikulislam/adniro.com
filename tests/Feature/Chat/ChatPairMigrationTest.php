<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Conversations that existed per listing are merged into one per two people (adniro), by the migration
 * 2026_11_23_000400_keep_one_conversation_per_two_people.
 */
class ChatPairMigrationTest extends ChatTestCase
{
	private const MIGRATION = 'database/migrations/2026_11_23_000400_keep_one_conversation_per_two_people.php';

	public function test_conversations_between_the_same_two_people_are_merged_into_the_oldest(): void
	{
		// Arrange: the table as it was before the migration, with a conversation per listing
		$migration = require base_path(self::MIGRATION);
		$migration->down();
		Schema::table('threads', fn ($table) => $table->unique(['post_id', 'buyer_id'], 'threads_post_buyer_unique'));
		$table = $this->stockListing('Oak dining table', ['user_id' => self::SELLER_ID]);

		$sofaThread = $this->oldThread((int)$this->listing->id, 'Grey corner sofa', self::BUYER_ID);
		$sofaMessage = $this->oldMessage($sofaThread, self::BUYER_ID, 'Is the sofa available?');
		$tableThread = $this->oldThread((int)$table->id, 'Oak dining table', self::BUYER_ID);
		$tableQuestion = $this->oldMessage($tableThread, self::BUYER_ID, 'And the table?');
		$tableAnswer = $this->oldMessage($tableThread, self::SELLER_ID, 'Yes, both.');
		$otherThread = $this->oldThread((int)$this->listing->id, 'Grey corner sofa', self::OUTSIDER_ID);
		$this->oldMessage($otherThread, self::OUTSIDER_ID, 'Still for sale?');
		// The seller had read the sofa question, not the table one; the buyer has not read the answer
		$this->setRead($sofaThread, self::SELLER_ID, $sofaMessage, 0);
		$this->setRead($tableThread, self::SELLER_ID, null, 1);
		$this->setRead($tableThread, self::BUYER_ID, $tableQuestion, 1);

		// The seller had deleted the sofa conversation from their list, but not the table one
		DB::table('threads_participants')->where('thread_id', $sofaThread)->where('user_id', self::SELLER_ID)->update(['deleted_at' => now()]);
		// The other person had deleted their only conversation
		DB::table('threads_participants')->where('thread_id', $otherThread)->where('user_id', self::OUTSIDER_ID)->update(['deleted_at' => now()]);

		// Act
		$migration->up();

		// Assert: one conversation for the buyer and the seller, another person's is left alone
		$this->assertSame([$sofaThread, $otherThread], DB::table('threads')->orderBy('id')->pluck('id')->all());
		$merged = DB::table('threads')->where('id', $sofaThread)->first();
		$this->assertSame([self::SELLER_ID, self::BUYER_ID], [(int)$merged->user_low_id, (int)$merged->user_high_id]);
		$this->assertSame($tableAnswer, (int)$merged->last_message_id);
		$this->assertSame((int)$table->id, (int)$merged->post_id, 'The conversation is about the listing talked about last.');
		$this->assertSame('Oak dining table', $merged->subject);

		$messages = DB::table('threads_messages')->where('thread_id', $sofaThread)->orderBy('id')->get();
		$this->assertSame(['Is the sofa available?', 'And the table?', 'Yes, both.'], $messages->pluck('body')->all());
		$this->assertSame([(int)$this->listing->id, (int)$table->id, null], $messages->pluck('post_id')->map(fn ($id) => $id === null ? null : (int)$id)->all());

		$this->assertSame(2, DB::table('threads_participants')->where('thread_id', $sofaThread)->count());
		$this->assertSame(1, $this->unreadOf($sofaThread, self::SELLER_ID));
		$this->assertSame(1, $this->unreadOf($sofaThread, self::BUYER_ID));

		// What a member had deleted stays hidden for them, unless one of the merged conversations was still in their list
		$hidden = fn (int $threadId, int $userId) => DB::table('threads_participants')->where('thread_id', $threadId)->where('user_id', $userId)->value('deleted_at') !== null;
		$this->assertFalse($hidden($sofaThread, self::SELLER_ID), 'The seller still had the table conversation: the merged one is in their list.');
		$this->assertTrue($hidden($otherThread, self::OUTSIDER_ID), 'A conversation its member had deleted stays hidden for them.');

		// And the pair cannot get a second conversation
		$this->actingAs($this->buyer)->postJson('/chat/listings/' . $this->listing->id, ['body' => 'Hello again'])->assertJsonPath('thread.id', $sofaThread);
	}

	private function oldThread(int $postId, string $subject, int $buyerId): int
	{
		$threadId = DB::table('threads')->insertGetId([
			'post_id' => $postId, 'buyer_id' => $buyerId, 'subject' => $subject, 'created_at' => now(), 'updated_at' => now(),
		]);
		foreach ([$buyerId, self::SELLER_ID] as $userId) {
			DB::table('threads_participants')->insert(['thread_id' => $threadId, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]);
		}

		return $threadId;
	}

	private function oldMessage(int $threadId, int $userId, string $body): int
	{
		$messageId = DB::table('threads_messages')->insertGetId([
			'thread_id' => $threadId, 'user_id' => $userId, 'body' => $body, 'created_at' => now(), 'updated_at' => now(),
		]);
		DB::table('threads')->where('id', $threadId)->update(['last_message_id' => $messageId, 'last_message_at' => now()]);

		return $messageId;
	}

	private function setRead(int $threadId, int $userId, ?int $lastReadMessageId, int $unread): void
	{
		DB::table('threads_participants')->where('thread_id', $threadId)->where('user_id', $userId)
			->update(['last_read_message_id' => $lastReadMessageId, 'unread_count' => $unread]);
	}
}
