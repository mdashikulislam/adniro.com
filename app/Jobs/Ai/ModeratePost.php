<?php

namespace App\Jobs\Ai;

use App\Services\Ai\Moderation\ModerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Throwable;

/*
 * One AI check of one listing (adniro:ai).
 *
 * Unique per listing until it starts, so a burst of saves (text, then photos, then fields)
 * becomes one check. A save made while the check runs queues the next one.
 */

class ModeratePost implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
	use Dispatchable, InteractsWithQueue, Queueable;
	
	public int $tries = 3;
	public int $timeout = 120;
	public int $uniqueFor = 900;
	
	public function __construct(
		public readonly int $postId,
		public readonly string $trigger,
		public readonly ?string $locale = null,
	)
	{
	}
	
	public function uniqueId(): string
	{
		return 'post-' . $this->postId;
	}
	
	public function handle(ModerationService $service): void
	{
		$isSync = $this->job === null || $this->job instanceof SyncJob;
		$finalAttempt = $isSync || $this->attempts() >= $this->tries;
		
		$outcome = $service->moderate($this->postId, $this->trigger, $this->locale, $finalAttempt);
		
		if ($outcome === ModerationService::RETRY) {
			$this->release($this->attempts() <= 1 ? 30 : 120);
		}
	}
	
	public function failed(?Throwable $e): void
	{
		app(ModerationService::class)->recordFailure(
			$this->postId,
			$this->trigger,
			$this->locale,
			$e ?? new \RuntimeException('Moderation job failed')
		);
	}
}
