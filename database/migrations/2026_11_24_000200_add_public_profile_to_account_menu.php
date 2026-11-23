<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * adniro: "View public profile" at the top of the member's account menu in the header, opening their own
 * public profile in a new tab (route account.public-profile). Menu items form one nested set (lft/rgt), so a
 * gap is opened right after the menu's left edge. down() removes the link and closes the gap.
 */
return new class extends Migration
{
	private const ROUTE = 'account.public-profile';
	/** A link already in the member's menu: its parent receives the new one */
	private const SIBLING_ROUTE = 'account.listings.online';
	private const LABELS = [
		'en' => 'View public profile', 'fr' => 'Voir le profil public', 'es' => 'Ver perfil público',
		'de' => 'Öffentliches Profil ansehen', 'it' => 'Vedi profilo pubblico', 'ru' => 'Открыть публичный профиль',
		'ar' => 'عرض الملف العام', 'bn' => 'পাবলিক প্রোফাইল দেখুন', 'hi' => 'सार्वजनिक प्रोफ़ाइल देखें',
	];

	public function up(): void
	{
		if (!Schema::hasTable('menu_items') || !Schema::hasTable('menus') || DB::table('menu_items')->where('route_name', self::ROUTE)->exists()) {
			return;
		}

		// "My listings" is in the footer too: the one in the header's menu
		$headerMenuId = DB::table('menus')->where('location', 'header')->value('id');
		$sibling = DB::table('menu_items')
			->where('route_name', self::SIBLING_ROUTE)
			->where('menu_id', $headerMenuId)
			->whereNotNull('parent_id')
			->first(['menu_id', 'parent_id', 'depth', 'conditions']);
		$parent = $sibling ? DB::table('menu_items')->where('id', $sibling->parent_id)->first(['id', 'lft']) : null;
		if (!$parent) {
			return; // the menu was rebuilt by the admin: add the link in Admin > Menus
		}

		DB::transaction(function () use ($parent, $sibling) {
			$lft = (int)$parent->lft + 1; // the parent's first place
			DB::table('menu_items')->where('rgt', '>=', $lft)->increment('rgt', 2);
			DB::table('menu_items')->where('lft', '>=', $lft)->increment('lft', 2);

			DB::table('menu_items')->insert([
				'menu_id'    => $sibling->menu_id,
				'parent_id'  => $parent->id,
				'type'       => 'link',
				'icon'       => 'bi-person-badge',
				'label'      => json_encode(self::LABELS, JSON_UNESCAPED_UNICODE),
				'url_type'   => 'route',
				'route_name' => self::ROUTE,
				'target'     => '_blank',
				'conditions' => $sibling->conditions,
				'lft'        => $lft,
				'rgt'        => $lft + 1,
				'depth'      => $sibling->depth,
				'active'     => 1,
				'created_at' => now(),
				'updated_at' => now(),
			]);
		});

		$this->forgetCache();
	}

	public function down(): void
	{
		if (!Schema::hasTable('menu_items')) {
			return;
		}

		DB::transaction(function () {
			foreach (DB::table('menu_items')->where('route_name', self::ROUTE)->get(['id', 'lft']) as $item) {
				DB::table('menu_items')->where('id', $item->id)->delete();
				DB::table('menu_items')->where('lft', '>', $item->lft)->decrement('lft', 2);
				DB::table('menu_items')->where('rgt', '>', $item->lft)->decrement('rgt', 2);
			}
		});

		$this->forgetCache();
	}

	private function forgetCache(): void
	{
		try {
			caching()->invalidate(MenuItem::class);
		} catch (Throwable $e) {
			logger()->warning('Menu cache not invalidated after migration: ' . $e->getMessage());
		}
	}
};
