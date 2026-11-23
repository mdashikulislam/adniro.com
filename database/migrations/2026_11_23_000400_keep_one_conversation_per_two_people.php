<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat (adniro): one conversation per two people, whatever the number of listings they talk about.
 * Until now there was one per listing and buyer, so a seller with several listings appeared several times in
 * the same person's inbox.
 *
 * - threads: the two people as a pair (the lower and the higher member id), unique. "post_id" becomes the
 *   listing they last talked about.
 * - threads_messages: "post_id", the listing a message is about (the first message sent from that listing).
 *
 * Existing conversations between the same two people are merged into the oldest. Rows are written with the
 * query builder, so no model observer runs.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('threads', function (Blueprint $table) {
			$table->unsignedBigInteger('user_low_id')->nullable()->after('buyer_id');
			$table->unsignedBigInteger('user_high_id')->nullable()->after('user_low_id');
		});
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->unsignedBigInteger('post_id')->nullable()->after('thread_id');
			$table->index('post_id', 'threads_messages_post_id_index');
		});

		$this->tagFirstMessagesWithTheirListing();
		$this->fillPairs();
		$this->mergeConversationsOfTheSamePair();

		Schema::table('threads', function (Blueprint $table) {
			$table->dropUnique('threads_post_buyer_unique');
			$table->unique(['user_low_id', 'user_high_id'], 'threads_pair_unique');
		});
	}

	/** The merged conversations stay merged; the old "one per listing and buyer" key is not put back. */
	public function down(): void
	{
		Schema::table('threads', function (Blueprint $table) {
			$table->dropUnique('threads_pair_unique');
			$table->dropColumn(['user_low_id', 'user_high_id']);
		});
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->dropIndex('threads_messages_post_id_index');
			$table->dropColumn('post_id');
		});
	}

	/** Each conversation was about one listing: its first message now says which. */
	private function tagFirstMessagesWithTheirListing(): void
	{
		$threads = DB::table('threads')->whereNull('deleted_at')->whereNotNull('post_id')->get(['id', 'post_id']);

		foreach ($threads as $thread) {
			$firstMessageId = DB::table('threads_messages')->where('thread_id', $thread->id)->whereNull('deleted_at')->min('id');
			if (!empty($firstMessageId)) {
				DB::table('threads_messages')->where('id', $firstMessageId)->update(['post_id' => $thread->post_id]);
			}
		}
	}

	private function fillPairs(): void
	{
		foreach (DB::table('threads')->whereNull('deleted_at')->pluck('id') as $threadId) {
			$userIds = DB::table('threads_participants')->where('thread_id', $threadId)->orderBy('user_id')->pluck('user_id')->unique()->values();
			if ($userIds->count() !== 2) {
				continue;
			}

			DB::table('threads')->where('id', $threadId)->update(['user_low_id' => $userIds[0], 'user_high_id' => $userIds[1]]);
		}
	}

	private function mergeConversationsOfTheSamePair(): void
	{
		$pairs = DB::table('threads')
			->whereNotNull('user_low_id')
			->selectRaw('user_low_id, user_high_id, MIN(id) as keep_id, COUNT(*) as total')
			->groupBy('user_low_id', 'user_high_id')
			->having('total', '>', 1)
			->get();

		foreach ($pairs as $pair) {
			$duplicateIds = DB::table('threads')
				->where('user_low_id', $pair->user_low_id)
				->where('user_high_id', $pair->user_high_id)
				->where('id', '!=', $pair->keep_id)
				->pluck('id');

			$lastRead = $this->lastReadByUser([...$duplicateIds->all(), $pair->keep_id]);
			DB::table('threads_messages')->whereIn('thread_id', $duplicateIds)->update(['thread_id' => $pair->keep_id]);
			$this->keepVisibleForThoseWhoStillHadOne((int)$pair->keep_id, $duplicateIds->all());
			DB::table('threads_participants')->whereIn('thread_id', $duplicateIds)->delete();
			DB::table('threads')->whereIn('id', $duplicateIds)->delete();

			$this->refreshConversation((int)$pair->keep_id, $lastRead);
		}
	}

	/** A member who had hidden the kept conversation, but not one merged into it, goes on seeing it. */
	private function keepVisibleForThoseWhoStillHadOne(int $keepId, array $duplicateIds): void
	{
		$userIds = DB::table('threads_participants')->whereIn('thread_id', $duplicateIds)->whereNull('deleted_at')->pluck('user_id');

		DB::table('threads_participants')->where('thread_id', $keepId)->whereIn('user_id', $userIds)->update(['deleted_at' => null]);
	}

	/** @return array<int, int> the newest message each person had read in any of the conversations being merged */
	private function lastReadByUser(array $threadIds): array
	{
		return DB::table('threads_participants')
			->whereIn('thread_id', $threadIds)
			->selectRaw('user_id, MAX(last_read_message_id) as last_read')
			->groupBy('user_id')
			->pluck('last_read', 'user_id')
			->map(fn ($id) => (int)$id)
			->all();
	}

	/** After a merge: the conversation's last message and listing, and each person's read state, counted again. */
	private function refreshConversation(int $threadId, array $lastReadByUser): void
	{
		$messages = DB::table('threads_messages')->where('thread_id', $threadId)->whereNull('deleted_at');
		$lastMessageId = (clone $messages)->max('id');
		$lastPostId = (clone $messages)->whereNotNull('post_id')->orderByDesc('id')->value('post_id');

		DB::table('threads')->where('id', $threadId)->update(array_filter([
			'last_message_id' => $lastMessageId,
			'last_message_at' => DB::table('threads_messages')->where('id', $lastMessageId)->value('created_at'),
			'post_id'         => $lastPostId,
			'subject'         => empty($lastPostId) ? null : DB::table('posts')->where('id', $lastPostId)->value('title'),
		]));

		foreach (DB::table('threads_participants')->where('thread_id', $threadId)->get(['id', 'user_id']) as $participant) {
			$lastReadId = $lastReadByUser[(int)$participant->user_id] ?? 0;

			DB::table('threads_participants')->where('id', $participant->id)->update([
				'last_read_message_id' => $lastReadId ?: null,
				'unread_count'         => (clone $messages)->where('user_id', '!=', $participant->user_id)->where('id', '>', $lastReadId)->count(),
			]);
		}
	}
};
