<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat (adniro): deleting.
 *
 * - threads_messages.unsent_at: the sender took the message back. Both people see "Message deleted" in its
 *   place. The row and its text are kept, so a reported conversation can still be read by the admins.
 * - threads_participants.cleared_message_id: the member deleted the conversation from their own list. Messages
 *   up to this one are no longer shown to them; the other person keeps everything.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->timestamp('unsent_at')->nullable()->after('client_uuid');
		});
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->unsignedBigInteger('cleared_message_id')->nullable()->after('last_read_message_id');
		});
	}

	public function down(): void
	{
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->dropColumn('cleared_message_id');
		});
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->dropColumn('unsent_at');
		});
	}
};
