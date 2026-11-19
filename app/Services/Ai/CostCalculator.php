<?php

namespace App\Services\Ai;

use App\Models\AiModel;

/*
 * Cost of one call from its token usage and the prices the admin entered (adniro:ai).
 *
 * All input tokens are priced at the input rate. Providers bill cached tokens less,
 * so this slightly overestimates spend when prompt caching works, which is the safe side for a budget.
 */

class CostCalculator
{
	public static function cost(AiModel $model, int $inputTokens, int $outputTokens): ?float
	{
		if (!$model->hasPrices()) {
			return null;
		}
		
		$cost = ($inputTokens / 1_000_000) * (float)$model->input_price_per_mtok
			+ ($outputTokens / 1_000_000) * (float)$model->output_price_per_mtok;
		
		return round($cost, 6);
	}
}
