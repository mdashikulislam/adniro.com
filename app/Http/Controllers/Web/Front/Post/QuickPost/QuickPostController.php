<?php

namespace App\Http\Controllers\Web\Front\Post\QuickPost;

use App\Http\Controllers\Controller;
use App\Services\QuickPost\DraftForForm;
use App\Services\QuickPost\LimitStatus;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/*
 * The AI box inside the listing form (adniro:ai), ported from classifedz.com.
 *
 * The seller describes the item once, typed or recorded. The draft service (limits, free
 * re-drafts, budget) turns it into the form's fields, and the box fills them in place.
 * Nothing is put into the session: the seller checks the form and submits it as usual.
 */

class QuickPostController extends Controller
{
	private const AUDIO_TYPES = 'audio/webm,video/webm,audio/ogg,video/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/aac,audio/mpeg,audio/wav,audio/x-wav,audio/vnd.wave';

	/** HTTP status for each reason a fill did not work. */
	private const STATUS_BY_REASON = [
		'too_short'      => 422,
		'unusable'       => 422,
		'busy'           => 409,
		'limit'          => 429,
		'ai_unavailable' => 503,
	];

	/**
	 * The old composer page: the box now lives in the normal form.
	 */
	public function composer(): RedirectResponse
	{
		return redirect()->to(urlGen()->addPost(), 301)->withHeaders(config('larapen.core.noCacheHeaders') ?? []);
	}

	public function fill(Request $request, QuickPostService $service, DraftForForm $presenter): JsonResponse
	{
		$user = auth()->user();
		$headers = config('larapen.core.noCacheHeaders') ?? [];

		if (!$service->available($user)) {
			return response()->json(['ok' => false, 'reason' => 'off', 'message' => trans('ai.qp_off')], 403, $headers);
		}

		$perMinute = (int)config($user ? 'ai_features.quickpost.rate_per_user' : 'ai_features.quickpost.rate_per_ip', 6);
		if ($throttled = $this->throttled('quickpost', $perMinute, $request, $headers)) {
			return $throttled;
		}

		$data = $request->validate([
			'text'       => ['nullable', 'required_without:audio', 'string', 'max:' . (int)config('ai_features.quickpost.max_text', 2000)],
			'audio'      => ['nullable', 'required_without:text', 'file', 'mimetypes:' . self::AUDIO_TYPES, 'max:' . (int)config('ai_features.quickpost.max_audio_kb', 5120)],
			'redraft_of' => ['nullable', 'string', 'max:40'],
		]);

		$transcript = null;
		if ($request->hasFile('audio')) {
			$heard = $this->transcribe($request, $request->file('audio'), $service, $headers);
			if ($heard instanceof JsonResponse) {
				return $heard;
			}
			$transcript = $heard;
		}

		$outcome = $service->draft($transcript ?? (string)($data['text'] ?? ''), $user, $request->ip(), app()->getLocale(), [
			'session_id' => $request->session()->getId(),
			'redraft_of' => $data['redraft_of'] ?? null,
		]);

		/** @var LimitStatus $limit */
		$limit = $outcome['limit'];
		$payload = [
			'ok'         => $outcome['ok'],
			'reason'     => $outcome['reason'] ?? null,
			'limit'      => $limit->toArray(),
			'limit_text' => $this->limitText($limit),
			'transcript' => $transcript,
		];

		if ($outcome['ok']) {
			return response()->json($payload + [
					'draft'  => $outcome['draft']->token,
					'fields' => $presenter->present($outcome['draft']),
				], 200, $headers);
		}

		$payload['message'] = match ($outcome['reason']) {
			'too_short' => trans('ai.qp_too_short', ['min' => (int)config('ai_features.quickpost.min_text', 10)]),
			'busy'      => trans('ai.qp_busy'),
			'limit'     => $this->limitReachedText($limit),
			'unusable'  => $outcome['message'] ?: trans('ai.qp_unusable'),
			default     => trans('ai.box_unavailable'),
		};

		return response()->json($payload, self::STATUS_BY_REASON[$outcome['reason']] ?? 503, $headers);
	}

	/**
	 * Speech languages for the browser's own recognition, used when no speech-to-text model is
	 * assigned: the site language in the current country first, then the other site languages.
	 *
	 * @return array{default: string, languages: array<int, array{tag: string, label: string}>}
	 */
	public static function voiceLanguages(): array
	{
		$locale = strtolower((string)app()->getLocale());
		$country = strtoupper((string)config('country.code'));

		$languages = [];
		try {
			foreach (getSupportedLanguages() as $code => $language) {
				$tag = str_replace('_', '-', (string)data_get($language, 'locale', $code));
				$label = (string)(data_get($language, 'native') ?: data_get($language, 'name') ?: $code);
				$languages[strtolower((string)$code)] = ['tag' => $tag, 'label' => $label];
			}
		} catch (Throwable $e) {
			report($e);
		}

		$default = $languages[$locale]['tag'] ?? $locale;
		if ($country !== '' && preg_match('/^[a-z]{2,3}$/', $locale)) {
			$default = $locale . '-' . $country;
		}

		$list = [['tag' => $default, 'label' => ($languages[$locale]['label'] ?? $locale) . ' (' . $country . ')']];
		foreach ($languages as $language) {
			if ($language['tag'] !== $default) {
				$list[] = $language;
			}
		}

		return ['default' => $default, 'languages' => $list];
	}

	public static function limitText(LimitStatus $limit): ?string
	{
		if ($limit->unlimited()) {
			return null;
		}

		return trans_choice('ai.qp_left_' . $limit->period, (int)$limit->remaining(), [
			'left'  => $limit->remaining(),
			'limit' => $limit->limit,
		]);
	}

	/**
	 * The recording as text, or the answer to send when it could not be used.
	 */
	private function transcribe(Request $request, UploadedFile $file, QuickPostService $service, array $headers): string|JsonResponse
	{
		if (!$service->serverVoiceAvailable(auth()->user())) {
			return response()->json(['ok' => false, 'reason' => 'voice_off', 'message' => trans('ai.qp_voice_unavailable')], 403, $headers);
		}

		if ($throttled = $this->throttled('quickpost-voice', (int)config('ai_features.quickpost.voice_rate', 10), $request, $headers)) {
			return $throttled;
		}

		$heard = $service->transcribe((string)$file->getRealPath(), $file->getMimeType(), app()->getLocale(), auth()->user(), $request->ip());
		if (!$heard['ok']) {
			$message = trans($heard['reason'] === 'empty' ? 'ai.qp_voice_no_speech' : 'ai.qp_voice_failed');

			return response()->json(['ok' => false, 'reason' => 'voice_failed', 'message' => $message], 422, $headers);
		}

		return (string)$heard['text'];
	}

	private function limitReachedText(LimitStatus $limit): string
	{
		return trans('ai.qp_limit_reached', [
			'when' => $limit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL') ?? '',
		]);
	}

	/**
	 * A per-minute limit per account, or per IP address for guests. Null when the request may go on.
	 */
	private function throttled(string $prefix, int $perMinute, Request $request, array $headers): ?JsonResponse
	{
		$user = auth()->user();
		$rateKey = $prefix . ':' . ($user ? 'user:' . $user->getKey() : 'ip:' . $request->ip());

		if (RateLimiter::tooManyAttempts($rateKey, max(1, $perMinute))) {
			return response()->json([
				'ok'      => false,
				'reason'  => 'rate',
				'message' => trans('ai.qp_rate', ['seconds' => RateLimiter::availableIn($rateKey)]),
			], 429, $headers);
		}
		RateLimiter::hit($rateKey, 60);

		return null;
	}
}
