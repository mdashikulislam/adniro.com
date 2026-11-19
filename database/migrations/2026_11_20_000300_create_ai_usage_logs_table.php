<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * One row per AI call, successful or not (adniro:ai).
 * Used for cost tracking, the daily budget and the per-person AI limits.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('ai_usage_logs')) {
			return;
		}
		
		Schema::create('ai_usage_logs', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->string('feature', 40);
			$table->unsignedBigInteger('ai_model_id')->nullable();
			$table->string('provider', 50)->nullable();
			$table->string('model', 191)->nullable();
			$table->unsignedBigInteger('user_id')->nullable();
			$table->string('ip', 50)->nullable();
			$table->string('subject_type', 40)->nullable();
			$table->string('subject_id', 64)->nullable();
			$table->string('status', 20);
			$table->string('error_type', 100)->nullable();
			$table->string('error_message', 500)->nullable();
			$table->unsignedInteger('input_tokens')->default(0);
			$table->unsignedInteger('output_tokens')->default(0);
			$table->unsignedInteger('cache_read_tokens')->default(0);
			$table->unsignedInteger('cache_write_tokens')->default(0);
			$table->decimal('cost_usd', 12, 6)->nullable();
			$table->unsignedInteger('latency_ms')->default(0);
			$table->boolean('was_fallback')->default(false);
			$table->text('meta')->nullable();
			$table->timestamp('created_at')->nullable();
			
			$table->index(['feature', 'created_at'], 'ai_usage_logs_feature_created_at_index');
			$table->index(['user_id', 'feature', 'created_at'], 'ai_usage_logs_user_feature_created_at_index');
			$table->index(['ip', 'feature', 'created_at'], 'ai_usage_logs_ip_feature_created_at_index');
			$table->index('created_at', 'ai_usage_logs_created_at_index');
		});
	}
	
	public function down(): void
	{
		Schema::dropIfExists('ai_usage_logs');
	}
};
