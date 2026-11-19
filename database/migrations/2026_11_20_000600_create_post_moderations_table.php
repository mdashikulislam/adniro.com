<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * History of moderation decisions, one row per decision (adniro:ai).
 * Written by the AI check, by an admin or by the system (sweep, failures).
 */
return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('post_moderations')) {
			return;
		}
		
		Schema::create('post_moderations', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->bigInteger('post_id')->unsigned();
			$table->string('trigger', 30)->nullable()->comment('created, edited, pictures, fields, verified, admin, sweep, rerun');
			$table->string('source', 10)->comment('ai, human, system');
			$table->char('content_hash', 64)->nullable();
			$table->string('verdict', 20)->nullable()->comment('allow, reject, needs_human, as labelled by the model');
			$table->string('applied_status', 20)->nullable()->comment('Status written to the post, null in shadow mode');
			$table->json('categories')->nullable();
			$table->string('confidence', 10)->nullable();
			$table->text('user_reason')->nullable();
			$table->text('internal_reason')->nullable();
			$table->string('locale', 10)->nullable();
			$table->string('model', 191)->nullable();
			$table->string('policy_version', 30)->nullable();
			$table->bigInteger('ai_usage_log_id')->unsigned()->nullable();
			$table->bigInteger('reviewer_id')->unsigned()->nullable();
			$table->timestamp('notified_at')->nullable();
			$table->string('error', 500)->nullable();
			$table->timestamps();
			
			$table->index(['post_id', 'created_at']);
			$table->index('applied_status');
			$table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
			$table->foreign('ai_usage_log_id')->references('id')->on('ai_usage_logs')->nullOnDelete();
			$table->foreign('reviewer_id')->references('id')->on('users')->nullOnDelete();
		});
	}
	
	public function down(): void
	{
		Schema::dropIfExists('post_moderations');
	}
};
