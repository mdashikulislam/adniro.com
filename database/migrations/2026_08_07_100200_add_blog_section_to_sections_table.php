<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * Add the "Blog" homepage section (appended at the end of the sections list).
	 * Its position can then be changed from: Admin panel -> Settings -> Homepage
	 *
	 * @return void
	 */
	public function up(): void
	{
		$exists = DB::table('sections')
			->where('belongs_to', 'home')
			->where('name', 'blog')
			->exists();
		if ($exists) {
			return;
		}

		$maxRgt = (int)DB::table('sections')->max('rgt');

		DB::table('sections')->insert([
			'belongs_to'   => 'home',
			'name'         => 'blog',
			'label'        => 'Blog',
			'description'  => 'Latest Blog Posts Section',
			'fields'       => null,
			'field_values' => null,
			'parent_id'    => null,
			'lft'          => $maxRgt + 1,
			'rgt'          => $maxRgt + 2,
			'depth'        => 0,
			'active'       => 1,
			'created_at'   => now(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s'),
			'updated_at'   => null,
		]);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		DB::table('sections')
			->where('belongs_to', 'home')
			->where('name', 'blog')
			->delete();
	}
};
