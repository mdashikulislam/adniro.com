<?php

namespace App\Services\ListingCleanUp;

use App\Models\Post;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * Finds the live listings that break a clean-up rule, and hides them (adniro).
 * Used by "php artisan listings:clean-up". Hiding goes through the moderation service, so every
 * hidden listing has a recorded decision and can be approved again in Admin > AI > Moderation.
 */

class ListingCleanUp
{
	/** Texts shorter than this are too plain ("Call me.") to say two listings are copies. */
	public const COPY_MIN_TEXT_LENGTH = 80;
	
	/** Copies are compared on the start of their text: the rest is often a city name swapped in. */
	public const COPY_TEXT_LENGTH = 200;
	
	public function __construct(private readonly ModerationService $moderation)
	{
	}
	
	/**
	 * The listings to hide, newest first.
	 *
	 * @param string[] $rules only these rules (all of them when empty)
	 * @param int[] $exceptIds listings to leave alone
	 * @return array<int, array{id: int, country: string, title: string, rule: string, categories: string[]}>
	 */
	public function plan(array $rules = [], array $exceptIds = []): array
	{
		$rules = empty($rules) ? CleanUpRules::ALL : $rules;
		$seenTitles = [];
		$seenTexts = [];
		$plan = [];
		
		foreach ($this->liveListings() as $listing) {
			$title = (string)$listing->title;
			$text = CleanUpRules::plainText($listing->description);
			
			$match = CleanUpRules::match($title, $listing->description, $listing->country_code);
			
			// Newest first: a listing whose title or text was already seen is the older copy
			$titleKey = mb_strtolower(trim($title));
			$textKey = mb_strlen($text) >= self::COPY_MIN_TEXT_LENGTH
				? md5(mb_strtolower(mb_substr($text, 0, self::COPY_TEXT_LENGTH)))
				: null;
			$isCopy = isset($seenTitles[$titleKey]) || (!is_null($textKey) && isset($seenTexts[$textKey]));
			$seenTitles[$titleKey] = true;
			if (!is_null($textKey)) {
				$seenTexts[$textKey] = true;
			}
			
			if (is_null($match) && $isCopy) {
				$match = ['rule' => CleanUpRules::DUPLICATE, 'categories' => CleanUpRules::PROMOTION_CATEGORIES];
			}
			if (is_null($match) || !in_array($match['rule'], $rules, true) || in_array((int)$listing->id, $exceptIds, true)) {
				continue;
			}
			
			$plan[] = [
				'id'         => (int)$listing->id,
				'country'    => (string)$listing->country_code,
				'title'      => $title,
				'rule'       => $match['rule'],
				'categories' => $match['categories'],
			];
		}
		
		return $plan;
	}
	
	/**
	 * Hide the planned listings. One listing that fails is logged and skipped.
	 *
	 * @return array{hidden: int, skipped: int, failed: int}
	 */
	public function apply(array $plan): array
	{
		$result = ['hidden' => 0, 'skipped' => 0, 'failed' => 0];
		
		foreach ($plan as $item) {
			try {
				$post = Post::query()->withoutGlobalScopes()->find($item['id']);
				
				// Changed since the plan was made (hidden, archived or deleted by someone else)
				if (!$post instanceof Post || $post->moderation_status !== ModerationStatus::APPROVED) {
					$result['skipped']++;
					continue;
				}
				
				$this->moderation->rejectByRule($post, $item['rule'], $item['categories']);
				$result['hidden']++;
			} catch (Throwable $e) {
				$result['failed']++;
				Log::error('Listings clean-up failed for a listing.', ['post_id' => $item['id'], 'error' => $e->getMessage()]);
			}
		}
		
		return $result;
	}
	
	/**
	 * Listings the public can see or could see once verified, newest first, without the ones an
	 * admin has approved by hand: that decision stands.
	 */
	private function liveListings(): iterable
	{
		return DB::table('posts')
			->select(['id', 'country_code', 'title', 'description'])
			->where('moderation_status', ModerationStatus::APPROVED)
			->whereNull('archived_at')
			->whereNull('deleted_at')
			->whereNotExists(function ($query) {
				$query->select(DB::raw(1))
					->from('post_moderations')
					->whereColumn('post_moderations.post_id', 'posts.id')
					->where('post_moderations.source', 'human')
					->where('post_moderations.decided_status', ModerationStatus::APPROVED);
			})
			->orderByDesc('id')
			->cursor();
	}
}
