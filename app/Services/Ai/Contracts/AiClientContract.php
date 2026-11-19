<?php

namespace App\Services\Ai\Contracts;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;

/*
 * The only AI entry point for feature code (adniro:ai).
 */

interface AiClientContract
{
	/**
	 * Is AI switched on and does the feature have at least one usable model?
	 */
	public function enabled(string $feature): bool;
	
	/**
	 * Run one structured request with the feature's model, falling back once on errors.
	 */
	public function structured(AiRequest $request): AiResult;
	
	/**
	 * Send a small test request to a model and store the outcome on the model.
	 */
	public function testModel(AiModel $model): AiResult;
	
	/**
	 * Test a provider by testing its first active model, and store the outcome on the provider.
	 */
	public function testProvider(AiProvider $provider): AiResult;
}
