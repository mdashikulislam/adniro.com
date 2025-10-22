<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up(): void
	{
		Schema::create('user_social_logins', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->string('provider', 100)->nullable()->comment('facebook, google, twitter, linkedin, ...');
			$table->string('provider_id', 191)->nullable()->comment('Provider User ID');
			$table->string('token', 191)->nullable()->comment('Access token (if needed)');
			$table->string('avatar', 191)->nullable()->comment('Avatar URL (optional)');
			$table->timestamps();
			
			$table->unique(['user_id', 'provider']);
			$table->unique(['provider', 'provider_id']);
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		Schema::dropIfExists('user_social_logins');
	}
};
