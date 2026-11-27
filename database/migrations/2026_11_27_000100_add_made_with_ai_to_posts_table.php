<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Marks the listings that were filled in by the AI listing assistant (adniro:ai), for the admin's lists.
 * Set once, when the listing is published from a draft. Never fillable, so a user cannot post it.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasColumn('posts', 'made_with_ai')) {
			return;
		}
		
		Schema::table('posts', function (Blueprint $table) {
			$table->boolean('made_with_ai')->default(false)->after('moderated_at');
		});
	}
	
	public function down(): void
	{
		if (!Schema::hasColumn('posts', 'made_with_ai')) {
			return;
		}
		
		Schema::table('posts', function (Blueprint $table) {
			$table->dropColumn('made_with_ai');
		});
	}
};
