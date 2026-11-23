<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat (adniro): offers and seller tools.
 *
 * - threads_messages.offer_*: a message of type "offer" carries a price for the listing it is about, in that
 *   listing's currency, and whether it is still open. Its answer is a message of type "offer_answer".
 * - threads_messages.is_auto: sent by the site for a member who is away (their away message).
 * - threads_participants.away_replied_at: when this member's away message was last sent in the conversation,
 *   so it is not repeated at every message.
 * - chat_preferences: a member's away message and saved replies.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->decimal('offer_amount', 17, 2)->nullable()->after('unsent_at');
			$table->string('offer_currency', 3)->nullable()->after('offer_amount');
			$table->string('offer_status', 10)->nullable()->after('offer_currency');
			$table->boolean('is_auto')->default(false)->after('offer_status');
		});
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->timestamp('away_replied_at')->nullable()->after('digest_sent_at');
		});
		Schema::create('chat_preferences', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('user_id')->unique();
			$table->boolean('away_on')->default(false);
			$table->string('away_message', 300)->nullable();
			$table->json('saved_replies')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('chat_preferences');
		Schema::table('threads_participants', function (Blueprint $table) {
			$table->dropColumn('away_replied_at');
		});
		Schema::table('threads_messages', function (Blueprint $table) {
			$table->dropColumn(['offer_amount', 'offer_currency', 'offer_status', 'is_auto']);
		});
	}
};
