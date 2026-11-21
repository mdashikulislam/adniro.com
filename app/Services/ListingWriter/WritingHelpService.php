<?php

namespace App\Services\ListingWriter;

use App\Models\User;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\ListingAssistant\LimitStatus;

/*
 * AI writing help for the listing form's Title and Description (adniro:ai): suggest, shorten or
 * make clearer. It only returns text for the seller to review; it never changes a listing.
 * Members only; its own allowance (WritingLimit); every call also counts toward the site's
 * daily AI budget through the AI client.
 */

class WritingHelpService
{
	public const FEATURE = 'listing_writer';

	/** @var array<string, bool> remembered for the request */
	private array $availability = [];

	public function __construct(
		private readonly AiSettings $settings,
		private readonly AiClientContract $client,
		private readonly WritingLimit $limit,
		private readonly WritingPrompt $prompt,
		private readonly WritingSanitizer $sanitizer,
	)
	{
	}

	public function available(?User $user): bool
	{
		if (!$user) {
			return false;
		}

		return $this->availability['u' . $user->getKey()] ??= $this->settings->writingHelpEnabled() && $this->client->enabled(self::FEATURE);
	}

	public function limitStatus(User $user): LimitStatus
	{
		return $this->limit->status($user);
	}

	/**
	 * @return array{ok: bool, reason?: string, text?: string, limit: LimitStatus}
	 */
	public function write(WritingTask $task, User $user, ?string $ip, string $locale, ?int $postId = null): array
	{
		if (!$task->hasEnoughInput((int)config('ai_features.listing_writer.min_text', 10))) {
			return ['ok' => false, 'reason' => 'too_short', 'limit' => $this->limit->status($user)];
		}

		$lock = $this->limit->lock($user);
		if (!$lock->get()) {
			return ['ok' => false, 'reason' => 'busy', 'limit' => $this->limit->status($user)];
		}

		try {
			// Read inside the lock, so parallel requests cannot share the last use
			$status = $this->limit->status($user);
			if ($status->reached()) {
				return ['ok' => false, 'reason' => 'limit', 'limit' => $status];
			}

			$result = $this->client->structured(new AiRequest(
				feature: self::FEATURE,
				instructions: $this->prompt->instructions(),
				prompt: $this->prompt->prompt($task, $locale),
				schema: $this->prompt->schema(),
				maxTokens: (int)config('ai_features.listing_writer.max_tokens', 2000),
				timeoutSeconds: (int)config('ai_features.listing_writer.timeout', 20),
				userId: (int)$user->getKey(),
				ip: $ip,
				subjectType: $postId ? 'post' : null,
				subjectId: $postId,
				meta: ['prompt' => WritingPrompt::VERSION, 'task' => $task->field . ':' . $task->action],
			));

			if (!$result->ok()) {
				return ['ok' => false, 'reason' => 'ai_unavailable', 'limit' => $status];
			}

			$text = $this->sanitizer->sanitize($task->field, (string)($result->data['text'] ?? ''));
			if ($text === '') {
				return ['ok' => false, 'reason' => 'unusable', 'limit' => $this->limit->status($user)];
			}

			return ['ok' => true, 'text' => $text, 'limit' => $this->limit->status($user)];
		} finally {
			$lock->release();
		}
	}
}
