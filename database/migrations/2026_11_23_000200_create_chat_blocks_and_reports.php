<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat safety (adniro): a member can block another member (checked both ways on every send), and
 * report a conversation to the admins. Deleting a member or a conversation removes its rows (foreign keys).
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('user_blocks', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->unsignedBigInteger('blocker_id');
			$table->unsignedBigInteger('blocked_id');
			$table->timestamps();

			$table->unique(['blocker_id', 'blocked_id'], 'user_blocks_pair_unique');
			$table->index('blocked_id', 'user_blocks_blocked_index');
			$table->foreign('blocker_id', 'user_blocks_blocker_fk')->references('id')->on('users')->cascadeOnDelete();
			$table->foreign('blocked_id', 'user_blocks_blocked_fk')->references('id')->on('users')->cascadeOnDelete();
		});

		Schema::create('thread_reports', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->unsignedBigInteger('thread_id');
			$table->unsignedBigInteger('reporter_id');
			$table->string('reason', 16);
			$table->string('note', 500)->nullable();
			$table->string('status', 16)->default('open');
			$table->timestamps();

			$table->index('status', 'thread_reports_status_index');
			$table->index(['thread_id', 'reporter_id'], 'thread_reports_thread_reporter_index');
			$table->foreign('thread_id', 'thread_reports_thread_fk')->references('id')->on('threads')->cascadeOnDelete();
			$table->foreign('reporter_id', 'thread_reports_reporter_fk')->references('id')->on('users')->cascadeOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('thread_reports');
		Schema::dropIfExists('user_blocks');
	}
};
