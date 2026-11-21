<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Listings List > "Grid View Columns" now sets the search page's listings per row (adniro). Until now the
 * search page always showed 3 per row, whatever this setting said, so it is set to 3 to keep the page as
 * it looks today. The homepage sections have their own "Items per row" (4 when not set).
 *
 * The row is written with the query builder: saving Setting models runs the setting observers.
 */
return new class extends Migration
{
	private const SEARCH_PAGE_COLUMNS = '3';
	private const SHIPPED_COLUMNS = '4';

	public function up(): void
	{
		$this->setColumns(self::SEARCH_PAGE_COLUMNS);
	}

	public function down(): void
	{
		$this->setColumns(self::SHIPPED_COLUMNS);
	}

	private function setColumns(string $columns): void
	{
		if (!Schema::hasTable('settings')) {
			return;
		}

		$row = DB::table('settings')->where('name', 'listings_list')->first(['id', 'field_values']);
		if (empty($row)) {
			return; // fresh installs create the row with their own defaults
		}

		$current = json_decode((string)$row->field_values, true);
		$current = is_array($current) ? $current : [];

		DB::table('settings')
			->where('id', $row->id)
			->update(['field_values' => json_encode(array_merge($current, ['grid_view_cols' => $columns]))]);

		try {
			caching()->invalidateMultiple([Setting::class]);
		} catch (Throwable $e) {
			// The cache store may be unreachable during a deploy; the value expires with the cache TTL.
			logger()->warning('Settings cache not invalidated after migration: ' . $e->getMessage());
		}
	}
};
