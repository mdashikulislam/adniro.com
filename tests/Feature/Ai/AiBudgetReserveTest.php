<?php

namespace Tests\Feature\Ai;

use App\Models\Setting;
use App\Services\Ai\AiBudget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
 * The last part of the daily AI budget is kept for moderation (adniro:ai).
 */

class AiBudgetReserveTest extends TestCase
{
	use RefreshDatabase;
	
	protected function setUp(): void
	{
		parent::setUp();
		Setting::query()->updateOrCreate(['name' => 'ai'], ['label' => 'AI', 'active' => 1, 'field_values' => [
			'enabled' => '1', 'daily_budget_usd' => '10', 'daily_call_limit' => '100',
		]]);
	}
	
	public function test_other_features_stop_at_80_percent_of_the_budget_and_moderation_goes_on(): void
	{
		// Arrange: $8 of $10 spent, 10 calls
		$this->setCounters(calls: 10, spentUsd: 8.0);
		$budget = app(AiBudget::class);
		
		// Act & Assert
		$this->assertSame(AiBudget::DAILY_BUDGET, $budget->check('quickpost_draft'));
		$this->assertSame(AiBudget::DAILY_BUDGET, $budget->check('transcription'));
		$this->assertNull($budget->check('moderation'));
	}
	
	public function test_other_features_stop_at_80_percent_of_the_call_limit(): void
	{
		// Arrange
		$this->setCounters(calls: 80, spentUsd: 1.0);
		$budget = app(AiBudget::class);
		
		// Act & Assert
		$this->assertSame(AiBudget::DAILY_CALL_LIMIT, $budget->check('quickpost_draft'));
		$this->assertNull($budget->check('moderation'));
	}
	
	public function test_moderation_stops_at_the_full_budget(): void
	{
		// Arrange
		$this->setCounters(calls: 10, spentUsd: 10.0);
		
		// Act & Assert
		$this->assertSame(AiBudget::DAILY_BUDGET, app(AiBudget::class)->check('moderation'));
	}
	
	private function setCounters(int $calls, float $spentUsd): void
	{
		Cache::put('ai:calls:' . now()->format('Y-m-d'), $calls, 3600);
		Cache::put('ai:spend_micro:' . now()->format('Y-m-d'), (int)round($spentUsd * 1_000_000), 3600);
	}
}
