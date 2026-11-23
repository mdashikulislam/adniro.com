<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat (adniro): tidying the inbox, and privacy choices.
 *
 * - threads_participants.archived_at: the member moved the conversation out of their list, to "Archived".
 *   It comes back with the next message. (Muting uses "muted_until", which the table already has.)
 * - chat_preferences.shows_online / shows_seen: whether the member lets others see that they are online or
 *   typing, and that they have read a message. Both are on until the member turns them off.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->timestamp('archived_at')->nullable()->after('muted_until');
		});
		Schema::table('chat_preferences', function (Blueprint $table) {
			$table->boolean('shows_online')->default(true)->after('saved_replies');
			$table->boolean('shows_seen')->default(true)->after('shows_online');
		});
	}

	public function down(): void
	{
		Schema::table('chat_preferences', function (Blueprint $table) {
			$table->dropColumn(['shows_online', 'shows_seen']);
		});
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->dropColumn('archived_at');
		});
	}
};
