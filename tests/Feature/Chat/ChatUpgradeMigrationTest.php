<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The old Messenger's conversations become chat conversations (adniro), by the migration
 * 2026_11_23_000100_upgrade_threads_for_live_chat: what a member had deleted stays hidden for them, and
 * nobody is emailed about messages that were already unread before the chat existed.
 */
class ChatUpgradeMigrationTest extends ChatTestCase
{
	private const UPGRADE = 'database/migrations/2026_11_23_000100_upgrade_threads_for_live_chat.php';
	private const LATER = [
		'database/migrations/2026_11_23_000400_keep_one_conversation_per_two_people.php',
		'database/migrations/2026_11_23_000500_add_replies_to_chat_messages.php',
	];

	public function test_the_upgrade_keeps_deleted_conversations_hidden_and_old_unread_messages_quiet(): void
	{
		// Arrange: the tables as the Messenger left them. The buyer wrote once, then deleted the
		// conversation from their list; the seller never read the message
		$upgrade = require base_path(self::UPGRADE);
		$later = array_map(fn (string $path) => require base_path($path), self::LATER);
		foreach (array_reverse($later) as $migration) {
			$migration->down();
		}
		Schema::table('threads', fn ($table) => $table->unique(['post_id', 'buyer_id'], 'threads_post_buyer_unique'));
		$upgrade->down();

		$threadId = DB::table('threads')->insertGetId(['post_id' => $this->listing->id, 'subject' => 'Grey corner sofa', 'created_at' => now(), 'updated_at' => now()]);
		DB::table('threads_participants')->insert([
			['thread_id' => $threadId, 'user_id' => self::BUYER_ID, 'last_read' => now(), 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
			['thread_id' => $threadId, 'user_id' => self::SELLER_ID, 'last_read' => null, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()],
		]);
		DB::table('threads_messages')->insert(['thread_id' => $threadId, 'user_id' => self::BUYER_ID, 'body' => 'Is this still available?', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

		// Act
		$upgrade->up();
		foreach ($later as $migration) {
			$migration->up();
		}

		// Assert
		$buyerRow = DB::table('threads_participants')->where('thread_id', $threadId)->where('user_id', self::BUYER_ID)->first();
		$sellerRow = DB::table('threads_participants')->where('thread_id', $threadId)->where('user_id', self::SELLER_ID)->first();
		try {
			$this->assertNotNull($buyerRow->deleted_at, 'The buyer had deleted this conversation: it stays hidden for them.');
			$this->assertNull($sellerRow->deleted_at);
			$this->assertSame(1, (int)$sellerRow->unread_count);
			$this->assertNotNull($sellerRow->digest_sent_at, 'An old unread message is not emailed about when the chat goes live.');
			$this->actingAs($this->buyer)->getJson('/chat/api/threads')->assertOk()->assertJsonCount(0, 'threads');
			$this->actingAs($this->seller)->getJson('/chat/api/threads')->assertOk()->assertJsonCount(1, 'threads');
		} finally {
			// Changing tables ends the test's transaction: these rows would stay for the next tests
			DB::table('threads_messages')->where('thread_id', $threadId)->delete();
			DB::table('threads_participants')->where('thread_id', $threadId)->delete();
			DB::table('threads')->where('id', $threadId)->delete();
		}
	}
}
