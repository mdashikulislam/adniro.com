<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Moderation state of a listing, kept apart from the human review column "reviewed_at" (adniro:ai).
 * The default "approved" keeps every existing listing, seeder and admin-created row visible.
 * These columns are never fillable, so a user cannot post them.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('posts', function (Blueprint $table) {
			if (!Schema::hasColumn('posts', 'moderation_status')) {
				$table->string('moderation_status', 20)->default('approved')->after('reviewed_at');
				$table->index('moderation_status', 'posts_moderation_status_index');
			}
			if (!Schema::hasColumn('posts', 'moderation_hash')) {
				$table->char('moderation_hash', 64)->nullable()->after('moderation_status');
			}
			if (!Schema::hasColumn('posts', 'moderated_at')) {
				$table->timestamp('moderated_at')->nullable()->after('moderation_hash');
			}
		});
	}
	
	public function down(): void
	{
		Schema::table('posts', function (Blueprint $table) {
			if (Schema::hasColumn('posts', 'moderation_status')) {
				$table->dropIndex('posts_moderation_status_index');
			}
			$columns = array_filter(
				['moderation_status', 'moderation_hash', 'moderated_at'],
				fn ($column) => Schema::hasColumn('posts', $column)
			);
			if (!empty($columns)) {
				$table->dropColumn($columns);
			}
		});
	}
};
