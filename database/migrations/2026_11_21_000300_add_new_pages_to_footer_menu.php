<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Footer links for the About us, Posting rules and Cookie policy pages (phase 5), added at the end
 * of the footer column that holds the other pages. Menu items form one nested set (lft/rgt), so a
 * gap is opened after the column's last item. down() removes the links and closes the gap.
 */
return new class extends Migration
{
	/** slug => labels, in footer order */
	private const LINKS = [
		'about-us'      => ['en' => 'About us', 'fr' => 'À propos', 'es' => 'Sobre nosotros', 'de' => 'Über uns', 'it' => 'Chi siamo', 'ru' => 'О нас', 'ar' => 'من نحن', 'hi' => 'हमारे बारे में', 'bn' => 'আমাদের সম্পর্কে'],
		'posting-rules' => ['en' => 'Posting rules', 'fr' => 'Règles de publication', 'es' => 'Normas de publicación', 'de' => 'Regeln für Anzeigen', 'it' => 'Regole di pubblicazione', 'ru' => 'Правила размещения', 'ar' => 'قواعد النشر', 'hi' => 'पोस्टिंग नियम', 'bn' => 'পোস্টিংয়ের নিয়ম'],
		'cookie-policy' => ['en' => 'Cookie policy', 'fr' => 'Politique de cookies', 'es' => 'Política de cookies', 'de' => 'Cookie-Richtlinie', 'it' => 'Informativa sui cookie', 'ru' => 'Политика cookie', 'ar' => 'سياسة ملفات تعريف الارتباط', 'hi' => 'कुकी नीति', 'bn' => 'কুকি নীতি'],
	];

	/** A page already linked in the footer: its column receives the new links. */
	private const SIBLING_ROUTE = 'page.privacy';

	public function up(): void
	{
		if (!Schema::hasTable('menu_items')) {
			return;
		}

		$sibling = DB::table('menu_items')->where('route_name', self::SIBLING_ROUTE)->first(['menu_id', 'parent_id', 'depth']);
		$parent = $sibling ? DB::table('menu_items')->where('id', $sibling->parent_id)->first(['id', 'rgt']) : null;
		if (!$parent) {
			return; // the footer was rebuilt by the admin: add the links in Admin > Menus
		}

		$links = array_filter(self::LINKS, fn ($labels, $slug) => !DB::table('menu_items')->where('route_name', 'page.' . $slug)->exists(), ARRAY_FILTER_USE_BOTH);
		if (empty($links)) {
			return;
		}

		DB::transaction(function () use ($links, $parent, $sibling) {
			$gapStart = (int)$parent->rgt; // new items go before the parent's right edge
			$width = count($links) * 2;
			DB::table('menu_items')->where('rgt', '>=', $gapStart)->increment('rgt', $width);
			DB::table('menu_items')->where('lft', '>', $gapStart)->increment('lft', $width);

			$lft = $gapStart;
			foreach ($links as $slug => $labels) {
				DB::table('menu_items')->insert([
					'menu_id'    => $sibling->menu_id,
					'parent_id'  => $parent->id,
					'type'       => 'link',
					'label'      => json_encode($labels, JSON_UNESCAPED_UNICODE),
					'url_type'   => 'route',
					'route_name' => 'page.' . $slug,
					'lft'        => $lft,
					'rgt'        => $lft + 1,
					'depth'      => $sibling->depth,
					'active'     => 1,
					'created_at' => now(),
					'updated_at' => now(),
				]);
				$lft += 2;
			}
		});

		$this->forgetCache();
	}

	public function down(): void
	{
		if (!Schema::hasTable('menu_items')) {
			return;
		}

		DB::transaction(function () {
			$items = DB::table('menu_items')
				->whereIn('route_name', array_map(fn ($slug) => 'page.' . $slug, array_keys(self::LINKS)))
				->orderByDesc('lft')
				->get(['id', 'lft']);

			foreach ($items as $item) {
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
