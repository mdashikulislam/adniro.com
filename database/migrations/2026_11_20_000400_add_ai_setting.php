<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Adds the "AI" group to Admin > Settings (adniro:ai).
 * Its fields and default values live in App\Models\Setting\AiSetting.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (!Schema::hasTable('settings')) {
			return;
		}
		
		if (DB::table('settings')->where('name', 'ai')->exists()) {
			return;
		}
		
		$maxRgt = (int)DB::table('settings')->max('rgt');
		
		DB::table('settings')->insert([
			'name'        => 'ai',
			'label'       => 'AI',
			'description' => 'AI providers, models, limits and budget',
			'parent_id'   => null,
			'lft'         => $maxRgt + 1,
			'rgt'         => $maxRgt + 2,
			'depth'       => 0,
			'active'      => 1,
			'created_at'  => now(),
			'updated_at'  => now(),
		]);
	}
	
	public function down(): void
	{
		if (!Schema::hasTable('settings')) {
			return;
		}
		
		DB::table('settings')->where('name', 'ai')->delete();
	}
};
