<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Sellers followed by the users (seller's public page: /user/{username}).
 * The followed seller is a user (followed_user_id), or the poster of listings
 * published without account (followed_guest_username = posts.guest_username).
 */
return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('follows')) {
			return;
		}
		
		Schema::create('follows', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->unsignedBigInteger('follower_id');
			$table->unsignedBigInteger('followed_user_id')->nullable();
			$table->string('followed_guest_username', 100)->nullable();
			$table->timestamp('created_at')->nullable();
			
			$table->unique(['follower_id', 'followed_user_id'], 'follows_follower_user_unique');
			$table->unique(['follower_id', 'followed_guest_username'], 'follows_follower_guest_unique');
			$table->index('followed_user_id', 'follows_followed_user_id_index');
			$table->index('followed_guest_username', 'follows_followed_guest_username_index');
		});
	}
	
	public function down(): void
	{
		Schema::dropIfExists('follows');
	}
};
