<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Listeners;

use App\Events\PostWasVisited;
use Throwable;

class UpdatePostVisits
{
	/**
	 * Create the event listener.
	 */
	public function __construct()
	{
		//
	}
	
	/**
	 * Handle the event.
	 *
	 * @param \App\Events\PostWasVisited $event
	 * @return bool
	 */
	public function handle(PostWasVisited $event)
	{
		$post = $event->post;
		$postId = $post->id ?? null;
		$postUserId = $post->user_id ?? null;
		
		// Don't count the author's self-visits
		$guard = getAuthGuard();
		if (auth($guard)->check()) {
			if (auth($guard)->user()->getAuthIdentifier() == $postUserId) {
				return false;
			}
		}
		
		$alreadyVisited = isFromApi()
			? (request()->header('X-VISITED-BY-SAME-SESSION') == $postId)
			: (session('isPostVisited') == $postId);
		
		if (!$alreadyVisited) {
			$this->incrementVisits($post);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param $post
	 * @return void
	 */
	private function incrementVisits($post): void
	{
		try {
			if (empty($post->id)) {
				return;
			}
			
			// A plain query, not $post->save(): it must neither touch "updated_at" (it's the
			// "lastmod" of the XML sitemaps, it only changes when the listing's content does)
			// nor fire the model events, whose cache invalidation flushes every cached listings
			// query on each page view (search results, similar listings, sitemaps...)
			$post->getConnection()
				->table($post->getTable())
				->where($post->getKeyName(), $post->getKey())
				->increment('visits');
			
			// Keep the in-memory model in sync, without marking it as changed
			$post->setRawAttributes(array_merge($post->getAttributes(), ['visits' => (int)$post->visits + 1]), true);
		} catch (Throwable $e) {
		}
	}
}
