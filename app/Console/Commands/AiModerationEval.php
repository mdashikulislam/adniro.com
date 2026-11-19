<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\Moderation\ListingPayload;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\Policy;
use App\Services\Ai\Moderation\VerdictMapper;
use Illuminate\Console\Command;

/*
 * php artisan ai:moderation-eval {model?} (adniro:ai)
 *
 * Runs the labelled listings in resources/ai/eval/moderation.json through a model with the real
 * moderation prompt and rules, and reports how it did. Each case is one real AI call.
 *
 * Release gate: no violating listing approved, and at most 5% of clean listings held or rejected.
 * A model that passes gets "moderation_eval_passed_at" set; one that fails has it cleared.
 */

class AiModerationEval extends Command
{
	public const MAX_CLEAN_HELD_RATE = 0.05;
	
	protected $signature = 'ai:moderation-eval
		{model? : An AI model, by its database id or its model ID. Defaults to the assigned moderation model}
		{--only= : Run only the cases whose id starts with this text}
		{--limit= : Run at most this many cases}';
	
	protected $description = 'Check a model against the labelled moderation listings (adniro:ai). Makes real AI calls.';
	
	public function handle(AiClientContract $client, AiSettings $settings): int
	{
		$model = $this->resolveModel($settings);
		if (!$model) {
			$this->warn('No model found. Pass a model, or assign a moderation model in Admin > Settings > AI.');
			
			return self::FAILURE;
		}
		
		$cases = $this->cases();
		if (empty($cases)) {
			$this->warn('No evaluation cases found.');
			
			return self::FAILURE;
		}
		
		$fullRun = !$this->option('only') && !$this->option('limit');
		$this->info('Evaluating ' . $model->fullName() . ' on ' . count($cases) . ' listings. Each one is a real AI call.');
		
		$rows = [];
		$stats = ['clean' => 0, 'clean_held' => 0, 'bad' => 0, 'bad_approved' => 0, 'no_answer' => 0, 'cost' => 0.0, 'ms' => 0];
		
		foreach ($cases as $case) {
			$result = $client->structured(new AiRequest(
				feature: ModerationService::FEATURE,
				instructions: Policy::instructions(),
				prompt: ListingPayload::prompt($case['listing'], $case['lang'] ?? 'en', 0),
				schema: Policy::schema(),
				maxTokens: ModerationService::MAX_OUTPUT_TOKENS,
				subjectType: 'moderation_eval',
				subjectId: $case['id'],
				model: $model,
			));
			$mapped = VerdictMapper::map($result);
			
			$stats['cost'] += (float)$result->costUsd;
			$stats['ms'] += $result->latencyMs;
			if (!$result->ok()) {
				$stats['no_answer']++;
			}
			
			$expectAllow = ($case['expect'] ?? '') === 'allow';
			if ($expectAllow) {
				$stats['clean']++;
				$pass = $mapped->isApproved();
				$stats['clean_held'] += $pass ? 0 : 1;
			} else {
				$stats['bad']++;
				$pass = !$mapped->isApproved();
				$stats['bad_approved'] += $pass ? 0 : 1;
			}
			
			$rows[] = [
				$case['id'],
				$expectAllow ? 'allow' : 'block (' . ($case['category'] ?? '?') . ')',
				$mapped->status . ($mapped->categories ? ' [' . implode(', ', $mapped->categories) . ']' : ''),
				$mapped->confidence ?? $result->status,
				$pass ? 'pass' : 'FAIL',
			];
			$this->output->write($pass ? '.' : 'F');
		}
		$this->newLine(2);
		
		$this->table(['Case', 'Expected', 'Decided', 'Confidence', 'Result'], $rows);
		
		$heldRate = $stats['clean'] > 0 ? $stats['clean_held'] / $stats['clean'] : 0;
		$passed = $stats['bad_approved'] === 0 && $heldRate <= self::MAX_CLEAN_HELD_RATE;
		
		$this->table(['Measure', 'Value'], [
			['Violating listings approved', $stats['bad_approved'] . ' of ' . $stats['bad'] . ' (must be 0)'],
			['Clean listings held or rejected', $stats['clean_held'] . ' of ' . $stats['clean'] . ' (' . round($heldRate * 100, 1) . '%, at most 5%)'],
			['Calls without a usable answer', $stats['no_answer']],
			['Average time', count($cases) ? round($stats['ms'] / count($cases)) . ' ms' : '-'],
			['Total cost', '$' . number_format($stats['cost'], 4)],
		]);
		
		if ($fullRun) {
			$model->forceFill(['moderation_eval_passed_at' => $passed ? now() : null])->save();
		} else {
			$this->line('Partial run: the model\'s evaluation result was not changed.');
		}
		
		$passed ? $this->info('PASSED') : $this->error('FAILED');
		
		return $passed ? self::SUCCESS : self::FAILURE;
	}
	
	private function resolveModel(AiSettings $settings): ?AiModel
	{
		$argument = $this->argument('model');
		if ($argument) {
			return AiModel::query()->with('provider')
				->where(fn ($q) => $q->where('model_id', $argument)->orWhere('id', ctype_digit((string)$argument) ? (int)$argument : 0))
				->first();
		}
		
		$id = $settings->modelIdsFor(ModerationService::FEATURE)[0] ?? null;
		
		return $id ? AiModel::query()->with('provider')->find($id) : null;
	}
	
	private function cases(): array
	{
		$data = json_decode((string)file_get_contents(resource_path('ai/eval/moderation.json')), true);
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
