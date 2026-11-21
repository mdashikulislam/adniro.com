<?php

use App\Models\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * "Recently viewed" becomes a homepage section (adniro): listed in Admin > Homepage > Sections, ordered
 * with the others, with its own options (App\Models\Section\Home\RecentlyViewedSection). It is put
 * where the homepage showed it until now: just before the latest listings (else last).
 *
 * The sections are a flat nested set (lft/rgt); the ones after the new section move down by 2.
 * Rows are written with the query builder, so no model observer runs.
 */
return new class extends Migration
{
	private const NAME = 'recently_viewed';
	private const BEFORE = 'latest_listings';
	private const NODE_WIDTH = 2;

	public function up(): void
	{
		if (!Schema::hasTable('sections') || DB::table('sections')->where('name', self::NAME)->exists()) {
			return;
		}

		DB::transaction(function () {
			$lft = $this->insertPosition();
			$this->shiftFrom($lft, self::NODE_WIDTH);

			DB::table('sections')->insert([
				'belongs_to'   => 'home',
				'name'         => self::NAME,
				'label'        => 'Recently Viewed',
				'field_values' => null, // the defaults: App\Models\HasSettings\Presets\Section\RecentlyViewedPreset
				'description'  => 'Recently Viewed Listings Section',
				'parent_id'    => null,
				'lft'          => $lft,
				'rgt'          => $lft + 1,
				'depth'        => 0,
				'active'       => 1,
				'created_at'   => now(),
				'updated_at'   => now(),
			]);
		});

		$this->invalidateCache();
	}

	public function down(): void
	{
		if (!Schema::hasTable('sections')) {
			return;
		}

		$section = DB::table('sections')->where('name', self::NAME)->first(['id', 'lft']);
		if (empty($section)) {
			return;
		}

		DB::transaction(function () use ($section) {
			DB::table('sections')->where('id', $section->id)->delete();
			$this->shiftFrom((int)$section->lft, -self::NODE_WIDTH);
		});

		$this->invalidateCache();
	}

	/** Where the latest listings section starts, or after the last section */
	private function insertPosition(): int
	{
		$beforeLft = DB::table('sections')->where('name', self::BEFORE)->value('lft');
		if (!is_null($beforeLft)) {
			return (int)$beforeLft;
		}

		return (int)DB::table('sections')->max('rgt') + 1;
	}

	private function shiftFrom(int $lft, int $by): void
	{
		DB::table('sections')->where('lft', '>=', $lft)->update(['lft' => DB::raw('lft + ' . $by)]);
		DB::table('sections')->where('rgt', '>=', $lft)->update(['rgt' => DB::raw('rgt + ' . $by)]);
	}

	private function invalidateCache(): void
	{
		try {
			caching()->invalidateMultiple([Section::class]);
		} catch (Throwable $e) {
			// The cache store may be unreachable during a deploy; the sections expire with the cache TTL.
			logger()->warning('Sections cache not invalidated after migration: ' . $e->getMessage());
		}
	}
};
