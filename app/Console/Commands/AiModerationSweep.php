<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationStatus;
use App\Services\Ai\Moderation\ModerationTrigger;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * php artisan ai:moderation-sweep (adniro:ai), scheduled every 5 minutes.
 *
 * Enforce mode: verified listings waiting for a check longer than 10 minutes are queued again,
 * and those waiting longer than 60 minutes go to an admin.
 * Enforce chosen but not possible (no model passed the evaluation): waiting listings go to an admin.
 * Moderation switched to off or shadow: listings left waiting from an earlier enforce period are
 * published or held, as chosen in "When the AI cannot be used".
 * If the settings cannot be read, nothing is changed.
 */

class AiModerationSweep extends Command
{
	public const RETRY_AFTER_MINUTES = 10;
	public const GIVE_UP_AFTER_MINUTES = 60;
	
	protected $signature = 'ai:moderation-sweep';
	
	protected $description = 'Re-queue or hand over listings stuck waiting for the AI check (adniro:ai).';
	
	public function handle(ModerationService $service, AiSettings $settings): int
	{
		// Without the real settings, nothing is published or released
		if ($settings->readFailed()) {
			$this->error('The AI settings could not be read. Nothing was changed.');
			
			return self::FAILURE;
		}
		
		if ($service->isEnforcing()) {
			return $this->sweepEnforced($service);
		}
		
		// Enforce is chosen but cannot run (no model passed the evaluation): admins decide
		if ($settings->moderationModeSetting() === 'enforce') {
			return $this->eachWaiting('Handed to admins', fn (Post $post) => $service->handOver($post, 'enforce_degraded', [
				'trigger' => ModerationTrigger::SWEEP,
				'error'   => 'Enforce is chosen, but no assigned model has passed the evaluation',
			]));
		}
		
		// Enforce was switched off on purpose: nothing will check these, so publish or hold them as configured
		return $this->eachWaiting('Moderation is not enforced. Released', fn (Post $post) => $service->release(
			$post,
			'enforce_off',
			['trigger' => ModerationTrigger::SWEEP]
		));
	}
	
	private function sweepEnforced(ModerationService $service): int
	{
		$giveUpBefore = now()->subMinutes(self::GIVE_UP_AFTER_MINUTES);
		$retryBefore = now()->subMinutes(self::RETRY_AFTER_MINUTES);
		
		$handedOver = $this->each(
			$this->waiting()->where('updated_at', '<', $giveUpBefore),
			fn (Post $post) => $service->handOver($post, 'sweep_timeout', [
				'trigger' => ModerationTrigger::SWEEP,
				'error'   => 'No AI decision after ' . self::GIVE_UP_AFTER_MINUTES . ' minutes',
			])
		);
		$requeued = $this->each(
			$this->waiting()->where('updated_at', '<', $retryBefore),
			fn (Post $post) => $service->schedule($post, ModerationTrigger::SWEEP)
		);
		
		$this->info("Re-queued: {$requeued['done']}. Handed to admins: {$handedOver['done']}.");
		
		return $this->finish($handedOver['failed'] + $requeued['failed']);
	}
	
	private function eachWaiting(string $label, callable $action): int
	{
		$result = $this->each($this->waiting(), $action);
		$this->info("{$label}: {$result['done']}.");
		
		return $this->finish($result['failed']);
	}
	
	/**
	 * Unverified listings wait for verification, not for the AI.
	 */
	private function waiting(): Builder
	{
		return Post::query()
			->withoutGlobalScopes()
			->where('moderation_status', ModerationStatus::PENDING)
			->whereNotNull('email_verified_at')
			->whereNotNull('phone_verified_at')
			->whereNull('archived_at');
	}
	
	/**
	 * Run $action on each listing. One listing that fails is logged and skipped, so it cannot
	 * stop the others. An action returning false (nothing done) is not counted.
	 *
	 * @return array{done: int, failed: int}
	 */
	private function each(Builder $query, callable $action): array
	{
		$done = 0;
		$failed = 0;
		
		$query->chunkById(100, function ($posts) use ($action, &$done, &$failed) {
			foreach ($posts as $post) {
				try {
					if ($action($post) !== false) {
						$done++;
					}
				} catch (Throwable $e) {
					$failed++;
					Log::error('Moderation sweep failed for a listing.', ['post_id' => $post->getKey(), 'error' => $e->getMessage()]);
				}
			}
		});
		
		return ['done' => $done, 'failed' => $failed];
	}
	
	private function finish(int $failed): int
	{
		if ($failed > 0) {
			$this->error("Failed: {$failed} (see the log).");
			
			return self::FAILURE;
		}
		
		return self::SUCCESS;
	}
}
