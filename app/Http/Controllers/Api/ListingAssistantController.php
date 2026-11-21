<?php

namespace App\Http\Controllers\Api;

use App\Models\City;
use App\Services\ListingAssistant\LimitStatus;
use App\Services\ListingAssistant\ListingAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/*
 * AI listing assistant for the mobile apps (adniro:ai): the same drafting as the AI box in the
 * website's listing form.
 *
 * The app sends text and up to four photos; the answer holds the draft in the same field names
 * as POST /api/posts, so the app shows it for review and then creates the listing as usual.
 * Photos are not stored here: the app uploads them with the listing.
 *
 * @group AI listing assistant
 */

class ListingAssistantController extends BaseController
{
	/** Looked up on each call: the router keeps controller instances between requests. */
	private function service(): ListingAssistantService
	{
		return app(ListingAssistantService::class);
	}
	
	/**
	 * AI post limit of the current user (or of the device's IP address for guests).
	 */
	public function limit(Request $request): JsonResponse
	{
		$user = auth('sanctum')->user();
		
		return response()->json([
			'success' => true,
			'message' => null,
			'result'  => [
				'available' => $this->service()->available($user),
				'voice'     => $this->service()->serverVoiceAvailable($user),
				'limit'     => $this->service()->limitStatus($user, $request->ip())->toArray(),
			],
		]);
	}
	
	/**
	 * Draft a listing from text and/or photos.
	 *
	 * @bodyParam text string Description in the person's words. Required without photos (10 characters at least).
	 * @bodyParam photos file[] Up to 4 images.
	 * @bodyParam lat number Device latitude, used to pick the nearest city.
	 * @bodyParam lon number Device longitude.
	 * @bodyParam redraft_of string Token of a previous draft of the same item (members only): up to two re-drafts are free.
	 */
	public function draft(Request $request): JsonResponse
	{
		$user = auth('sanctum')->user();
		$ip = $request->ip();
		
		if (!$this->service()->available($user)) {
			return $this->fail(trans('ai.assistant_off'), 'off', 403);
		}
		
		$rateKey = $user ? 'listing-assistant:user:' . $user->getKey() : 'listing-assistant:ip:' . $ip;
		$perMinute = (int)config($user ? 'ai_features.listing_assistant.rate_per_user' : 'ai_features.listing_assistant.rate_per_ip', 6);
		if (RateLimiter::tooManyAttempts($rateKey, $perMinute)) {
			$seconds = RateLimiter::availableIn($rateKey);
			
			return $this->fail(trans('ai.assistant_rate', ['seconds' => $seconds]), 'rate', 429)->header('Retry-After', (string)$seconds);
		}
		RateLimiter::hit($rateKey, 60);
		
		$allowed = collect(getServerAllowedImageFormats())->join(',');
		$data = $request->validate([
			'text'       => ['nullable', 'string', 'max:' . (int)config('ai_features.listing_assistant.max_text', 2000)],
			'photos'     => ['nullable', 'array', 'max:' . ListingAssistantService::MAX_PHOTOS],
			'photos.*'   => ['file', 'mimes:' . $allowed, 'max:' . (int)config('settings.upload.max_image_size', 1000)],
			'lat'        => ['nullable', 'numeric', 'between:-90,90'],
			'lon'        => ['nullable', 'numeric', 'between:-180,180'],
			'redraft_of' => ['nullable', 'string', 'max:40'],
		]);
		
		$outcome = $this->service()->draft((string)($data['text'] ?? ''), $user, $ip, app()->getLocale(), [
			'image_files' => (array)$request->file('photos', []),
			'lat'         => isset($data['lat']) ? (float)$data['lat'] : null,
			'lon'         => isset($data['lon']) ? (float)$data['lon'] : null,
			'redraft_of'  => $user ? ($data['redraft_of'] ?? null) : null,
		]);
		
		/** @var LimitStatus $limit */
		$limit = $outcome['limit'];
		
		if (!$outcome['ok']) {
			$reason = $outcome['reason'];
			$message = match ($reason) {
				'too_short' => trans('ai.assistant_too_short', ['min' => (int)config('ai_features.listing_assistant.min_text', 10)]),
				'busy'      => trans('ai.assistant_busy'),
				'limit'     => trans('ai.assistant_limit_reached', [
					'when' => $limit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL') ?? '',
				]),
				'unusable'  => $outcome['message'] ?: trans('ai.assistant_unusable'),
				default     => trans('ai.assistant_off'),
			};
			$status = match ($reason) {
				'limit' => 429,
				'busy'  => 409,
				default => 200,
			};
			
			$response = $this->fail($message, $reason, $status, $limit);
			if ($reason === 'limit' && $limit->resetsAt) {
				$response->header('Retry-After', (string)max(1, now()->diffInSeconds($limit->resetsAt, false)));
			}
			
			return $response;
		}
		
		$draft = $outcome['draft'];
		$clean = (array)$draft->draft;
		$city = !empty($clean['city_id']) ? City::query()->find($clean['city_id']) : null;
		
		return response()->json([
			'success' => true,
			'message' => null,
			'result'  => [
				'draft'          => $draft->token,
				'post'           => array_merge((array)($clean['values'] ?? []), [
					'country_code' => $draft->country_code,
					'city_id'      => $city?->id,
					'cf'           => (object)($clean['cf'] ?? []),
				]),
				'city'           => $city ? ['id' => $city->id, 'name' => $city->name] : null,
				'marks'          => (object)($clean['marks'] ?? []),
				'policy_flags'   => (array)($clean['policy_flags'] ?? []),
				'items_detected' => (int)($clean['items_detected'] ?? 1),
			],
			'extra'   => ['limit' => $limit->toArray()],
		]);
	}
	
	/**
	 * Turn a voice recording into text with the assigned speech-to-text model.
	 *
	 * @bodyParam audio file required Recording of at most a minute (webm, ogg, mp4/m4a, mp3, wav).
	 * @bodyParam language string Language of the speech, for example bn or bn-BD.
	 */
	public function transcription(Request $request): JsonResponse
	{
		$user = auth('sanctum')->user();
		
		if (!$this->service()->serverVoiceAvailable($user)) {
			return $this->fail(trans('ai.assistant_voice_unavailable'), 'off', 403);
		}
		
		$rateKey = 'listing-assistant-voice:' . ($user ? 'user:' . $user->getKey() : 'ip:' . $request->ip());
		if (RateLimiter::tooManyAttempts($rateKey, 10)) {
			$seconds = RateLimiter::availableIn($rateKey);
			
			return $this->fail(trans('ai.assistant_rate', ['seconds' => $seconds]), 'rate', 429)->header('Retry-After', (string)$seconds);
		}
		RateLimiter::hit($rateKey, 60);
		
		$request->validate([
			'audio'    => ['required', 'file', 'mimetypes:audio/webm,video/webm,audio/ogg,video/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/aac,audio/mpeg,audio/wav,audio/x-wav,audio/vnd.wave', 'max:' . (int)config('ai_features.listing_assistant.max_audio_kb', 5120)],
			'language' => ['nullable', 'string', 'max:20'],
		]);
		
		$file = $request->file('audio');
		$outcome = $this->service()->transcribe((string)$file->getRealPath(), $file->getMimeType(), $request->input('language'), $user, $request->ip());
		
		if (!$outcome['ok']) {
			return $this->fail(trans($outcome['reason'] === 'empty' ? 'ai.assistant_voice_no_speech' : 'ai.assistant_voice_failed'), $outcome['reason'], 200);
		}
		
		return response()->json(['success' => true, 'message' => null, 'result' => ['text' => $outcome['text']]]);
	}
	
	private function fail(string $message, ?string $reason, int $status, ?LimitStatus $limit = null): JsonResponse
	{
		return response()->json([
			'success' => false,
			'message' => $message,
			'result'  => null,
			'extra'   => array_filter(['reason' => $reason, 'limit' => $limit?->toArray()], fn ($v) => !is_null($v)),
		], $status);
	}
}
