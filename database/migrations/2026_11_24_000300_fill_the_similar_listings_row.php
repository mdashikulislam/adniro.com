<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * "Similar Listings" under a listing now shows 5 per row, the size of the homepage's cards (adniro). The
 * site took 4, which would leave the row one short, so the limit goes from 4 to 5. A limit somebody set to
 * another number is left alone.
 *
 * The row is written with the query builder: saving Setting models runs the setting observers.
 */
return new class extends Migration
{
	private const KEY = 'similar_posts_limit';
	private const ONE_SHORT = 4;
	private const FULL_ROW = 5;

	public function up(): void
	{
		$this->changeLimit(self::ONE_SHORT, self::FULL_ROW);
	}

	public function down(): void
	{
		$this->changeLimit(self::FULL_ROW, self::ONE_SHORT);
	}

	private function changeLimit(int $from, int $to): void
	{
		if (!Schema::hasTable('settings')) {
			return;
		}

		$row = DB::table('settings')->where('name', 'pagination')->first(['id', 'field_values']);
		if (empty($row)) {
			return; // fresh installs create the row with their own defaults
		}

		$current = json_decode((string)$row->field_values, true);
		$current = is_array($current) ? $current : [];
		if (array_key_exists(self::KEY, $current) && (int)$current[self::KEY] !== $from) {
			return;
		}

		DB::table('settings')
			->where('id', $row->id)
			->update(['field_values' => json_encode(array_merge($current, [self::KEY => $to]))]);

		try {
			caching()->invalidateMultiple([Setting::class]);
		} catch (Throwable $e) {
			// The cache store may be unreachable during a deploy; the value expires with the cache TTL.
			logger()->warning('Settings cache not invalidated after migration: ' . $e->getMessage());
		}
	}
};
