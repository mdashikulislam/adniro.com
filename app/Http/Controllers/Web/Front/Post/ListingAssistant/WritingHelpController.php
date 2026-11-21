<?php

namespace App\Http\Controllers\Web\Front\Post\ListingAssistant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Front\Post\ListingAssistant\Concerns\AiJsonResponses;
use App\Models\Category;
use App\Models\Post;
use App\Services\ListingAssistant\LimitStatus;
use App\Services\ListingWriter\WritingHelpService;
use App\Services\ListingWriter\WritingTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/*
 * AI writing help next to the listing form's Title and Description (adniro:ai).
 * Returns a suggestion only; the seller decides whether to use it, and nothing is saved here.
 */

class WritingHelpController extends Controller
{
	use AiJsonResponses;

	/** HTTP status for each reason a request did not work. */
	private const STATUS_BY_REASON = [
		'too_short'      => 422,
		'unusable'       => 422,
		'busy'           => 409,
		'limit'          => 429,
		'ai_unavailable' => 503,
	];

	public function write(Request $request, WritingHelpService $service): JsonResponse
	{
		$user = auth()->user();
		if (!$user) {
			return $this->aiJson(['ok' => false, 'reason' => 'login', 'message' => trans('ai.writing_login')], 401);
		}
		if (!$service->available($user)) {
			return $this->aiJson(['ok' => false, 'reason' => 'off', 'message' => trans('ai.writing_off')], 403);
		}
		if ($throttled = $this->throttled('listing-writer', (int)config('ai_features.listing_writer.rate_per_user', 10), $request)) {
			return $throttled;
		}

		$titleMax = (int)config('settings.listing_form.title_max_length', 150);
		$descriptionMax = (int)config('settings.listing_form.description_max_length', 6000);
		$data = $request->validate([
			'field'       => ['required', Rule::in(WritingTask::FIELDS)],
			'action'      => ['required', Rule::in(WritingTask::ACTIONS)],
			'title'       => ['nullable', 'string', 'max:' . ($titleMax * 2)],
			// The rich editor sends HTML, which is longer than the text it holds
			'description' => ['nullable', 'string', 'max:' . ($descriptionMax * 3)],
			'category_id' => ['nullable', 'integer'],
			'post_id'     => ['nullable', 'integer'],
		]);

		$postId = !empty($data['post_id']) ? (int)$data['post_id'] : null;
		if ($postId && !$this->ownsPost($postId, (int)$user->getKey())) {
			return $this->aiJson(['ok' => false, 'reason' => 'not_found', 'message' => t('post_not_found')], 404);
		}

		$task = WritingTask::make(
			$data['field'],
			$data['action'],
			$data['title'] ?? null,
			$data['description'] ?? null,
			$this->categoryName($data['category_id'] ?? null),
		);
		$outcome = $service->write($task, $user, $request->ip(), app()->getLocale(), $postId);

		/** @var LimitStatus $limit */
		$limit = $outcome['limit'];
		$payload = [
			'ok'         => $outcome['ok'],
			'reason'     => $outcome['reason'] ?? null,
			'limit'      => $limit->toArray(),
			'limit_text' => self::limitText($limit),
		];
		if ($outcome['ok']) {
			return $this->aiJson($payload + ['text' => $outcome['text']]);
		}

		$payload['message'] = match ($outcome['reason']) {
			'too_short' => trans('ai.writing_too_short'),
			'busy'      => trans('ai.assistant_busy'),
			'limit'     => trans('ai.writing_limit_reached', [
				'when' => $limit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL') ?? '',
			]),
			'unusable'  => trans('ai.writing_unusable'),
			default     => trans('ai.writing_unavailable'),
		};

		return $this->aiJson($payload, self::STATUS_BY_REASON[$outcome['reason']] ?? 503);
	}

	/** "28 of 30 writing helps left today", or null when there is no limit */
	public static function limitText(LimitStatus $limit): ?string
	{
		if ($limit->unlimited()) {
			return null;
		}

		return trans_choice('ai.writing_left_' . $limit->period, (int)$limit->remaining(), [
			'left'  => $limit->remaining(),
			'limit' => $limit->limit,
		]);
	}

	private function ownsPost(int $postId, int $userId): bool
	{
		return Post::query()->withoutGlobalScopes()->whereKey($postId)->where('user_id', $userId)->exists();
	}

	private function categoryName(mixed $categoryId): ?string
	{
		if (empty($categoryId)) {
			return null;
		}
		$category = Category::query()->withoutGlobalScopes()->find((int)$categoryId);

		return $category ? (string)data_get($category, 'name') : null;
	}
}
