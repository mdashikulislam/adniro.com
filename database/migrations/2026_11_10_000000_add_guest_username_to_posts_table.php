<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Username of the poster of a listing published without account (guest listing).
 * It's the key of the seller's public page (/user/{username}), like the "username"
 * column of the "users" table for the listings published from an account.
 * All the guest listings published with the same email address (or phone number) share it.
 *
 * Note: MySQL 8 adds the column instantly & builds the index online,
 * the table stays readable & writable meanwhile.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('posts', function (Blueprint $table) {
			if (!Schema::hasColumn('posts', 'guest_username')) {
				$table->string('guest_username', 100)->nullable();
			}
		});
		Schema::table('posts', function (Blueprint $table) {
			if (!Schema::hasIndex('posts', 'posts_guest_username_index')) {
				$table->index('guest_username', 'posts_guest_username_index');
			}
		});
	}
	
	public function down(): void
	{
		Schema::table('posts', function (Blueprint $table) {
			if (Schema::hasIndex('posts', 'posts_guest_username_index')) {
				$table->dropIndex('posts_guest_username_index');
			}
		});
		Schema::table('posts', function (Blueprint $table) {
			if (Schema::hasColumn('posts', 'guest_username')) {
				$table->dropColumn('guest_username');
			}
		});
	}
};
