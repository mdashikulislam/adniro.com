<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Cover image of the user, shown at the top of the seller's public page (/user/{username}).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			if (!Schema::hasColumn('users', 'cover_path')) {
				$table->string('cover_path', 255)->nullable();
			}
		});
	}
	
	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			if (Schema::hasColumn('users', 'cover_path')) {
				$table->dropColumn('cover_path');
			}
		});
	}
};
