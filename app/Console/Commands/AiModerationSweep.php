<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\PostModeration;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationStatus;
use App\Services\Ai\Moderation\ModerationTrigger;
use App\Services\Ai\Moderation\Policy;
use Illuminate\Console\Command;

/*
 * php artisan ai:moderation-sweep (adniro:ai), scheduled every 5 minutes.
 *
 * Listings waiting for a check longer than 10 minutes are queued again.
 * Listings waiting longer than 60 minutes go to an admin.
 * In shadow mode no listing is ever waiting, so this does nothing.
 */

class AiModerationSweep extends Command
{
	public const RETRY_AFTER_MINUTES = 10;
	public const GIVE_UP_AFTER_MINUTES = 60;
	
	protected $signature = 'ai:moderation-sweep';
	
	protected $description = 'Re-queue or hand over listings stuck waiting for the AI check (adniro:ai).';
	
	public function handle(ModerationService $service): int
	{
		$base = fn () => Post::query()
			->withoutGlobalScopes()
			->where('moderation_status', ModerationStatus::PENDING);
		
		$handedOver = 0;
		$base()
			->where('updated_at', '<', now()->subMinutes(self::GIVE_UP_AFTER_MINUTES))
			->chunkById(100, function ($posts) use (&$handedOver) {
				foreach ($posts as $post) {
					$post->forceFill(['moderation_status' => ModerationStatus::NEEDS_REVIEW])->save();
					PostModeration::query()->create([
						'post_id'        => $post->getKey(),
						'trigger'        => ModerationTrigger::SWEEP,
						'source'         => 'system',
						'policy_version' => Policy::VERSION,
						'decided_status' => ModerationStatus::NEEDS_REVIEW,
						'applied_status' => ModerationStatus::NEEDS_REVIEW,
						'rule'           => 'sweep_timeout',
						'error'          => 'No AI decision after ' . self::GIVE_UP_AFTER_MINUTES . ' minutes',
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
