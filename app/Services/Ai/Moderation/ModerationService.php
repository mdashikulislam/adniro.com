<?php

namespace App\Services\Ai\Moderation;

use App\Jobs\Ai\ModeratePost;
use App\Models\AiUsageLog;
use App\Models\Permission;
use App\Models\Post;
use App\Models\PostModeration;
use App\Models\User;
use App\Notifications\AdminModerationAlert;
use App\Notifications\PostNotApproved;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiResult;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Contracts\AiClientContract;
use App\Services\Ai\SecretRedactor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/*
 * Schedules and runs the AI check of a listing (adniro:ai).
 *
 * shadow: every decision is recorded in post_moderations, the listing is never changed.
 * enforce: new and edited listings wait as "pending", then the decision is applied to the
 * listing, and a rejection is emailed to its owner.
 */

class ModerationService
{
	public const FEATURE = 'moderation';
	public const DELAY_SECONDS = 15;
	public const ALERTS_PER_HOUR = 20;
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
	
	public function isEnforcing(): bool
	{
		return $this->mode() === 'enforce';
	}
	
	/**
	 * In enforce mode, hide a listing whose photos or fields just changed until it is checked again.
	 * (Text changes are marked in the same save by the post observer.)
	 */
	public function markPending(Post|int|string|null $post): void
	{
		if (isFromAdminPanel() || !$this->isEnforcing()) {
			return;
		}
		
		if (!$post instanceof Post) {
			$post = empty($post) ? null : Post::query()->withoutGlobalScopes()->find($post);
		}
		if (!$post instanceof Post || $post->moderation_status === ModerationStatus::PENDING) {
			return;
		}
		
		$post->forceFill(['moderation_status' => ModerationStatus::PENDING])->save();
	}
	
	/**
	 * An admin's edit counts as a decision on that content, so it is never re-checked (enforce mode).
	 */
	public function acceptAdminContent(Post|int|string|null $post): void
	{
		if (!isFromAdminPanel() || !$this->isEnforcing()) {
			return;
		}
		
		if (!$post instanceof Post) {
			$post = empty($post) ? null : Post::query()->withoutGlobalScopes()->find($post);
		}
		if (!$post instanceof Post) {
			return;
		}
		
		// No visible change, so no need to go through the model events
		DB::table($post->getTable())->where('id', $post->getKey())->update(['moderation_hash' => ContentHash::for($post)]);
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
		
		$this->dispatch(new ModeratePost((int)$post->getKey(), $trigger, $locale ?: app()->getLocale()));
		
		return true;
	}
	
	/**
	 * Admin queue: ask the AI again, even if this content was decided before.
	 */
	public function rerun(Post $post): bool
	{
		if (!$this->isActive() || !$this->isCheckable($post)) {
			return false;
		}
		
		$this->dispatch(new ModeratePost((int)$post->getKey(), ModerationTrigger::RERUN, $this->localeFor($post), true), 0);
		
		return true;
	}
	
	/**
	 * Admin queue: approve or reject the listing as it is now. Stored with its fingerprint,
	 * so the same content is never flagged again. Applied in any mode.
	 */
	public function decideByAdmin(Post $post, string $status, User $admin, array $categories = [], ?string $reason = null): PostModeration
	{
		$categories = array_values(array_unique(array_filter($categories, fn ($code) => is_string($code) && Policy::severity($code) !== null)));
		$hash = ContentHash::for($post);
		
		$row = $this->record([
			'post_id'        => $post->getKey(),
			'trigger'        => 'admin',
			'mode'           => $this->mode(),
			'source'         => 'human',
			'content_hash'   => $hash,
			'decided_status' => $status,
			'rule'           => 'admin_' . $status,
			'categories'     => $status === ModerationStatus::REJECTED ? $categories : [],
			'user_reason'    => $status === ModerationStatus::REJECTED ? VerdictMapper::cleanUserReason($reason) : null,
			'locale'         => $this->localeFor($post),
			'policy_version' => Policy::VERSION,
			'reviewer_id'    => $admin->getKey(),
		]);
		
		// With human review on, an approved listing also needs "reviewed_at" to go live
		$extra = [];
		if (
			$status === ModerationStatus::APPROVED
			&& config('settings.listing_form.listings_review_activation') == '1'
			&& empty($post->reviewed_at)
		) {
			$extra['reviewed_at'] = now();
		}
		
		$this->apply($post, $status, $hash, $row, $extra);
		
		return $row;
	}
	
	/**
	 * Hand a listing to the admins, with a recorded reason (sweep timeout and similar).
	 */
	public function handOver(Post $post, string $rule, array $attributes = []): PostModeration
	{
		$row = $this->record(array_merge([
			'post_id'        => $post->getKey(),
			'mode'           => $this->mode(),
			'policy_version' => Policy::VERSION,
		], $attributes, [
			'source'         => 'system',
			'decided_status' => ModerationStatus::NEEDS_REVIEW,
			'rule'           => $rule,
		]));
		
		$this->apply($post, ModerationStatus::NEEDS_REVIEW, null, $row);
		
		return $row;
	}
	
	private function dispatch(ModeratePost $job, ?int $delaySeconds = null): void
	{
		$connection = (string)config('ai_features.queue.connection');
		if (config('queue.connections.' . $connection . '.driver') === 'sync') {
			// No worker: run after the response is sent, once the photos and fields are saved too
			dispatch($job)->onConnection($connection)->afterResponse();
			
			return;
		}
		
		dispatch($job)
			->onConnection($connection)
			->onQueue((string)config('ai_features.queue.name', 'ai'))
			->delay(now()->addSeconds($delaySeconds ?? self::DELAY_SECONDS))
			->afterCommit();
	}
	
	private function localeFor(Post $post): string
	{
		$locale = PostModeration::query()->where('post_id', $post->getKey())->whereNotNull('locale')->orderByDesc('id')->value('locale');
		
		return (string)($locale ?: ($post->user?->language_code ?: config('app.locale')));
	}
	
	/**
	 * Run the check. Called by the ModeratePost job.
	 */
	public function moderate(int $postId, string $trigger, ?string $locale = null, bool $finalAttempt = true, bool $force = false): string
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
		$enforce = $mode === 'enforce';
		
		// The same content was decided before: reuse that decision, no AI call
		$previous = $force ? null : $this->previousDecision($post, $hash);
		if (!is_null($previous)) {
			if ($enforce) {
				[$status, $row] = $previous;
				if ($post->moderation_status !== $status) {
					$this->apply($post, $status, $hash, $row);
				}
			}
			
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
		
		$limit = $force ? null : $this->limitReached($post);
		if (!is_null($limit)) {
			$row = $this->record($base + [
					'source'         => 'system',
					'decided_status' => ModerationStatus::NEEDS_REVIEW,
					'rule'           => $limit,
					'error'          => $limit,
				]);
			if ($enforce) {
				$this->apply($post, ModerationStatus::NEEDS_REVIEW, null, $row);
			}
			
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
		
		// Over the daily budget or call limit: the listing waits for an admin, whatever "When the AI
		// cannot be used" says, so that spending the budget cannot publish everything
		if ($result->status === AiResult::CAPPED) {
			$row = $this->record($base + [
					'source'         => 'system',
					'decided_status' => $enforce ? ModerationStatus::NEEDS_REVIEW : null,
					'rule'           => 'capped',
					'error'          => 'capped: ' . $result->errorType,
				]);
			if ($enforce) {
				$this->apply($post, ModerationStatus::NEEDS_REVIEW, null, $row);
			}
			
			return self::CAPPED;
		}
		
		// No usable model: publish or hold, as chosen in Settings > AI > Moderation
		if ($result->status === AiResult::DISABLED) {
			if ($enforce) {
				$this->release($post, 'unavailable', $base + [
						'error' => $result->status . ($result->errorType ? ': ' . $result->errorType : ''),
					]);
			}
			
			return self::SKIPPED;
		}
		
		if ($result->status === AiResult::ERROR) {
			if (!$finalAttempt) {
				return self::RETRY;
			}
			
			$row = $this->record($base + $this->modelColumns($result) + [
					'source'         => 'system',
					'decided_status' => ModerationStatus::NEEDS_REVIEW,
					'rule'           => 'error',
					'error'          => mb_substr(SecretRedactor::redact(trim($result->errorType . ': ' . $result->errorMessage)), 0, 500),
				]);
			if ($enforce) {
				$this->apply($post, ModerationStatus::NEEDS_REVIEW, null, $row);
			}
			
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
		
		$mapped = $this->holdIfPhotosUnchecked(VerdictMapper::map($result), $fresh, count($images));
		
		$row = $this->record($base + $this->modelColumns($result) + [
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
		
		if ($enforce) {
			$this->apply($fresh, $mapped->status, $hash, $row);
		} else if (in_array('child_safety', $mapped->categories, true)) {
			$this->alertAdmins($fresh, $row, true);
		}
		
		return self::DECIDED;
	}
	
	/**
	 * An approval only covers the photos the AI saw. A photo that could not be read, or one past
	 * the number sent, sends the listing to an admin. The AI's answer is kept for them.
	 */
	private function holdIfPhotosUnchecked(MappedVerdict $mapped, Post $post, int $checkedPhotos): MappedVerdict
	{
		if (!$mapped->isApproved() || ListingPayload::pictureCount($post) <= $checkedPhotos) {
			return $mapped;
		}
		
		return new MappedVerdict(
			status: ModerationStatus::NEEDS_REVIEW,
			verdict: $mapped->verdict,
			categories: $mapped->categories,
			confidence: $mapped->confidence,
			userReason: $mapped->userReason,
			internalReason: trim($mapped->internalReason . ' Some photos could not be checked.'),
			rule: 'photos_not_checked',
		);
	}
	
	/**
	 * Enforce mode, AI not usable: publish the listing or hold it for an admin,
	 * as chosen in Settings > AI > Moderation. Also used by the sweep when enforce is switched off.
	 */
	public function release(Post $post, string $rule, array $attributes = []): void
	{
		$status = $this->settings->moderationWhenUnavailable() === 'review'
			? ModerationStatus::NEEDS_REVIEW
			: ModerationStatus::APPROVED;
		
		$row = $this->record(array_merge([
			'post_id'        => $post->getKey(),
			'mode'           => $this->mode(),
			'policy_version' => Policy::VERSION,
		], $attributes, [
			'source'         => 'system',
			'decided_status' => $status,
			'rule'           => $rule,
		]));
		
		$this->apply($post, $status, null, $row);
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
			
			$row = $this->record([
				'post_id'        => $postId,
				'trigger'        => $trigger,
				'mode'           => $this->mode(),
				'source'         => 'system',
				'locale'         => $locale,
				'policy_version' => Policy::VERSION,
				'decided_status' => ModerationStatus::NEEDS_REVIEW,
				'rule'           => 'job_failed',
				'error'          => mb_substr(class_basename($e) . ': ' . SecretRedactor::redact($e->getMessage()), 0, 500),
			]);
			
			$post = Post::query()->withoutGlobalScopes()->find($postId);
			if ($post && $this->isEnforcing() && $post->moderation_status === ModerationStatus::PENDING) {
				$this->apply($post, ModerationStatus::NEEDS_REVIEW, null, $row);
			}
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
	 * The decision taken earlier on exactly this content, by the AI or an admin.
	 * A matching hash on the listing without a recorded row is an admin edit, which counts as approved.
	 *
	 * @return array{0: string, 1: PostModeration|null}|null
	 */
	private function previousDecision(Post $post, string $hash): ?array
	{
		$row = PostModeration::query()
			->where('post_id', $post->getKey())
			->where('content_hash', $hash)
			->whereIn('source', ['ai', 'human'])
			->whereNotNull('decided_status')
			->orderByDesc('id')
			->first();
		if ($row) {
			return [$row->decided_status, $row];
		}
		
		if ($post->moderation_hash === $hash) {
			$status = $post->moderation_status === ModerationStatus::PENDING
				? ModerationStatus::APPROVED
				: $post->moderation_status;
			
			return [$status, null];
		}
		
		return null;
	}
	
	/**
	 * Write a decision to the listing. save() goes through the model events, so cached queries are
	 * cleared and the "listing is online" email goes out when it becomes approved.
	 * A rejection is emailed once per decision.
	 */
	private function apply(Post $post, string $status, ?string $hash, ?PostModeration $row, array $extra = []): void
	{
		$previousStatus = $post->moderation_status;
		
		$attributes = array_merge(['moderation_status' => $status, 'moderated_at' => now()], $extra);
		if (!is_null($hash)) {
			$attributes['moderation_hash'] = $hash;
		}
		$post->forceFill($attributes)->save();
		
		if ($row && $row->source !== 'human') {
			$isNewReview = ($status === ModerationStatus::NEEDS_REVIEW && $previousStatus !== ModerationStatus::NEEDS_REVIEW);
			$isChildSafety = in_array('child_safety', (array)($row->categories ?? []), true);
			if ($isNewReview || $isChildSafety) {
				$this->alertAdmins($post, $row, $isChildSafety);
			}
		}
		
		if (!$row) {
			return;
		}
		
		if (is_null($row->applied_status)) {
			$row->applied_status = $status;
			$row->save();
		}
		
		if ($status === ModerationStatus::REJECTED && is_null($row->notified_at) && $this->notifyOwnerOfRejection($post, $row)) {
			$row->notified_at = now();
			$row->save();
		}
	}
	
	/**
	 * Tell the owner why the listing was not approved. Sent at once (not queued), so true means
	 * it really went out; false when the owner has no verified email or phone, or sending failed.
	 */
	private function notifyOwnerOfRejection(Post $post, PostModeration $row): bool
	{
		try {
			$locale = $row->locale ?: ($post->user?->language_code ?: config('app.locale'));
			$notification = (new PostNotApproved($post, $row))->locale($locale);
			
			if (empty($notification->via($post))) {
				Log::warning('Rejected listing: the owner has no verified email or phone to be told.', ['post_id' => $post->getKey()]);
				
				return false;
			}
			
			$post->notifyNow($notification);
			
			return true;
		} catch (Throwable $e) {
			Log::error('Rejected listing: the owner could not be told.', ['post_id' => $post->getKey(), 'error' => $e->getMessage()]);
			
			return false;
		}
	}
	
	/**
	 * Email the staff about a listing that needs a person. At most 20 alerts an hour;
	 * the next alert after that says how many were left out. Child safety is always sent.
	 */
	private function alertAdmins(Post $post, PostModeration $row, bool $urgent = false): void
	{
		try {
			if (!$this->settings->moderationAlertAdmins() && !$urgent) {
				return;
			}
			
			$hourKey = 'ai:moderation_alerts:' . now()->format('YmdH');
			$sent = (int)Cache::get($hourKey, 0);
			if (!$urgent && $sent >= self::ALERTS_PER_HOUR) {
				Cache::increment('ai:moderation_alerts_skipped');
				
				return;
			}
			
			$admins = User::query()->permission(Permission::getStaffPermissions())->get();
			if ($admins->isEmpty()) {
				Log::log($urgent ? 'critical' : 'warning', 'Moderation alert not sent: no staff account to send it to.', [
					'post_id' => $post->getKey(),
					'rule'    => $row->rule,
				]);
				
				return;
			}
			
			$skipped = (int)Cache::pull('ai:moderation_alerts_skipped', 0);
			Notification::send($admins, new AdminModerationAlert($post, $row, $skipped, $urgent));
			Cache::put($hourKey, $sent + 1, now()->addHours(2));
		} catch (Throwable $e) {
			Log::log($urgent ? 'critical' : 'error', 'Moderation alert could not be sent to the admins.', [
				'post_id' => $post->getKey(),
				'rule'    => $row->rule,
				'error'   => $e->getMessage(),
			]);
		}
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
