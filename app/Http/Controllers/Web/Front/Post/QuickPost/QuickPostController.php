<?php

namespace App\Http\Controllers\Web\Front\Post\QuickPost;

use App\Helpers\Common\Files\TmpUpload;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\BaseController;
use App\Services\QuickPost\LimitStatus;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/*
 * Quick post: describe the item in a sentence, get a prefilled listing form (adniro:ai).
 *
 * Extends the multi-step create controller, and its URLs keep "create" as their second segment,
 * so the guest, package and navigation rules of the normal form apply unchanged.
 */

class QuickPostController extends BaseController
{
	public function composer(QuickPostService $service)
	{
		$user = auth()->user();
		if (!$service->available($user)) {
			return redirect()->to(urlGen()->addPost())->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$limit = $service->limitStatus($user, request()->ip());
		
		// Photos added earlier in this browser session (here or in the photos step)
		$photos = $this->sessionPhotos();
		
		// "Describe it again" from the prefilled form: the same text, as a free re-draft
		$redraft = $service->ownedDraft((string)request()->query('redraft'), $user, request()->session()->getId());
		
		return response()
			->view('front.post.quick.composer', [
				'limit'      => $limit,
				'limitText'  => $this->limitText($limit),
				'formUrl'    => urlGen()->addPost(),
				'draftUrl'   => url('posts/create/quick/draft'),
				'minText'    => (int)config('ai_features.quickpost.min_text', 10),
				'maxText'    => (int)config('ai_features.quickpost.max_text', 2000),
				'timeoutMs'  => ((int)config('ai_features.quickpost.timeout', 25) + 5) * 1000,
				'redraftToken' => $redraft?->token,
				'redraftText'  => $redraft?->input_text,
				'photos'       => $photos,
				'photoUrl'     => url('posts/create/quick/photos'),
				'photoLimit'   => $this->photoLimit(),
				'photoMaxKb'   => (int)config('settings.upload.max_image_size', 1000),
			])
			->withHeaders(config('larapen.core.noCacheHeaders'));
	}
	
	public function draft(Request $request, QuickPostService $service): JsonResponse
	{
		$user = auth()->user();
		$ip = $request->ip();
		$headers = config('larapen.core.noCacheHeaders');
		
		if (!$service->available($user)) {
			return response()->json(['ok' => false, 'reason' => 'off', 'redirect' => urlGen()->addPost()], 200, $headers);
		}
		
		// Burst protection, on top of the admin-set AI post limit
		$rateKey = $user ? 'quickpost:user:' . $user->getKey() : 'quickpost:ip:' . $ip;
		$perMinute = (int)config($user ? 'ai_features.quickpost.rate_per_user' : 'ai_features.quickpost.rate_per_ip', 6);
		if (RateLimiter::tooManyAttempts($rateKey, $perMinute)) {
			return response()->json([
				'ok'      => false,
				'reason'  => 'rate',
				'message' => trans('ai.qp_rate', ['seconds' => RateLimiter::availableIn($rateKey)]),
			], 429, $headers);
		}
		RateLimiter::hit($rateKey, 60);
		
		$data = $request->validate([
			'text'       => ['nullable', 'string', 'max:' . (int)config('ai_features.quickpost.max_text', 2000)],
			'lat'        => ['nullable', 'numeric', 'between:-90,90'],
			'lon'        => ['nullable', 'numeric', 'between:-180,180'],
			'redraft_of' => ['nullable', 'string', 'max:40'],
		]);
		
		$outcome = $service->draft((string)($data['text'] ?? ''), $user, $ip, app()->getLocale(), [
			'photos'     => array_values(array_filter((array)session('picturesInput', []))),
			'session_id' => $request->session()->getId(),
			'lat'        => isset($data['lat']) ? (float)$data['lat'] : null,
			'lon'        => isset($data['lon']) ? (float)$data['lon'] : null,
			'redraft_of' => $data['redraft_of'] ?? null,
		]);
		
		/** @var LimitStatus $limit */
		$limit = $outcome['limit'];
		$payload = [
			'ok'         => $outcome['ok'],
			'reason'     => $outcome['reason'] ?? null,
			'limit'      => $limit->toArray(),
			'limit_text' => $this->limitText($limit),
		];
		
		if ($outcome['ok']) {
			$payload['redirect'] = url('posts/create/quick/' . $outcome['draft']->token . '/apply');
			$payload['draft'] = $outcome['draft']->token;
			
			return response()->json($payload, 200, $headers);
		}
		
		$payload['message'] = match ($outcome['reason']) {
			'too_short'      => trans('ai.qp_too_short', ['min' => (int)config('ai_features.quickpost.min_text', 10)]),
			'busy'           => trans('ai.qp_busy'),
			'limit'          => $this->limitReachedText($limit),
			'unusable'       => $outcome['message'] ?: trans('ai.qp_unusable'),
			'ai_unavailable' => trans('ai.qp_unavailable'),
			default          => trans('ai.qp_unavailable'),
		};
		
		// When the AI cannot help, the text was put into the normal form: go there
		if ($outcome['reason'] === 'ai_unavailable') {
			$payload['redirect'] = urlGen()->addPost();
		}
		
		return response()->json($payload, $outcome['reason'] === 'limit' ? 429 : 200, $headers);
	}
	
	/**
	 * One photo per request, already shrunk by the browser. Stored exactly like the photos step
	 * stores its uploads (same folder and session key), so the person never uploads twice.
	 */
	public function photo(Request $request, QuickPostService $service): JsonResponse
	{
		$headers = config('larapen.core.noCacheHeaders');
		
		if (!$service->available(auth()->user())) {
			return response()->json(['ok' => false, 'message' => trans('ai.qp_unavailable')], 403, $headers);
		}
		
		$file = $request->file('photo');
		$allowed = collect(getServerAllowedImageFormats())->join(',');
		$validator = validator(['photo' => $file], [
			'photo' => ['required', 'file', 'mimes:' . $allowed, 'max:' . (int)config('settings.upload.max_image_size', 1000)],
		]);
		if ($validator->fails()) {
			$isHeic = $file && in_array(strtolower((string)$file->getClientOriginalExtension()), ['heic', 'heif'], true);
			
			return response()->json([
				'ok'      => false,
				'message' => $isHeic ? trans('ai.qp_photo_heic') : $validator->errors()->first('photo'),
			], 422, $headers);
		}
		
		$photos = (array)session('picturesInput', []);
		if (count($photos) >= $this->photoLimit()) {
			return response()->json(['ok' => false, 'message' => trans('ai.qp_photo_limit', ['limit' => $this->photoLimit()])], 422, $headers);
		}
		
		if (!session()->has('uid')) {
			session()->put('uid', generateUniqueCode(9));
		}
		
		try {
			$path = TmpUpload::image($file, $this->tmpUploadDir . '/' . session('uid'));
		} catch (Throwable $e) {
			$path = null;
		}
		if (empty($path)) {
			return response()->json(['ok' => false, 'message' => trans('ai.qp_photo_failed')], 422, $headers);
		}
		
		$photos[] = $path;
		session()->put('picturesInput', $photos);
		
		return response()->json([
			'ok'    => true,
			'photo' => ['key' => array_key_last($photos), 'url' => $this->thumbUrl($path)],
			'count' => count($photos),
		], 200, $headers);
	}
	
	public function apply(Request $request, string $token, QuickPostService $service): RedirectResponse
	{
		$applied = $service->apply($token, auth()->user(), $request->session()->getId());
		
		if (!$applied) {
			flash(trans('ai.qp_draft_expired'))->warning();
			
			return redirect()->to(url('posts/create/quick'))->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		return redirect()->to(urlGen()->addPost())->withHeaders(config('larapen.core.noCacheHeaders'));
	}
	
	private function photoLimit(): int
	{
		return max(1, (int)config('settings.listing_form.pictures_limit', 5));
	}
	
	/**
	 * @return array<int, array{key: int|string, url: string}>
	 */
	private function sessionPhotos(): array
	{
		$photos = [];
		foreach ((array)session('picturesInput', []) as $key => $path) {
			if (!empty($path)) {
				$photos[] = ['key' => $key, 'url' => $this->thumbUrl((string)$path)];
			}
		}
		
		return $photos;
	}
	
	private function thumbUrl(string $path): string
	{
		try {
			return thumbService($path)->resize('picture-md')->url();
		} catch (Throwable $e) {
			return '';
		}
	}
	
	private function limitText(LimitStatus $limit): ?string
	{
		if ($limit->unlimited()) {
			return null;
		}
		
		return trans_choice('ai.qp_left_' . $limit->period, (int)$limit->remaining(), [
			'left'  => $limit->remaining(),
			'limit' => $limit->limit,
		]);
	}
	
	private function limitReachedText(LimitStatus $limit): string
	{
		return trans('ai.qp_limit_reached', [
			'when' => $limit->resetsAt?->setTimezone((string)config('app.timezone', 'UTC'))->isoFormat('LLL') ?? '',
		]);
	}
}
