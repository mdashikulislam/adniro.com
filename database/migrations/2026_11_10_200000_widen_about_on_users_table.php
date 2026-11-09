<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The user's description, shown on the seller's public page (/user/{username}):
 * 500 characters instead of 255.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->string('about', 500)->nullable()->change();
		});
	}
	
	public function down(): void
	{
		// Left at 500 characters: shrinking the column back would truncate the saved descriptions
	}
};
