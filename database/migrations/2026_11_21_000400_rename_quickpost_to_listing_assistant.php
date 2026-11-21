<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * "Quick post" became the AI listing assistant, the AI box in the listing form (adniro:ai).
 * Renames what a database created before the rename still stores under the old name: the model
 * evaluation column, the Settings > AI keys and the AI usage log labels. A new database already
 * has the new names, so each step only runs when the old name is there.
 */
return new class extends Migration
{
	private const COLUMN_OLD = 'quickpost_eval_passed_at';
	private const COLUMN_NEW = 'listing_assistant_eval_passed_at';

	/** ai_usage_logs: column => [old value => new value] */
	private const LOG_VALUES = [
		'feature'      => ['quickpost_draft' => 'listing_assistant'],
		'subject_type' => ['quickpost_eval' => 'listing_assistant_eval', 'quickpost_voice' => 'listing_assistant_voice'],
	];

	public function up(): void
	{
		$this->renameColumn(self::COLUMN_OLD, self::COLUMN_NEW);
		$this->renameSettingKeys('quickpost_', 'listing_assistant_');
		$this->renameLogValues(false);
	}

	public function down(): void
	{
		$this->renameColumn(self::COLUMN_NEW, self::COLUMN_OLD);
		$this->renameSettingKeys('listing_assistant_', 'quickpost_');
		$this->renameLogValues(true);
	}

	private function renameColumn(string $from, string $to): void
	{
		if (Schema::hasColumn('ai_models', $from) && !Schema::hasColumn('ai_models', $to)) {
			Schema::table('ai_models', fn ($table) => $table->renameColumn($from, $to));
		}
	}

	private function renameSettingKeys(string $fromPrefix, string $toPrefix): void
	{
		if (!Schema::hasTable('settings')) {
			return;
		}

		$row = DB::table('settings')->where('name', 'ai')->first(['id', 'field_values']);
		$values = $row ? json_decode((string)$row->field_values, true) : null;
		if (!is_array($values)) {
			return;
		}

		$renamed = [];
		foreach ($values as $key => $value) {
			$newKey = str_starts_with($key, $fromPrefix) ? $toPrefix . substr($key, strlen($fromPrefix)) : $key;
			$renamed[$newKey] = $value;
		}

		if ($renamed !== $values) {
			DB::table('settings')->where('id', $row->id)->update(['field_values' => json_encode($renamed)]);
			// The AI client keeps the last readable copy of these settings
			Cache::forget('ai:settings:last_good');
			if (function_exists('caching')) {
				caching()->invalidate(\App\Models\Setting::class);
			}
		}
	}

	private function renameLogValues(bool $reverse): void
	{
		if (!Schema::hasTable('ai_usage_logs')) {
			return;
		}

		foreach (self::LOG_VALUES as $column => $map) {
			foreach ($map as $old => $new) {
				[$from, $to] = $reverse ? [$new, $old] : [$old, $new];
				DB::table('ai_usage_logs')->where($column, $from)->update([$column => $to]);
			}
		}
	}
};
