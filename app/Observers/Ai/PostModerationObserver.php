<?php

namespace App\Observers\Ai;

use App\Models\Post;
use App\Services\Ai\Moderation\ContentHash;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationTrigger;
use Throwable;

/*
 * Schedules a moderation check when a listing is created, its text changes,
 * or its verification completes (adniro:ai). Never breaks a save.
 */

class PostModerationObserver
{
	public function created(Post $post): void
	{
		$this->schedule($post, ModerationTrigger::CREATED);
	}
	
	public function updated(Post $post): void
	{
		if ($post->wasChanged(ContentHash::POST_COLUMNS)) {
			$this->schedule($post, ModerationTrigger::EDITED);
			
			return;
		}
		
		if ($post->wasChanged(['email_verified_at', 'phone_verified_at'])) {
			$this->schedule($post, ModerationTrigger::VERIFIED);
		}
	}
	
	private function schedule(Post $post, string $trigger): void
	{
		try {
			app(ModerationService::class)->schedule($post, $trigger);
		} catch (Throwable $e) {
			report($e);
		}
	}
}
