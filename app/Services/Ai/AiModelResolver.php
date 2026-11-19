<?php

namespace App\Services\Ai;

use App\Models\AiModel;

/*
 * Finds the models to use for a feature (adniro:ai): the admin's primary choice, then the fallback.
 * A model that is switched off, lost a capability or failed its last test is skipped.
 */

class AiModelResolver
{
	public function __construct(private readonly AiSettings $settings) {}
	
	/**
	 * @return AiModel[] Usable models, primary first
	 */
	public function candidatesFor(string $feature): array
	{
		$ids = $this->settings->modelIdsFor($feature);
		if (empty($ids)) {
			return [];
		}
		
		$models = AiModel::query()->with('provider')->whereIn('id', $ids)->get()->keyBy('id');
		
		$candidates = [];
		foreach ($ids as $id) {
			$model = $models->get($id);
			if ($model instanceof AiModel && $model->isEligibleFor($feature)) {
				$candidates[] = $model;
			}
		}
		
		return $candidates;
	}
}
