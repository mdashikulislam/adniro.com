<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Live chat (adniro): an admin closes a report once it is dealt with (Admin > Users > Chat reports):
 * who closed it and when.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('thread_reports', function (Blueprint $table) {
			$table->unsignedBigInteger('closed_by')->nullable()->after('status');
			$table->timestamp('closed_at')->nullable()->after('closed_by');
		});
	}

	public function down(): void
	{
		Schema::table('thread_reports', function (Blueprint $table) {
			$table->dropColumn(['closed_by', 'closed_at']);
		});
	}
};
