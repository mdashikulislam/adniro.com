<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The status the rules decided, recorded in shadow mode too (adniro:ai).
 * "applied_status" stays null until a decision is actually written to the listing.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('post_moderations', function (Blueprint $table) {
			if (!Schema::hasColumn('post_moderations', 'decided_status')) {
				$table->string('decided_status', 20)->nullable()->after('verdict');
				$table->string('mode', 10)->nullable()->after('source');
				$table->string('rule', 40)->nullable()->after('decided_status');
				$table->index('decided_status');
			}
		});
	}
	
	public function down(): void
	{
		Schema::table('post_moderations', function (Blueprint $table) {
			if (Schema::hasColumn('post_moderations', 'decided_status')) {
				$table->dropIndex(['decided_status']);
				$table->dropColumn(['decided_status', 'mode', 'rule']);
			}
		});
	}
};
