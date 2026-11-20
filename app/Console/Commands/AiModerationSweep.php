<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationStatus;
use App\Services\Ai\Moderation\ModerationTrigger;
use Illuminate\Console\Command;

/*
 * php artisan ai:moderation-sweep (adniro:ai), scheduled every 5 minutes.
 *
 * Enforce mode: verified listings waiting for a check longer than 10 minutes are queued again,
 * and those waiting longer than 60 minutes go to an admin.
 * Otherwise: listings left waiting from an earlier enforce period are published or held,
 * as chosen in "When the AI cannot be used".
 */

class AiModerationSweep extends Command
{
	public const RETRY_AFTER_MINUTES = 10;
	public const GIVE_UP_AFTER_MINUTES = 60;
	
	protected $signature = 'ai:moderation-sweep';
	
	protected $description = 'Re-queue or hand over listings stuck waiting for the AI check (adniro:ai).';
	
	public function handle(ModerationService $service): int
	{
		// Unverified listings wait for verification, not for the AI
		$base = fn () => Post::query()
			->withoutGlobalScopes()
			->where('moderation_status', ModerationStatus::PENDING)
			->whereNotNull('email_verified_at')
			->whereNotNull('phone_verified_at')
			->whereNull('archived_at');
		
		// Enforce was switched off: nothing will check these, so publish or hold them as configured
		if (!$service->isEnforcing()) {
			$released = 0;
			$base()->chunkById(100, function ($posts) use ($service, &$released) {
				foreach ($posts as $post) {
					$service->release($post, 'enforce_off', ['trigger' => ModerationTrigger::SWEEP]);
					$released++;
				}
			});
			
			$this->info("Moderation is not enforced. Released: {$released}.");
			
			return self::SUCCESS;
		}
		
		$handedOver = 0;
		$base()
			->where('updated_at', '<', now()->subMinutes(self::GIVE_UP_AFTER_MINUTES))
			->chunkById(100, function ($posts) use ($service, &$handedOver) {
				foreach ($posts as $post) {
					$service->handOver($post, 'sweep_timeout', [
						'trigger' => ModerationTrigger::SWEEP,
						'error'   => 'No AI decision after ' . self::GIVE_UP_AFTER_MINUTES . ' minutes',
					]);
					$handedOver++;
				}
			});
		
		$requeued = 0;
		$base()
			->where('updated_at', '<', now()->subMinutes(self::RETRY_AFTER_MINUTES))
			->chunkById(100, function ($posts) use ($service, &$requeued) {
				foreach ($posts as $post) {
					if ($service->schedule($post, ModerationTrigger::SWEEP)) {
						$requeued++;
					}
				}
			});
		
		$this->info("Re-queued: {$requeued}. Handed to admins: {$handedOver}.");
		
		return self::SUCCESS;
	}
}
