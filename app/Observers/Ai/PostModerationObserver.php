<?php

namespace App\Observers\Ai;

use App\Models\Post;
use App\Services\Ai\Moderation\ContentHash;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationStatus;
use App\Services\Ai\Moderation\ModerationTrigger;
use Throwable;

/*
 * Listing moderation hooks (adniro:ai). Never breaks a save.
 *
 * - A new listing, or a change to its text, schedules a check. In enforce mode the listing is
 *   saved as "pending" in that same save, so it is never public before the check.
 * - Completing email or phone verification schedules the check of a listing that waited for it.
 * - Edits made in the admin panel are never sent to the AI; in enforce mode they count as accepted.
 */

class PostModerationObserver
{
	public function creating(Post $post): void
	{
		$this->markPendingInSameSave($post);
	}
	
	public function updating(Post $post): void
	{
		if ($post->isDirty(ContentHash::POST_COLUMNS)) {
			$this->markPendingInSameSave($post);
		}
	}
	
	public function created(Post $post): void
	{
		if (isFromAdminPanel()) {
			$this->acceptAdminContent($post);
			
			return;
		}
		
		$this->schedule($post, ModerationTrigger::CREATED);
	}
	
	public function updated(Post $post): void
	{
		if ($post->wasChanged(ContentHash::POST_COLUMNS)) {
			if (isFromAdminPanel()) {
				$this->acceptAdminContent($post);
				
				return;
			}
			
			$this->schedule($post, ModerationTrigger::EDITED);
			
			return;
		}
		
		if ($post->wasChanged(['email_verified_at', 'phone_verified_at'])) {
			$this->schedule($post, ModerationTrigger::VERIFIED);
		}
	}
	
	private function markPendingInSameSave(Post $post): void
	{
		try {
			if (!isFromAdminPanel() && app(ModerationService::class)->isEnforcing()) {
				$post->moderation_status = ModerationStatus::PENDING;
			}
		} catch (Throwable $e) {
			report($e);
		}
	}
	
	private function acceptAdminContent(Post $post): void
	{
		try {
			app(ModerationService::class)->acceptAdminContent($post);
		} catch (Throwable $e) {
			report($e);
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
