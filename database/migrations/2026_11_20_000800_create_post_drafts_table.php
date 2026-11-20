<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Quick post drafts (adniro:ai). One row per AI draft request.
 * A successful draft that counts against the AI post limit has counted = 1.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('post_drafts')) {
			return;
		}
		
		Schema::create('post_drafts', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->string('token', 40)->unique();
			$table->bigInteger('user_id')->unsigned()->nullable();
			$table->string('session_id', 100)->nullable();
			$table->string('ip', 45)->nullable();
			$table->string('locale', 10)->nullable();
			$table->string('country_code', 2)->nullable();
			$table->text('input_text')->nullable();
			$table->json('input_meta')->nullable()->comment('Location hint and similar');
			$table->json('picture_paths')->nullable();
			$table->json('raw_output')->nullable();
			$table->json('draft')->nullable()->comment('Sanitised draft');
			$table->string('status', 20)->default('new')->comment('new, drafted, unusable, failed, applied, posted');
			$table->boolean('counted')->default(false);
			$table->unsignedTinyInteger('redrafts')->default(0);
			$table->bigInteger('redraft_of')->unsigned()->nullable();
			$table->bigInteger('ai_usage_log_id')->unsigned()->nullable();
			$table->bigInteger('post_id')->unsigned()->nullable();
			$table->string('error', 500)->nullable();
			$table->timestamp('expires_at')->nullable();
			$table->timestamps();
			
			$table->index(['user_id', 'counted', 'created_at']);
			$table->index(['ip', 'counted', 'created_at']);
			$table->index('expires_at');
		});
	}
	
	public function down(): void
	{
		Schema::dropIfExists('post_drafts');
	}
};
