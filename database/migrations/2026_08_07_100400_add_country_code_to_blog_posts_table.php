<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * Country targeting for the blog posts:
	 * - NULL  => the post is displayed in all the countries
	 * - 'XX'  => the post is only displayed in the 'XX' country
	 *
	 * @return void
	 */
	public function up(): void
	{
		Schema::table('blog_posts', function (Blueprint $table) {
			if (!Schema::hasColumn('blog_posts', 'country_code')) {
				$table->string('country_code', 2)->nullable()->after('category_id');

				$table->index(['country_code']);
				$table->index(['country_code', 'active', 'published_at']);
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::table('blog_posts', function (Blueprint $table) {
			if (Schema::hasColumn('blog_posts', 'country_code')) {
				$table->dropIndex(['country_code', 'active', 'published_at']);
				$table->dropIndex(['country_code']);
				$table->dropColumn('country_code');
			}
		});
	}
};
