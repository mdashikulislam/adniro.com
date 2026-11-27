<?php

use App\Models\CategoryField;
use App\Models\Field;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * No field of the listing form's "Additional Details" section is required any more (adniro, asked by the
 * owner on 2026-11-27): "Required" is switched off on every listing field. The switch stays in
 * Admin > Custom fields, so one can be made required again there.
 *
 * Written with the query builder (no model events), then the cached field lists are dropped.
 * Not reversed: which fields were required is not kept. The backup taken before the deploy has it.
 */
return new class extends Migration
{
	private const LISTING_FIELDS = 'post';
	
	public function up(): void
	{
		if (!Schema::hasTable('fields')) {
			return;
		}
		
		$changed = DB::table('fields')
			->where('belongs_to', self::LISTING_FIELDS)
			->where('required', 1)
			->update(['required' => 0]);
		
		if ($changed === 0) {
			return;
		}
		
		try {
			caching()->invalidateMultiple([Field::class, CategoryField::class]);
		} catch (Throwable $e) {
			// The cache store may be unreachable during a deploy; the lists expire with the cache TTL.
			logger()->warning('Field cache not invalidated after migration: ' . $e->getMessage());
		}
	}
	
	public function down(): void
	{
		// Nothing to put back: see above.
	}
};
