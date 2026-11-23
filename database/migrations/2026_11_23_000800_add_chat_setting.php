<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Adds the "Chat" group to Admin > Settings (adniro).
 * Its fields live in App\Models\Setting\ChatSetting, its default values in App\Services\Chat\ChatSettings.
 */
return new class extends Migration
{
	private const NAME = 'chat';

	public function up(): void
	{
		if (!Schema::hasTable('settings') || DB::table('settings')->where('name', self::NAME)->exists()) {
			return;
		}

		$maxRgt = (int)DB::table('settings')->max('rgt');

		DB::table('settings')->insert([
			'name'        => self::NAME,
			'label'       => 'Chat',
			'description' => 'Live chat: on or off, photos, send limits and the unread-messages email',
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
		if (Schema::hasTable('settings')) {
			DB::table('settings')->where('name', self::NAME)->delete();
		}
	}
};
