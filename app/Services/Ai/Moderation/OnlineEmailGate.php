<?php

namespace App\Services\Ai\Moderation;

use App\Models\Post;
use App\Models\PostModeration;

/*
 * Decides whether the app's "your listing is online" email may go out (adniro:ai).
 * Used by App\Observers\PostObserver::sendNotification().
 */

final class OnlineEmailGate
{
	/** Not approved: no "online" email. */
	public const HOLD = 'hold';
	/** Just approved after waiting or being hidden: send the "online" email now. */
	public const ANNOUNCE = 'announce';
	/** Just approved again after an edit of a listing that was already live: no email. */
	public const SKIP = 'skip';
	/** Moderation has nothing to say: the app's own rules apply. */
	public const DEFAULT = 'default';
	
	public static function decide(Post $post): string
	{
		if (!ModerationStatus::isPublic($post->moderation_status)) {
			return self::HOLD;
		}
		
		if ($post->wasRecentlyCreated || !$post->wasChanged('moderation_status')) {
			return self::DEFAULT;
		}
		
		$original = $post->getOriginal('moderation_status');
		if (ModerationStatus::isPublic($original)) {
			return self::DEFAULT;
		}
		
		// Re-approved after an edit: tell the owner only if the listing was not live before.
		// The current decision's row is marked applied after this save, so the latest applied
		// row here is the previous decision.
		if ($original === ModerationStatus::PENDING) {
			$previous = PostModeration::query()
				->where('post_id', $post->getKey())
				->whereNotNull('applied_status')
				->orderByDesc('id')
				->value('applied_status');
			
			if ($previous === ModerationStatus::APPROVED) {
				return self::SKIP;
			}
		}
		
		return self::ANNOUNCE;
	}
}
