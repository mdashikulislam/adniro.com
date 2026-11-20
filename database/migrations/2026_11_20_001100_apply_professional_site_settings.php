<?php

use App\Models\Post;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Professional-site defaults (docs/professional-website-plan.md, phase 1).
 *
 * Rows are written with the query builder on purpose: saving Setting models runs the setting
 * observers, and the optimization observer rewrites the /.env file. The settings, sections and
 * listings caches are invalidated afterwards (cached listings carry formatted dates), so the new
 * values apply at once.
 *
 * down() puts back the values this site had before (the LaraClassifier defaults it shipped with).
 */
return new class extends Migration
{
	/** @var array<string, array<string, string>> setting name => [key => new value] */
	private const NEW_VALUES = [
		'security'      => ['csrf_protection' => '1'],
		'header'        => ['default_animation' => '0'],
		'app'           => ['date_format' => 'D MMM YYYY', 'datetime_format' => 'D MMM YYYY, HH:mm'],
		'listings_list' => ['date_from_now' => '1'],
		'listing_page'  => ['date_from_now' => '1'],
		'footer'        => ['hide_powered_by' => '1'],
		'seo'           => ['no_index_listing_report' => '1'],
		'optimization'  => ['webp_format' => '1'],
		'upload'        => ['image_quality' => '85'],
		'mail'          => ['email_verification' => '1'],
		'backup'        => ['taking_backup' => 'daily', 'taking_backup_at' => '03:00'],
	];

	/** @var array<string, array<string, string|null>> */
	private const OLD_VALUES = [
		'security'      => ['csrf_protection' => '0'],
		'header'        => ['default_animation' => '1'],
		'app'           => ['date_format' => 'YYYY-MM-DD', 'datetime_format' => 'YYYY-MM-DD HH:mm'],
		'listings_list' => ['date_from_now' => null],
		'listing_page'  => ['date_from_now' => null],
		'footer'        => ['hide_powered_by' => '0'],
		'seo'           => ['no_index_listing_report' => '0'],
		'optimization'  => ['webp_format' => '0'],
		'upload'        => ['image_quality' => '100'],
		'mail'          => ['email_verification' => '0'],
		'backup'        => ['taking_backup' => 'none', 'taking_backup_at' => null],
	];

	/** Home page sections hidden while the site is new (the stats block shows tiny numbers). */
	private const HIDDEN_SECTIONS = ['stats'];

	public function up(): void
	{
		$this->apply(self::NEW_VALUES, sectionsActive: 0);
	}

	public function down(): void
	{
		$this->apply(self::OLD_VALUES, sectionsActive: 1);
	}

	private function apply(array $valuesBySetting, int $sectionsActive): void
	{
		if (Schema::hasTable('settings')) {
			foreach ($valuesBySetting as $name => $values) {
				$this->mergeFieldValues($name, $values);
			}
		}

		if (Schema::hasTable('sections')) {
			DB::table('sections')->whereIn('name', self::HIDDEN_SECTIONS)->update(['active' => $sectionsActive]);
		}

		$this->invalidateCache();
	}

	private function mergeFieldValues(string $name, array $values): void
	{
		$row = DB::table('settings')->where('name', $name)->first(['id', 'field_values']);
		if (empty($row)) {
			return; // fresh installs create the row with their own defaults
		}

		$current = json_decode((string)$row->field_values, true);
		$current = is_array($current) ? $current : [];

		DB::table('settings')
			->where('id', $row->id)
			->update(['field_values' => json_encode(array_merge($current, $values))]);
	}

	private function invalidateCache(): void
	{
		try {
			caching()->invalidateMultiple([Setting::class, Section::class, Post::class]);
		} catch (Throwable $e) {
			// The cache store may be unreachable during a deploy; the values expire with the cache TTL.
			logger()->warning('Settings cache not invalidated after migration: ' . $e->getMessage());
		}
	}
};
