<?php

namespace App\Services\Ai\Moderation;

use App\Jobs\Ai\ModeratePost;
use App\Models\AiUsageLog;
use App\Models\Post;
use App\Models\PostModeration;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use Throwable;

/*
 * Schedules and runs the AI check of a listing (adniro:ai).
 *
 * Phase 3 runs in shadow mode only: every decision is recorded in post_moderations,
 * and the listing itself is never changed.
 */

class ModerationService
{
	public const FEATURE = 'moderation';
	public const DELAY_SECONDS = 15;
	public const MAX_OUTPUT_TOKENS = 2000;
	
	// Outcomes of moderate()
	public const DECIDED = 'decided';
	public const SKIPPED = 'skipped';
	public const ALREADY_CHECKED = 'already_checked';
	public const LIMITED = 'limited';
	public const CAPPED = 'capped';
	public const RETRY = 'retry';
	public const FAILED = 'failed';
	public const RESCHEDULED = 'rescheduled';
	
	public function __construct(
		private readonly AiSettings $settings,
		private readonly AiClientContract $client,
	)
	{
	}
	
	public function mode(): string
	{
		return $this->settings->enabled() ? $this->settings->moderationMode() : 'off';
	}
	
	public function isActive(): bool
	{
		return $this->mode() !== 'off';
	}
	
	/**
	 * Queue one check for the listing, about 15 seconds from now. Further calls for the same
	 * listing before the check starts are dropped, so a burst of saves costs one AI call.
	 */
	public function schedule(Post|int|string|null $post, string $trigger, ?string $locale = null): bool
	{
		if (isFromAdminPanel() || !$this->isActive()) {
			return false;
		}
		
		if (!$post instanceof Post) {
			$post = empty($post) ? null : Post::query()->withoutGlobalScopes()->find($post);
		}
		if (!$post instanceof Post || !$this->isCheckable($post)) {
			return false;
		}
		
		$job = new ModeratePost((int)$post->getKey(), $trigger, $locale ?: app()->getLocale());
		
		$connection = (string)config('ai_features.queue.connection');
		if (config('queue.connections.' . $connection . '.driver') === 'sync') {
			// No worker: run after the response is sent, once the photos and fields are saved too
			dispatch($job)->onConnection($connection)->afterResponse();
		} else {
			dispatch($job)
				->onConnection($connection)
				->onQueue((string)config('ai_features.queue.name', 'ai'))
				->delay(now()->addSeconds(self::DELAY_SECONDS))
				->afterCommit();
		}
		
		return true;
	}
	
	/**
	 * Run the check. Called by the ModeratePost job.
	 */
	public function moderate(int $postId, string $trigger, ?string $locale = null, bool $finalAttempt = true): string
	{
		$post = Post::query()->withoutGlobalScopes()->find($postId);
		if (!$post instanceof Post || !$this->isCheckable($post)) {
			return self::SKIPPED;
		}
		
		$mode = $this->mode();
		if ($mode === 'off') {
			return self::SKIPPED;
		}
		
		$hash = ContentHash::for($post);
		if ($this->alreadyChecked($post, $hash)) {
			return self::ALREADY_CHECKED;
		}
		
		$base = [
			'post_id'        => $post->getKey(),
			'trigger'        => $trigger,
			'mode'           => $mode,
			'content_hash'   => $hash,
			'locale'         => $locale,
			'policy_version' => Policy::VERSION,
		];
		
		$limit = $this->limitReached($post);
		if (!is_null($limit)) {
			$this->record($base + [
					'source'         => 'system',
					'decided_status' => ModerationStatus::NEEDS_REVIEW,
					'rule'           => $limit,
					'error'          => $limit,
				]);
			
			return self::LIMITED;
		}
		
		$images = ListingPayload::images($post);
		$result = $this->client->structured(new AiRequest(
			feature: self::FEATURE,
			instructions: Policy::instructions(),
			prompt: ListingPayload::prompt(ListingPayload::fromPost($post), $locale, count($images)),
			schema: Policy::schema(),
			images: $images,
			maxTokens: self::MAX_OUTPUT_TOKENS,
			userId: $post->user_id ? (int)$post->user_id : null,
			ip: $this->ipOf($post),
			subjectType: 'post',
			subjectId: $post->getKey(),
			meta: ['trigger' => $trigger, 'policy' => Policy::VERSION],
		));
		
		if ($result->status === AiResult::DISABLED) {
			return self::SKIPPED;
		}
		
		if ($result->status === AiResult::CAPPED) {
			$this->record($base + [
					'source' => 'system',
					'rule'   => 'capped',
					'error'  => 'capped: ' . $result->errorType,
				]);
			
			return self::CAPPED;
		}
		
		if ($result->status === AiResult::ERROR) {
			if (!$finalAttempt) {
				return self::RETRY;
			}
			
			$this->record($base + $this->modelColumns($result) + [
					'source'         => 'system',
					'decided_status' => ModerationStatus::NEEDS_REVIEW,
					'rule'           => 'error',
					'error'          => mb_substr(trim($result->errorType . ': ' . $result->errorMessage), 0, 500),
				]);
			
			return self::FAILED;
		}
		
		// The listing changed while the model was reading it: throw the answer away and check again
		$fresh = Post::query()->withoutGlobalScopes()->find($postId);
		if (!$fresh instanceof Post) {
			return self::SKIPPED;
		}
		if (ContentHash::for($fresh) !== $hash) {
			$this->schedule($fresh, ModerationTrigger::CHANGED_DURING_CHECK, $locale);
			
			return self::RESCHEDULED;
		}
		
		$mapped = VerdictMapper::map($result);
		
		$this->record($base + $this->modelColumns($result) + [
				'source'          => 'ai',
				'verdict'         => $mapped->verdict,
				'decided_status'  => $mapped->status,
				'rule'            => $mapped->rule,
				'categories'      => $mapped->categories,
				'confidence'      => $mapped->confidence,
				'user_reason'     => $mapped->userReason,
				'internal_reason' => $mapped->internalReason,
				'error'           => $result->ok() ? null : $result->status,
			]);
		
		return self::DECIDED;
	}
	
	/**
	 * Called when the job itself crashed on its last try.
	 */
	public function recordFailure(int $postId, string $trigger, ?string $locale, Throwable $e): void
	{
		try {
			if (!Post::query()->withoutGlobalScopes()->whereKey($postId)->exists()) {
				return;
			}
			
			$this->record([
				'post_id'        => $postId,
				'trigger'        => $trigger,
				'mode'           => $this->mode(),
				'source'         => 'system',
				'locale'         => $locale,
				'policy_version' => Policy::VERSION,
				'decided_status' => ModerationStatus::NEEDS_REVIEW,
				'rule'           => 'job_failed',
				'error'          => mb_substr(class_basename($e) . ': ' . $e->getMessage(), 0, 500),
			]);
		} catch (Throwable $e) {
			report($e);
		}
	}
	
	/**
	 * Only verified, unarchived listings are sent to the AI.
	 */
	public function isCheckable(Post $post): bool
	{
		if (method_exists($post, 'trashed') && $post->trashed()) {
			return false;
		}
		
		return empty($post->archived_at)
			&& !empty($post->email_verified_at)
			&& !empty($post->phone_verified_at);
	}
	
	/**
	 * The same content was decided already: by an admin or an enforced decision (the hash on the
	 * listing), or by an earlier AI check (shadow mode leaves the listing untouched).
	 */
	private function alreadyChecked(Post $post, string $hash): bool
	{
		if ($post->moderation_hash === $hash) {
			return true;
		}
		
		return PostModeration::query()
			->where('post_id', $post->getKey())
			->where('content_hash', $hash)
			->where('source', 'ai')
			->exists();
	}
	
	private function limitReached(Post $post): ?string
	{
		$today = now()->startOfDay();
		
		$postLimit = $this->settings->moderationPostDailyLimit();
		if ($postLimit > 0) {
			$count = PostModeration::query()
				->where('post_id', $post->getKey())
				->where('source', 'ai')
				->where('created_at', '>=', $today)
				->count();
			if ($count >= $postLimit) {
				return 'post_limit';
			}
		}
		
		$userLimit = $this->settings->moderationUserDailyLimit();
		if ($userLimit > 0) {
			$query = AiUsageLog::query()
				->where('feature', self::FEATURE)
				->where('created_at', '>=', $today);
			
			if (!empty($post->user_id)) {
				$query->where('user_id', $post->user_id);
			} else if (!empty($ip = $this->ipOf($post))) {
				$query->whereNull('user_id')->where('ip', $ip);
			} else {
				return null;
			}
			
			if ($query->count() >= $userLimit) {
				return 'user_limit';
			}
		}
		
		return null;
	}
	
	private function ipOf(Post $post): ?string
	{
		$ip = $post->latest_update_ip ?: $post->create_from_ip;
		
		return empty($ip) ? null : (string)$ip;
	}
	
	private function modelColumns(AiResult $result): array
	{
		return [
			'model'           => $result->model ? trim($result->provider . '/' . $result->model, '/') : null,
			'ai_usage_log_id' => $result->usageLogId,
		];
	}
	
	private function record(array $attributes): PostModeration
	{
		return PostModeration::query()->create($attributes);
	}
}
