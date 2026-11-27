<?php

namespace App\Console\Commands;

use App\Services\ListingCleanUp\CleanUpRules;
use App\Services\ListingCleanUp\ListingCleanUp;
use Illuminate\Console\Command;

/*
 * php artisan listings:clean-up (adniro)
 *
 * Lists the live listings that break a clean-up rule: prohibited content, crypto and exchange
 * software adverts, "price in <place>" templates and listings filed under the wrong country, and
 * the older copies of a repeated listing. With --apply it hides them: each gets the moderation
 * status "rejected" and a recorded decision, nothing is deleted, and the owners are not emailed.
 * A hidden listing can be approved again in Admin > AI > Moderation.
 *
 *   php artisan listings:clean-up                          list only
 *   php artisan listings:clean-up --rule=prohibited        one rule (the option can be repeated)
 *   php artisan listings:clean-up --except=12,34 --apply   hide all but listings 12 and 34
 */

class ListingsCleanUp extends Command
{
	private const RULE_PREFIX = 'cleanup_';
	private const TITLE_LENGTH = 90;
	
	protected $signature = 'listings:clean-up
		{--apply : Hide the listings (without it, they are only listed)}
		{--rule=* : Only these rules: prohibited, crypto_software, price_template, wrong_country, duplicate}
		{--except= : Listing numbers to leave alone, separated by commas}';
	
	protected $description = 'List, or hide with --apply, the live listings that break a clean-up rule (adniro).';
	
	public function handle(ListingCleanUp $cleanUp): int
	{
		$rules = $this->rules();
		if (is_null($rules)) {
			return self::FAILURE;
		}
		
		$plan = $cleanUp->plan($rules, $this->exceptIds());
		if (empty($plan)) {
			$this->info('No listing matches the clean-up rules.');
			
			return self::SUCCESS;
		}
		
		foreach ($plan as $item) {
			$this->line(sprintf(
				'%s | %d | %s | %s',
				$this->shortName($item['rule']),
				$item['id'],
				$item['country'],
				mb_substr($item['title'], 0, self::TITLE_LENGTH)
			));
		}
		$this->newLine();
		foreach (array_count_values(array_column($plan, 'rule')) as $rule => $count) {
			$this->line($this->shortName($rule) . ': ' . $count);
		}
		$this->line('Total: ' . count($plan));
		
		if (!$this->option('apply')) {
			$this->comment('Nothing was changed. Add --apply to hide these listings.');
			
			return self::SUCCESS;
		}
		
		$result = $cleanUp->apply($plan);
		$this->info("Hidden: {$result['hidden']}. Skipped (changed meanwhile): {$result['skipped']}.");
		
		if ($result['failed'] > 0) {
			$this->error("Failed: {$result['failed']} (see the log).");
			
			return self::FAILURE;
		}
		
		return self::SUCCESS;
	}
	
	/**
	 * The rules asked for, as stored names; an empty list means all of them. Null when one is unknown.
	 *
	 * @return string[]|null
	 */
	private function rules(): ?array
	{
		$rules = array_map(fn ($name) => self::RULE_PREFIX . $name, (array)$this->option('rule'));
		
		$unknown = array_diff($rules, CleanUpRules::ALL);
		if (!empty($unknown)) {
			$known = implode(', ', array_map(fn ($rule) => $this->shortName($rule), CleanUpRules::ALL));
			$this->error('Unknown rule: ' . implode(', ', array_map(fn ($rule) => $this->shortName($rule), $unknown)) . '. Use: ' . $known . '.');
			
			return null;
		}
		
		return array_values($rules);
	}
	
	/**
	 * @return int[]
	 */
	private function exceptIds(): array
	{
		$ids = array_map('intval', explode(',', (string)$this->option('except')));
		
		return array_values(array_filter($ids, fn ($id) => $id > 0));
	}
	
	private function shortName(string $rule): string
	{
		return str_starts_with($rule, self::RULE_PREFIX) ? substr($rule, strlen(self::RULE_PREFIX)) : $rule;
	}
}
