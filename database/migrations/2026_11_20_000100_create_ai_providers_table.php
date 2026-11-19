<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * AI providers managed from the admin panel (adniro:ai).
 * The API key is stored with Laravel's "encrypted" cast, so it is unreadable without APP_KEY.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('ai_providers')) {
			return;
		}
		
		Schema::create('ai_providers', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->string('name', 100);
			$table->string('driver', 50);
			$table->text('api_key')->nullable();
			$table->string('base_url', 255)->nullable();
			$table->boolean('active')->default(true);
			$table->timestamp('last_tested_at')->nullable();
			$table->boolean('last_test_ok')->nullable();
			$table->string('last_test_error', 500)->nullable();
			$table->timestamps();
			
			$table->index('active', 'ai_providers_active_index');
		});
	}
	
	public function down(): void
	{
		Schema::dropIfExists('ai_providers');
	}
};
