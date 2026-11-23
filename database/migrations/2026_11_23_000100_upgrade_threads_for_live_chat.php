<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat (adniro): the Messenger's three tables are kept and upgraded in place.
 *
 * - threads: one conversation per listing and buyer (buyer_id), and its last message, so the inbox sorts
 *   without a join.
 * - threads_messages: a type, and the sender's own id for the message (client_uuid), so a double tap or a
 *   retry on a bad connection saves it once.
 * - threads_participants: how far each person has read, their stored unread count, mute, and when the
 *   unread email last went out.
 *
 * Existing conversations are filled in, and several conversations between the same buyer and listing are
 * merged into the oldest. Rows are written with the query builder, so no model observer runs.
 */
return new class extends Migration
{
	public function up(): void
	{
		$this->removeDuplicateParticipants();
		$this->addColumns();
		$this->fillBuyers();
		$this->mergeDuplicateThreads();
		$this->fillLastMessages();
		$this->fillReadState();
		$this->addUniqueKeys();
	}

	public function down(): void
	{
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->dropUnique('threads_participants_thread_user_unique');
			$table->dropIndex('threads_participants_user_unread_index');
			$table->dropColumn(['last_read_message_id', 'unread_count', 'muted_until', 'digest_sent_at']);
		});
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->dropUnique('threads_messages_client_unique');
			$table->dropIndex('threads_messages_thread_id_id_index');
			$table->dropColumn(['type', 'client_uuid']);
		});
		Schema::table('threads', function (Blueprint $table) {
			$table->dropUnique('threads_post_buyer_unique');
			$table->dropIndex('threads_last_message_at_index');
			$table->dropColumn(['buyer_id', 'last_message_id', 'last_message_at']);
		});
	}

	private function addColumns(): void
	{
		Schema::table('threads', function (Blueprint $table) {
			$table->unsignedBigInteger('buyer_id')->nullable()->after('post_id');
			$table->unsignedBigInteger('last_message_id')->nullable()->after('subject');
			$table->timestamp('last_message_at')->nullable()->after('last_message_id');
			$table->index('last_message_at', 'threads_last_message_at_index');
		});
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->string('type', 16)->default('text')->after('user_id');
			$table->char('client_uuid', 36)->nullable()->after('file_path');
			$table->index(['thread_id', 'id'], 'threads_messages_thread_id_id_index');
		});
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->unsignedBigInteger('last_read_message_id')->nullable()->after('last_read');
			$table->unsignedInteger('unread_count')->default(0)->after('last_read_message_id');
			$table->timestamp('muted_until')->nullable()->after('is_important');
			$table->timestamp('digest_sent_at')->nullable()->after('muted_until');
			$table->index(['user_id', 'unread_count'], 'threads_participants_user_unread_index');
		});
	}

	private function addUniqueKeys(): void
	{
		Schema::table('threads', function (Blueprint $table) {
			$table->unique(['post_id', 'buyer_id'], 'threads_post_buyer_unique');
		});
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->unique(['thread_id', 'client_uuid'], 'threads_messages_client_unique');
		});
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->unique(['thread_id', 'user_id'], 'threads_participants_thread_user_unique');
		});
	}

	/** One row per person and conversation: the oldest is kept. */
	private function removeDuplicateParticipants(): void
	{
		$table = DB::getTablePrefix() . 'threads_participants';
		DB::statement(
			"DELETE newer FROM {$table} newer INNER JOIN {$table} older"
			. ' ON newer.thread_id = older.thread_id AND newer.user_id = older.user_id AND newer.id > older.id'
		);
		DB::table('threads_participants')->whereNull('thread_id')->orWhereNull('user_id')->delete();
	}

	/** The buyer is the person in the conversation who does not own the listing. */
	private function fillBuyers(): void
	{
		$threads = DB::table('threads')->whereNull('deleted_at')->whereNotNull('post_id')->get(['id', 'post_id']);

		foreach ($threads as $thread) {
			$sellerId = DB::table('posts')->where('id', $thread->post_id)->value('user_id');
			if (empty($sellerId)) {
				continue;
			}

			$buyerId = DB::table('threads_participants')
				->where('thread_id', $thread->id)
				->where('user_id', '!=', $sellerId)
				->orderBy('id')
				->value('user_id');
			if (empty($buyerId)) {
				continue;
			}

			DB::table('threads')->where('id', $thread->id)->update(['buyer_id' => $buyerId]);
			DB::table('threads_participants')->where('thread_id', $thread->id)->update(['deleted_at' => null]);
		}
	}

	/** The old Messenger opened a new conversation for every first message: keep the oldest, move the rest into it. */
	private function mergeDuplicateThreads(): void
	{
		$groups = DB::table('threads')
			->whereNotNull('buyer_id')
			->selectRaw('post_id, buyer_id, MIN(id) as keep_id, COUNT(*) as total')
			->groupBy('post_id', 'buyer_id')
			->having('total', '>', 1)
			->get();

		foreach ($groups as $group) {
			$duplicateIds = DB::table('threads')
				->where('post_id', $group->post_id)
				->where('buyer_id', $group->buyer_id)
				->where('id', '!=', $group->keep_id)
				->pluck('id');

			DB::table('threads_messages')->whereIn('thread_id', $duplicateIds)->update(['thread_id' => $group->keep_id]);
			DB::table('threads_participants')->whereIn('thread_id', $duplicateIds)->delete();
			DB::table('threads')->whereIn('id', $duplicateIds)->delete();
		}
	}

	private function fillLastMessages(): void
	{
		$lastMessages = DB::table('threads_messages')
			->whereNull('deleted_at')
			->selectRaw('thread_id, MAX(id) as id')
			->groupBy('thread_id')
			->pluck('id', 'thread_id');

		foreach ($lastMessages as $threadId => $messageId) {
			DB::table('threads')->where('id', $threadId)->update([
				'last_message_id' => $messageId,
				'last_message_at' => DB::table('threads_messages')->where('id', $messageId)->value('created_at'),
			]);
		}
	}

	/** From the old "last read" time: the last message each person has seen, and how many they have not. */
	private function fillReadState(): void
	{
		foreach (DB::table('threads_participants')->orderBy('id')->cursor() as $participant) {
			$messages = DB::table('threads_messages')->where('thread_id', $participant->thread_id)->whereNull('deleted_at');

			$lastReadId = empty($participant->last_read)
				? null
				: (clone $messages)->where('created_at', '<=', $participant->last_read)->max('id');

			$unread = (clone $messages)
				->where('user_id', '!=', $participant->user_id)
				->when(!empty($lastReadId), fn ($query) => $query->where('id', '>', $lastReadId))
				->count();

			DB::table('threads_participants')->where('id', $participant->id)->update([
				'last_read_message_id' => $lastReadId,
				'unread_count'         => $unread,
			]);
		}
	}
};
