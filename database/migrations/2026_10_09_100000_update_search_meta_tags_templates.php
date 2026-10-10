<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Better default title & description templates for the category & tag results pages:
 * - with the country name (the same category page exists in every country, they all had the same title),
 * - without "for Sale" (wrong for services, jobs, events...).
 * A template is only updated when it's still the former default text (in English),
 * so the templates edited from the admin panel are kept as they are.
 */
return new class extends Migration
{
	private array $changes = [
		'searchCategory' => [
			'title'       => [
				'old' => '{category.name} for Sale - Buy & Sell {category.name} | {app.name}',
				'new' => '{category.name} in {country.name} - Classified Ads | {app.name}',
			],
			'description' => [
				'old' => 'Find great deals on {category.name} on {app.name}. Browse thousands of new and used {category.name} from local sellers. {category.description}',
				'new' => 'Find {category.name} in {country.name} on {app.name}: browse the latest local listings or post a free ad. {category.description}',
			],
		],
		'searchTag'      => [
			'title' => [
				'old' => '{tag} for Sale - Buy & Sell {tag} - {app.name}',
				'new' => '{tag} in {country.name} - Classified Ads | {app.name}',
			],
		],
	];

	public function up(): void
	{
		$this->apply('old', 'new');
	}

	public function down(): void
	{
		$this->apply('new', 'old');
	}

	private function apply(string $from, string $to): void
	{
		foreach ($this->changes as $page => $columns) {
			$metaTag = DB::table('meta_tags')->where('page', $page)->first();
			if (empty($metaTag)) {
				continue;
			}

			$values = [];
			foreach ($columns as $column => $texts) {
				$translations = json_decode((string)($metaTag->{$column} ?? ''), true);
				if (!is_array($translations) || ($translations['en'] ?? null) !== $texts[$from]) {
					continue;
				}
				$translations['en'] = $texts[$to];
				$values[$column] = json_encode($translations, JSON_UNESCAPED_UNICODE);
			}

			if (!empty($values)) {
				DB::table('meta_tags')->where('id', $metaTag->id)->update($values);
			}
		}
	}
};
