<?php

namespace App\Console\Commands;

use App\Helpers\Services\ListingCounts;
use App\Models\Country;
use Illuminate\Console\Command;
use Throwable;

/**
 * Rebuild the per-country listings counts & last changes (see ListingCounts),
 * so that pages and sitemaps (and the crawlers fetching them) don't have to.
 */
class ListingsWarmCounts extends Command
{
	protected $signature = 'listings:warm-counts {country? : A country code (all the active countries by default)}';

	protected $description = 'Rebuild the cached listings counts & last changes used by the sitemaps, the sidebar and the noindex rules.';

	public function handle(): int
	{
		$countryCodes = !empty($this->argument('country'))
			? [strtoupper((string)$this->argument('country'))]
			: Country::query()->active()->pluck('code')->all();

		foreach ($countryCodes as $countryCode) {
			$startedAt = microtime(true);
			try {
				ListingCounts::refresh($countryCode);
				$this->info(sprintf('%s: done in %.2fs', $countryCode, microtime(true) - $startedAt));
			} catch (Throwable $e) {
				$this->error($countryCode . ': ' . $e->getMessage());
			}
		}

		return self::SUCCESS;
	}
}
