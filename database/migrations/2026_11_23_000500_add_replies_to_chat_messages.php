<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat (adniro): a message can be a reply to an earlier message of the same conversation
 * ("reply_to_id"); the reply shows a short quote of it.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->unsignedBigInteger('reply_to_id')->nullable()->after('post_id');
		});
	}

	public function down(): void
	{
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->dropColumn('reply_to_id');
		});
	}
};
