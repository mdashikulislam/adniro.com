<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\Post;
use App\Models\PostModeration;
use App\Models\PostDraft;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/*
 * Figures for the AI spend widget on the admin dashboard (adniro:ai).
 */

class AiSpend
{
	/** Calls made by the "Test model" button and the evaluation commands */
	private const TEST_FEATURE = 'test';
	
	private const NO_USAGE = ['calls' => 0, 'cost' => 0.0];
	
	public function __construct(private readonly AiSettings $settings)
	{
	}
	
	/**
	 * @return array<string, mixed>|null Null when the AI tables do not exist yet
	 */
	public function summary(): ?array
	{
		try {
			if (!Schema::hasTable('ai_usage_logs')) {
				return null;
			}
			
			$today = now()->startOfDay();
			$month = now()->startOfMonth();
			
			$byFeature = $this->totalsByFeature($today);
			$monthByFeature = $this->totalsByFeature($month);
			
			$todayCost = (float)$byFeature->sum('cost');
			$budget = $this->settings->dailyBudgetUsd();
			
			$days = [];
			$rows = AiUsageLog::query()
				->where('created_at', '>=', now()->subDays(6)->startOfDay())
				->selectRaw('date(created_at) as day, sum(cost_usd) as cost, count(*) as calls')
				->groupBy('day')
				->get()
				->keyBy('day');
			for ($i = 6; $i >= 0; $i--) {
				$day = now()->subDays($i)->toDateString();
				$days[] = ['day' => $day, 'cost' => (float)($rows[$day]->cost ?? 0), 'calls' => (int)($rows[$day]->calls ?? 0)];
			}
			
			return [
				'enabled'       => $this->settings->enabled(),
				'today_cost'    => $todayCost,
				'today_calls'   => (int)$byFeature->sum('calls'),
				'today_failed'  => (int)$byFeature->sum('failed'),
				'month_cost'    => (float)$monthByFeature->sum('cost'),
				'budget'        => $budget,
				'budget_used'   => $budget > 0 ? min(100, round($todayCost / $budget * 100)) : null,
				'call_limit'    => $this->settings->dailyCallLimit(),
				'features'      => $byFeature->all(),
				'feature_rows'  => $this->featureRows($byFeature, $monthByFeature),
				'days'          => $days,
				'needs_review'  => Post::query()->withoutGlobalScopes()->where('moderation_status', 'needs_review')->count(),
				'rejected_today' => PostModeration::query()->where('applied_status', 'rejected')->where('created_at', '>=', $today)->count(),
				'drafts_today'  => Schema::hasTable('post_drafts') ? PostDraft::query()->where('counted', true)->where('created_at', '>=', $today)->count() : 0,
			];
		} catch (Throwable $e) {
			return null;
		}
	}
	
	/**
	 * Calls, cost and failures of each feature since the given moment.
	 *
	 * @return \Illuminate\Support\Collection<string, array{calls: int, cost: float, failed: int}>
	 */
	private function totalsByFeature(CarbonInterface $since): Collection
	{
		return AiUsageLog::query()
			->where('created_at', '>=', $since)
			->groupBy('feature')
			->selectRaw('feature, count(*) as calls, sum(cost_usd) as cost, sum(case when status = ? then 1 else 0 end) as failed', [AiResult::ERROR])
			->get()
			->keyBy('feature')
			->map(fn ($row) => ['calls' => (int)$row->calls, 'cost' => (float)$row->cost, 'failed' => (int)$row->failed]);
	}
	
	/**
	 * One row per kind of AI call: every feature (config/ai_features.php), the model tests, then anything else
	 * this month's log holds, so no cost is left out of the widget.
	 *
	 * @return array<int, array{feature: string, label: string, today: array, month: array}>
	 */
	private function featureRows(Collection $today, Collection $month): array
	{
		$features = collect(array_keys((array)config('ai_features.features')))
			->push(self::TEST_FEATURE)
			->merge($month->keys())
			->unique()
			->values();
		
		return $features->map(fn (string $feature) => [
			'feature' => $feature,
			'label'   => Lang::has('ai.spend_feature_' . $feature) ? trans('ai.spend_feature_' . $feature) : Str::headline($feature),
			'today'   => $this->usage($today->get($feature)),
			'month'   => $this->usage($month->get($feature)),
		])->all();
	}
	
	/**
	 * @return array{calls: int, cost: float}
	 */
	private function usage(?array $totals): array
	{
		return empty($totals) ? self::NO_USAGE : ['calls' => $totals['calls'], 'cost' => $totals['cost']];
	}
}
