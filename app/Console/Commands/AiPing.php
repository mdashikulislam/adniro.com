<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use Illuminate\Console\Command;

/*
 * php artisan ai:ping (adniro:ai)
 *
 * Runs the same check as the "Test model" button: one small structured request,
 * with an image when the model understands images. Each run costs a fraction of a cent.
 */

class AiPing extends Command
{
	protected $signature = 'ai:ping
		{model? : An AI model, by its database id or its model ID. Without it, every model assigned to a feature is tested}';
	
	protected $description = 'Send a small test request to AI models and show the result (adniro:ai).';
	
	public function handle(AiClientContract $client, AiSettings $settings): int
	{
		$models = $this->models($settings);
		
		if ($models->isEmpty()) {
			$this->warn($this->argument('model')
				? 'No AI model matches "' . $this->argument('model') . '".'
				: 'No model is assigned to a feature yet. Pass a model, or assign one in Admin > Settings > AI.');
			
			return self::FAILURE;
		}
		
		$rows = [];
		$allOk = true;
		
		foreach ($models as $model) {
			$this->line('Testing ' . $model->fullName() . ' ...');
			$result = $client->testModel($model);
			$allOk = $allOk && $result->ok();
			
			$rows[] = [
				$model->id,
				$model->fullName(),
				$result->ok() ? 'ok' : $client->describe($result),
				$result->latencyMs . ' ms',
				$result->inputTokens . ' / ' . $result->outputTokens,
				$result->cacheReadTokens . ' / ' . $result->cacheWriteTokens,
				is_null($result->costUsd) ? 'no price' : '$' . number_format($result->costUsd, 6),
				$result->ok() ? json_encode($result->data, JSON_UNESCAPED_UNICODE) : '',
			];
		}
		
		$this->table(['ID', 'Model', 'Result', 'Time', 'Tokens in / out', 'Cache read / write', 'Cost', 'Answer'], $rows);
		
		return $allOk ? self::SUCCESS : self::FAILURE;
	}
	
	private function models(AiSettings $settings)
	{
		$query = AiModel::query()->with('provider');
		$argument = $this->argument('model');
		
		if (!empty($argument)) {
			return is_numeric($argument)
				? $query->whereKey((int)$argument)->get()
				: $query->where('model_id', $argument)->get();
		}
		
		$ids = collect(array_keys((array)config('ai_features.features')))
			->flatMap(fn ($feature) => $settings->modelIdsFor($feature))
			->unique()
			->values()
			->all();
		
		return $query->whereIn('id', $ids)->get();
	}
}
