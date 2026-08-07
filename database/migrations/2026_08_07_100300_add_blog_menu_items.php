<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * Add a "Blog" link at the end of the header & footer menus.
	 * Their position can then be changed from: Admin panel -> Menus
	 *
	 * @return void
	 */
	public function up(): void
	{
		$label = json_encode([
			'en' => 'Blog',
			'fr' => 'Blog',
			'es' => 'Blog',
			'pt' => 'Blogue',
			'de' => 'Blog',
			'it' => 'Blog',
			'ro' => 'Blog',
			'tr' => 'Blog',
			'ru' => 'Блог',
			'ar' => 'المدونة',
			'hi' => 'ब्लॉग',
			'bn' => 'ব্লগ',
			'zh' => '博客',
			'ja' => 'ブログ',
			'th' => 'บล็อก',
			'he' => 'בלוג',
			'ka' => 'ბლოგი',
		], JSON_UNESCAPED_UNICODE);

		$menus = DB::table('menus')->whereIn('location', ['header', 'footer'])->get(['id']);
		$now = now(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s');

		foreach ($menus as $menu) {
			$exists = DB::table('menu_items')
				->where('menu_id', $menu->id)
				->where('route_name', 'blog.index')
				->exists();
			if ($exists) {
				continue;
			}

			$maxRgt = (int)DB::table('menu_items')->where('menu_id', $menu->id)->max('rgt');

			DB::table('menu_items')->insert([
				'menu_id'    => $menu->id,
				'parent_id'  => null,
				'type'       => 'link',
				'label'      => $label,
				'url_type'   => 'route',
				'route_name' => 'blog.index',
				'lft'        => $maxRgt + 1,
				'rgt'        => $maxRgt + 2,
				'depth'      => 0,
				'active'     => 1,
				'created_at' => $now,
				'updated_at' => $now,
			]);
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void
	{
		DB::table('menu_items')->where('route_name', 'blog.index')->delete();
	}
};
