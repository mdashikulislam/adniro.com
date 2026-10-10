<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Indexes for the queries that scan a whole country's listings, so they stay
 * index-only scans (no table rows read, no temporary table / filesort) with
 * millions of listings:
 *
 * - idx_posts_live_cat_city: live listings counts per category × city
 *   (ListingCounts::forCountry(): which pages are indexable, sitemaps, sidebar)
 *   and the listings sitemaps' chunks & their "lastmod" (the primary key is part of
 *   every InnoDB index).
 * - idx_posts_lastmod_cat_city: last change per category × city
 *   (ListingCounts::lastModifiedForCountry(): the sitemaps' "lastmod").
 *
 * Note: MySQL 8 builds these online (InnoDB "ALGORITHM=INPLACE, LOCK=NONE"),
 * the table stays readable & writable while they're built.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('posts', function (Blueprint $table) {
			if (!Schema::hasIndex('posts', 'idx_posts_live_cat_city')) {
				$table->index(
					['country_code', 'archived_at', 'deleted_at', 'category_id', 'city_id', 'email_verified_at', 'phone_verified_at', 'reviewed_at', 'updated_at', 'created_at'],
					'idx_posts_live_cat_city'
				);
			}
			if (!Schema::hasIndex('posts', 'idx_posts_lastmod_cat_city')) {
				$table->index(
					['country_code', 'category_id', 'city_id', 'updated_at', 'created_at'],
					'idx_posts_lastmod_cat_city'
				);
			}
		});
	}

	public function down(): void
	{
		Schema::table('posts', function (Blueprint $table) {
			if (Schema::hasIndex('posts', 'idx_posts_live_cat_city')) {
				$table->dropIndex('idx_posts_live_cat_city');
			}
			if (Schema::hasIndex('posts', 'idx_posts_lastmod_cat_city')) {
				$table->dropIndex('idx_posts_lastmod_cat_city');
			}
		});
	}
};
