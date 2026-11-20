<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\Post;
use App\Models\PostModeration;
use App\Models\PostDraft;
use Illuminate\Support\Facades\Schema;
use Throwable;

/*
 * Figures for the AI spend widget on the admin dashboard (adniro:ai).
 */

class AiSpend
{
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
			
			$byFeature = AiUsageLog::query()
				->where('created_at', '>=', $today)
				->groupBy('feature')
				->selectRaw('feature, count(*) as calls, sum(cost_usd) as cost, sum(case when status = ? then 1 else 0 end) as failed', [AiResult::ERROR])
				->get()
				->keyBy('feature');
			
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
				'month_cost'    => (float)AiUsageLog::query()->where('created_at', '>=', $month)->sum('cost_usd'),
				'budget'        => $budget,
				'budget_used'   => $budget > 0 ? min(100, round($todayCost / $budget * 100)) : null,
				'call_limit'    => $this->settings->dailyCallLimit(),
				'features'      => $byFeature->map(fn ($row) => ['calls' => (int)$row->calls, 'cost' => (float)$row->cost, 'failed' => (int)$row->failed])->all(),
				'days'          => $days,
				'needs_review'  => Post::query()->withoutGlobalScopes()->where('moderation_status', 'needs_review')->count(),
				'rejected_today' => PostModeration::query()->where('applied_status', 'rejected')->where('created_at', '>=', $today)->count(),
				'drafts_today'  => Schema::hasTable('post_drafts') ? PostDraft::query()->where('counted', true)->where('created_at', '>=', $today)->count() : 0,
			];
		} catch (Throwable $e) {
			return null;
		}
	}
}
