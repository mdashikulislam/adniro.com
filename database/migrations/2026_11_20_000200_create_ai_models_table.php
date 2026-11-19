<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * AI models offered by each provider, managed from the admin panel (adniro:ai).
 * "model_id" is the exact string the provider expects. Prices are USD per million tokens.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('ai_models')) {
			return;
		}
		
		Schema::create('ai_models', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->unsignedBigInteger('ai_provider_id');
			$table->string('model_id', 191);
			$table->string('label', 191)->nullable();
			$table->boolean('supports_vision')->default(false);
			$table->boolean('supports_structured_output')->default(true);
			$table->boolean('supports_transcription')->default(false);
			$table->decimal('input_price_per_mtok', 10, 4)->nullable();
			$table->decimal('output_price_per_mtok', 10, 4)->nullable();
			$table->text('options')->nullable();
			$table->boolean('active')->default(true);
			$table->timestamp('last_tested_at')->nullable();
			$table->boolean('last_test_ok')->nullable();
			$table->string('last_test_error', 500)->nullable();
			$table->timestamp('moderation_eval_passed_at')->nullable();
			$table->timestamp('quickpost_eval_passed_at')->nullable();
			$table->timestamps();
			
			$table->unique(['ai_provider_id', 'model_id'], 'ai_models_provider_model_unique');
			$table->index('active', 'ai_models_active_index');
			$table->foreign('ai_provider_id', 'ai_models_ai_provider_id_foreign')
				->references('id')->on('ai_providers')
				->cascadeOnDelete();
		});
	}
	
	public function down(): void
	{
		Schema::dropIfExists('ai_models');
	}
};
