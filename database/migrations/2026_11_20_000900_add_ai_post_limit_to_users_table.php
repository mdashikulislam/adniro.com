<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Per-user AI post limit override, and the moment an admin reset the user's count (adniro:ai).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			if (!Schema::hasColumn('users', 'ai_post_limit')) {
				$table->unsignedInteger('ai_post_limit')->nullable();
			}
			if (!Schema::hasColumn('users', 'ai_post_reset_at')) {
				$table->timestamp('ai_post_reset_at')->nullable();
			}
		});
	}
	
	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			foreach (['ai_post_limit', 'ai_post_reset_at'] as $column) {
				if (Schema::hasColumn('users', $column)) {
					$table->dropColumn($column);
				}
			}
		});
	}
};
