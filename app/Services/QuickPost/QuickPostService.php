<?php

namespace App\Services\QuickPost;

use App\Models\PostDraft;
use App\Models\User;
use App\Services\Ai\AiImage;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use Illuminate\Support\Str;

/*
 * Quick post: a person's text becomes a prefilled listing form (adniro:ai).
 * The draft call is synchronous; quick post never creates a listing itself.
 */

class QuickPostService
{
	public const FEATURE = 'quickpost_draft';
	public const MAX_PHOTOS = 4;
	
	/** Remembered for the request: "Post an ad" links ask on every page. */
	private array $availability = [];
	
	public function __construct(
		private readonly AiSettings $settings,
		private readonly AiClientContract $client,
		private readonly AiPostLimit $limit,
		private readonly DraftPrompt $prompt,
		private readonly DraftSanitizer $sanitizer,
		private readonly LocationResolver $location,
		private readonly DraftToPostInput $toPostInput,
	)
	{
	}
	
	/**
	 * Quick post is offered: switched on, a usable model assigned, the multi-step form in use,
	 * and the person allowed (members always, guests only when the admin allows them).
	 */
	public function available(?User $user): bool
	{
		$key = $user ? 'u' . $user->getKey() : 'guest';
		
		return $this->availability[$key] ??= $this->checkAvailable($user);
	}
	
	private function checkAvailable(?User $user): bool
	{
		if (!$this->settings->quickpostEnabled() || !isMultipleStepsFormEnabled()) {
			return false;
		}
		
		if (!$user && (!$this->settings->quickpostGuests() || !doesGuestHaveAbilityToCreateListings())) {
			return false;
		}
		
		return $this->client->enabled(self::FEATURE);
	}
	
	public function limitStatus(?User $user, ?string $ip): LimitStatus
	{
		return $this->limit->status($user, $ip);
	}
	
	/**
	 * @param array{session_id?: string, lat?: float|null, lon?: float|null, redraft_of?: string|null, photos?: array<int, string>} $context
	 * @return array{ok: bool, reason?: string, message?: string, draft?: PostDraft, limit: LimitStatus}
	 */
	public function draft(string $text, ?User $user, ?string $ip, string $locale, array $context = []): array
	{
		$text = trim($text);
		$photos = array_slice(array_values(array_filter((array)($context['photos'] ?? []))), 0, self::MAX_PHOTOS);
		
		// With a photo, the text is optional
		$min = empty($photos) ? (int)config('ai_features.quickpost.min_text', 10) : 0;
		if (mb_strlen($text) < $min) {
			return ['ok' => false, 'reason' => 'too_short', 'limit' => $this->limit->status($user, $ip)];
		}
		
		// Re-drafting the same draft is free a couple of times
		$parent = $this->ownedDraft($context['redraft_of'] ?? null, $user, $context['session_id'] ?? null);
		$free = $parent && $parent->redrafts < (int)config('ai_features.quickpost.free_redrafts', 2);
		
		$lock = $this->limit->lock($user, $ip);
		if (!$lock->get()) {
			return ['ok' => false, 'reason' => 'busy', 'limit' => $this->limit->status($user, $ip)];
		}
		
		try {
			$status = $this->limit->status($user, $ip);
			if (!$free && $status->reached()) {
				return ['ok' => false, 'reason' => 'limit', 'limit' => $status];
			}
			
			$countryCode = (string)config('country.code');
			$currency = (string)(config('currency.code') ?: config('country.currency_code'));
			
			$draft = PostDraft::query()->create([
				'token'        => Str::random(32),
				'user_id'      => $user?->getKey(),
				'session_id'   => $context['session_id'] ?? null,
				'ip'           => $ip,
				'locale'       => $locale,
				'country_code' => $countryCode ?: null,
				'input_text'   => mb_substr($text, 0, (int)config('ai_features.quickpost.max_text', 2000)),
				'input_meta'   => array_filter(['lat' => $context['lat'] ?? null, 'lon' => $context['lon'] ?? null], fn ($v) => !is_null($v)),
				'picture_paths' => $photos ?: null,
				'status'       => PostDraft::NEW,
				'redraft_of'   => $parent?->id,
				'expires_at'   => now()->addHours((int)config('ai_features.quickpost.draft_ttl', 48)),
			]);
			
			$images = [];
			foreach ($photos as $path) {
				$image = AiImage::fromStorage((string)$path);
				if ($image) {
					$images[] = $image;
				}
			}
			
			$result = $this->client->structured(new AiRequest(
				feature: self::FEATURE,
				instructions: $this->prompt->instructions(),
				prompt: $this->prompt->prompt($text, $locale, (string)config('country.name'), $currency, count($images)),
				schema: DraftSchema::schema(),
				images: $images,
				maxTokens: (int)config('ai_features.quickpost.max_tokens', 3000),
				timeoutSeconds: (int)config('ai_features.quickpost.timeout', 25),
				userId: $user?->getKey() ? (int)$user->getKey() : null,
				ip: $ip,
				subjectType: 'post_draft',
				subjectId: $draft->id,
				meta: ['prompt' => DraftPrompt::VERSION],
			));
			
			$draft->ai_usage_log_id = $result->usageLogId;
			
			if (!$result->ok()) {
				$draft->status = PostDraft::FAILED;
				$draft->error = mb_substr($result->status . ($result->errorType ? ': ' . $result->errorType : ''), 0, 500);
				$draft->save();
				
				// The form still works: the person's text goes into the description, the photos stay
				if ($text !== '') {
					$this->toPostInput->applyText($text);
				}
				
				return ['ok' => false, 'reason' => 'ai_unavailable', 'draft' => $draft, 'limit' => $status];
			}
			
			$clean = $this->sanitizer->sanitize((array)$result->data, $currency);
			$draft->raw_output = $result->data;
			
			if (!$clean['listable'] || empty($clean['values']['title'] ?? null)) {
				$draft->status = PostDraft::UNUSABLE;
				$draft->draft = $clean;
				$draft->save();
				
				return ['ok' => false, 'reason' => 'unusable', 'message' => $clean['unusable_reason'], 'draft' => $draft, 'limit' => $status];
			}
			
			[$city, $citySource] = $countryCode
				? $this->location->resolve($countryCode, $context['lat'] ?? null, $context['lon'] ?? null, $clean['location'], $user)
				: [null, null];
			$clean['city_id'] = $city?->id;
			$clean['city_source'] = $citySource;
			if ($city && $citySource !== 'position') {
				$clean['marks']['city_id'] = 'check';
			}
			
			$draft->draft = $clean;
			$draft->status = PostDraft::DRAFTED;
			$draft->counted = !$free;
			$draft->save();
			
			if ($free) {
				$parent->increment('redrafts');
			}
			
			return ['ok' => true, 'draft' => $draft, 'limit' => $this->limit->status($user, $ip)];
		} finally {
			$lock->release();
		}
	}
	
	/**
	 * Put a draft into the normal form. Only its owner (account or browser session) may use it.
	 */
	public function apply(string $token, ?User $user, ?string $sessionId): bool
	{
		$draft = $this->ownedDraft($token, $user, $sessionId);
		if (!$draft || $draft->isExpired() || !in_array($draft->status, [PostDraft::DRAFTED, PostDraft::APPLIED], true)) {
			return false;
		}
		
		$this->toPostInput->apply($draft);
		
		$draft->status = PostDraft::APPLIED;
		$draft->save();
		
		return true;
	}
	
	public function ownedDraft(?string $token, ?User $user, ?string $sessionId): ?PostDraft
	{
		if (empty($token)) {
			return null;
		}
		
		$draft = PostDraft::query()->where('token', $token)->first();
		if (!$draft) {
			return null;
		}
		
		$isOwner = $user
			? (int)$draft->user_id === (int)$user->getKey()
			: (is_null($draft->user_id) && !empty($sessionId) && hash_equals((string)$draft->session_id, (string)$sessionId));
		
		return $isOwner ? $draft : null;
	}
}
