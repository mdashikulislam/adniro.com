<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Category;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\ListingAssistant\DraftPrompt;
use App\Services\ListingAssistant\DraftSanitizer;
use App\Services\ListingAssistant\DraftSchema;
use App\Services\ListingAssistant\ListingAssistantService;
use Illuminate\Console\Command;
use Throwable;

/*
 * php artisan ai:listing-assistant-eval {model?} (adniro:ai).
 *
 * Runs the inputs in resources/ai/eval/listing-assistant.json through a model with the real drafting
 * prompt, catalog and sanitizer, and reports category accuracy, invented prices and whether
 * non-listings are recognised. Each case is one real AI call.
 *
 * Gate: at least 80% right categories, no invented price, every non-listing recognised and at
 * most one real listing wrongly refused. A model that passes gets "listing_assistant_eval_passed_at" set.
 */

class AiListingAssistantEval extends Command
{
	public const MIN_CATEGORY_ACCURACY = 0.8;
	
	protected $signature = 'ai:listing-assistant-eval
		{model? : An AI model, by its database id or its model ID. Defaults to the assigned AI listing assistant model}
		{--only= : Run only the cases whose id starts with this text}
		{--limit= : Run at most this many cases}';
	
	protected $description = 'Check a model against the AI listing assistant evaluation inputs (adniro:ai). Makes real AI calls.';
	
	public function handle(AiClientContract $client, AiSettings $settings, DraftPrompt $prompt, DraftSanitizer $sanitizer): int
	{
		$model = $this->resolveModel($settings);
		if (!$model) {
			$this->warn('No model found. Pass a model, or assign an AI listing assistant model in Admin > Settings > AI > Models.');
			
			return self::FAILURE;
		}
		
		$cases = $this->cases();
		if (empty($cases)) {
			$this->warn('No evaluation cases found.');
			
			return self::FAILURE;
		}
		
		$fullRun = !$this->option('only') && !$this->option('limit');
		$this->info('Evaluating ' . $model->fullName() . ' on ' . count($cases) . ' inputs. Each one is a real AI call.');
		
		$instructions = $prompt->instructions();
		$currency = (string)(config('currency.code') ?: 'USD');
		$stats = ['listings' => 0, 'right_category' => 0, 'invented_price' => 0, 'wrong_price' => 0, 'non_listings' => 0, 'missed_non_listing' => 0, 'refused_listing' => 0, 'no_answer' => 0, 'cost' => 0.0, 'ms' => 0];
		$rows = [];
		$firstErrors = [];
		
		foreach ($cases as $index => $case) {
			$expectListing = !empty($case['categories']);
			$result = $client->structured(new AiRequest(
				feature: ListingAssistantService::FEATURE,
				instructions: $instructions,
				prompt: $prompt->prompt((string)$case['text'], (string)($case['lang'] ?? 'en'), (string)config('country.name'), $currency),
				schema: DraftSchema::schema(),
				maxTokens: (int)config('ai_features.listing_assistant.max_tokens', 3000),
				subjectType: 'listing_assistant_eval',
				subjectId: $case['id'],
				model: $model,
			));
			$stats['cost'] += (float)$result->costUsd;
			$stats['ms'] += $result->latencyMs;
			
			$notes = [];
			$pass = true;
			$categoryName = '-';
			
			if (!$result->ok()) {
				$stats['no_answer']++;
				$pass = false;
				$notes[] = AiModerationEval::reason($result);
			} else {
				$clean = $sanitizer->sanitize((array)$result->data, $currency);
				$listable = $clean['listable'] && !empty($clean['values']['title'] ?? null);
				
				if ($expectListing) {
					$stats['listings']++;
					if (!$listable) {
						$stats['refused_listing']++;
						$pass = false;
						$notes[] = 'refused';
					} else {
						$categoryName = $this->leafName($clean['values']['category_id'] ?? null);
						$expected = array_map('mb_strtolower', (array)$case['categories']);
						if (in_array(mb_strtolower($categoryName), $expected, true)) {
							$stats['right_category']++;
						} else {
							$pass = false;
							$notes[] = 'category';
						}
						
						$price = $clean['values']['price'] ?? null;
						if (is_null($case['price']) && !is_null($price)) {
							$stats['invented_price']++;
							$pass = false;
							$notes[] = 'invented price ' . $price;
						} else if (!is_null($case['price']) && (is_null($price) || abs((float)$price - (float)$case['price']) > max(1, 0.01 * (float)$case['price']))) {
							$stats['wrong_price']++;
							$notes[] = 'price ' . ($price ?? 'none');
						}
					}
				} else {
					$stats['non_listings']++;
					if ($listable) {
						$stats['missed_non_listing']++;
						$pass = false;
						$notes[] = 'drafted a non-listing';
					}
				}
			}
			
			$rows[] = [$case['id'], $expectListing ? implode(' / ', $case['categories']) : 'not a listing', $categoryName, implode(', ', $notes), $pass ? 'pass' : 'FAIL'];
			$this->output->write($pass ? '.' : 'F');
			
			// The first calls all failed the same way: stop instead of repeating it for every case
			if ($index < 3) {
				$firstErrors[] = $result->ok() ? null : AiModerationEval::reason($result);
				if ($index === 2 && count(array_unique($firstErrors)) === 1 && !is_null($firstErrors[0])) {
					$this->newLine(2);
					$this->error('Stopped: the first 3 calls failed with "' . $firstErrors[0] . '". Fix this, then run the evaluation again. The model\'s result was not changed.');
					
					return self::FAILURE;
				}
			}
		}
		$this->newLine(2);
		
		$this->table(['Case', 'Expected', 'Category', 'Notes', 'Result'], $rows);
		
		$accuracy = $stats['listings'] > 0 ? $stats['right_category'] / $stats['listings'] : 0;
		$passed = $accuracy >= self::MIN_CATEGORY_ACCURACY
			&& $stats['invented_price'] === 0
			&& $stats['missed_non_listing'] === 0
			&& $stats['refused_listing'] <= 1
			&& $stats['no_answer'] === 0;
		
		$this->table(['Measure', 'Value'], [
			['Right category', $stats['right_category'] . ' of ' . $stats['listings'] . ' (' . round($accuracy * 100, 1) . '%, at least 80%)'],
			['Invented prices', $stats['invented_price'] . ' (must be 0)'],
			['Stated price read wrongly', $stats['wrong_price'] . ' (reported only)'],
			['Non-listings drafted anyway', $stats['missed_non_listing'] . ' of ' . $stats['non_listings'] . ' (must be 0)'],
			['Real listings refused', $stats['refused_listing'] . ' (at most 1)'],
			['Failed calls', $stats['no_answer'] . ' (must be 0)'],
			['Average time', round($stats['ms'] / max(1, count($cases))) . ' ms'],
			['Total cost', '$' . number_format($stats['cost'], 4)],
		]);
		
		if ($fullRun) {
			$model->forceFill(['listing_assistant_eval_passed_at' => $passed ? now() : null])->save();
		} else {
			$this->line('Partial run: the model\'s evaluation result was not changed.');
		}
		
		$passed ? $this->info('PASSED') : $this->error('FAILED');
		
		return $passed ? self::SUCCESS : self::FAILURE;
	}
	
	private function leafName($categoryId): string
	{
		if (empty($categoryId)) {
			return '-';
		}
		
		try {
			$category = Category::query()->withoutGlobalScopes()->find($categoryId);
			
			return $category ? (string)$category->getTranslation('name', 'en', true) : '-';
		} catch (Throwable $e) {
			return '-';
		}
	}
	
	private function resolveModel(AiSettings $settings): ?AiModel
	{
		$argument = $this->argument('model');
		if ($argument) {
			return AiModel::query()->with('provider')
				->where(fn ($q) => $q->where('model_id', $argument)->orWhere('id', ctype_digit((string)$argument) ? (int)$argument : 0))
				->first();
		}
		
		$id = $settings->modelIdsFor(ListingAssistantService::FEATURE)[0] ?? null;
		
		return $id ? AiModel::query()->with('provider')->find($id) : null;
	}
	
	private function cases(): array
	{
		$data = json_decode((string)file_get_contents(resource_path('ai/eval/listing-assistant.json')), true);
		$cases = (array)($data['cases'] ?? []);
		
		if ($only = $this->option('only')) {
			$cases = array_values(array_filter($cases, fn ($case) => str_starts_with((string)$case['id'], $only)));
		}
		if ($limit = (int)$this->option('limit')) {
			$cases = array_slice($cases, 0, $limit);
		}
		
		return $cases;
	}
}
