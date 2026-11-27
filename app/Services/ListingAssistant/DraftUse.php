<?php

namespace App\Services\ListingAssistant;

use App\Models\Post;
use App\Models\PostDraft;
use App\Models\User;

/*
 * Ties a published listing to the AI listing assistant draft its form was filled from (adniro:ai).
 *
 * The form carries the draft's token (the "ai_draft" field, set by ai.formbox.js when a draft fills
 * the form and taken back on undo). The token is long, random and only ever sent to the person who
 * asked for the draft, so holding it is enough for a draft made as a guest; a member's draft also
 * has to be their own. A draft marks one listing only.
 */

final class DraftUse
{
	/** Name of the form field, and of the API parameter, that carries the draft's token */
	public const FIELD = 'ai_draft';
	
	private const TOKEN_MAX_LENGTH = 40;
	
	/**
	 * The draft a new listing by this author was filled from, if any.
	 */
	public function find(mixed $token, ?User $author): ?PostDraft
	{
		if (!is_string($token) || $token === '' || strlen($token) > self::TOKEN_MAX_LENGTH) {
			return null;
		}
		
		$draft = PostDraft::query()
			->where('token', $token)
			->where('status', PostDraft::DRAFTED)
			->whereNull('post_id')
			->first();
		
		if (empty($draft)) {
			return null;
		}
		
		$isSomeoneElses = !empty($draft->user_id) && (int)$draft->user_id !== (int)$author?->getKey();
		
		return $isSomeoneElses ? null : $draft;
	}
	
	public function markPublished(PostDraft $draft, Post $post): void
	{
		$draft->forceFill(['post_id' => $post->getKey(), 'status' => PostDraft::POSTED])->save();
	}
}
