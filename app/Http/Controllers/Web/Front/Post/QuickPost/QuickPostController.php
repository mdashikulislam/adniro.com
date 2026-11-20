<?php

namespace App\Http\Controllers\Web\Front\Post\QuickPost;

use App\Helpers\Common\Files\TmpUpload;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\BaseController;
use App\Http\Requests\Front\PostRequest;
use App\Models\CategoryField;
use App\Services\Ai\AiSettings;
use App\Services\QuickPost\DraftToPostInput;
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
				'voice'        => array_merge(self::voiceLanguages(), [
					'server'    => $service->serverVoiceAvailable($user),
					'serverUrl' => url('posts/create/quick/transcribe'),
					'maxSeconds' => (int)config('ai_features.quickpost.max_audio_seconds', 60),
				]),
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
			], 429, $headers ?? []);
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
		
		$throttled = $this->throttled('quickpost-photo', (int)config('ai_features.quickpost.photo_rate', 20), $request, $headers);
		if ($throttled) {
			return $throttled;
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
	
	/**
	 * Voice for browsers without speech recognition: a recording becomes text through the
	 * speech-to-text model assigned in Settings > AI (adniro:ai). Not counted as an AI post.
	 */
	public function transcribe(Request $request, QuickPostService $service): JsonResponse
	{
		$user = auth()->user();
		$headers = config('larapen.core.noCacheHeaders');
		
		if (!$service->serverVoiceAvailable($user)) {
			return response()->json(['ok' => false, 'message' => trans('ai.qp_voice_unavailable')], 403, $headers);
		}
		
		$throttled = $this->throttled('quickpost-voice', (int)config('ai_features.quickpost.voice_rate', 10), $request, $headers);
		if ($throttled) {
			return $throttled;
		}
		
		$validator = validator($request->all(), [
			'audio'    => ['required', 'file', 'mimetypes:audio/webm,video/webm,audio/ogg,video/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/aac,audio/mpeg,audio/wav,audio/x-wav,audio/vnd.wave', 'max:' . (int)config('ai_features.quickpost.max_audio_kb', 5120)],
			'language' => ['nullable', 'string', 'max:20'],
		]);
		if ($validator->fails()) {
			return response()->json(['ok' => false, 'message' => trans('ai.qp_voice_failed')], 422, $headers);
		}
		
		$file = $request->file('audio');
		$outcome = $service->transcribe((string)$file->getRealPath(), $file->getMimeType(), $request->input('language'), $user, $request->ip());
		
		return $outcome['ok']
			? response()->json(['ok' => true, 'text' => $outcome['text']], 200, $headers)
			: response()->json(['ok' => false, 'message' => trans($outcome['reason'] === 'empty' ? 'ai.qp_voice_no_speech' : 'ai.qp_voice_failed')], 200, $headers);
	}
	
	public function apply(Request $request, string $token, QuickPostService $service): RedirectResponse
	{
		$applied = $service->apply($token, auth()->user(), $request->session()->getId());
		
		if (!$applied) {
			flash(trans('ai.qp_draft_expired'))->warning();
			
			return redirect()->to(url('posts/create/quick'))->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$next = app(AiSettings::class)->quickpostOneScreen() ? url('posts/create/quick/review') : urlGen()->addPost();
		
		return redirect()->to($next)->withHeaders(config('larapen.core.noCacheHeaders'));
	}
	
	/**
	 * One-screen review: the normal listing form, prefilled, with the photos shown on the same
	 * page and a Publish button (adniro:ai).
	 */
	public function review(QuickPostService $service)
	{
		if (!$service->available(auth()->user()) || !session()->has(DraftToPostInput::MARKS_KEY) || empty(session('postInput'))) {
			return redirect()->to(urlGen()->addPost())->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Same preconditions as the form's own first step
		$pricingUrl = $this->getPricingPage($this->getSelectedPackage());
		if (!empty($pricingUrl)) {
			return redirect()->to($pricingUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		if (!session()->has('cfUid')) {
			session()->put('cfUid', 'cf-' . generateUniqueCode(9));
		}
		$this->syncSessionCountryData();
		
		$postInput = session('postInput');
		
		view()->share('previousStepUrl', null);
		view()->share('previousStepLabel', null);
		view()->share('formActionUrl', url('posts/create/quick/publish'));
		view()->share('nextStepUrl', null);
		view()->share('nextStepLabel', '<i class="bi bi-send"></i> ' . e(trans('ai.qp_review_publish')));
		view()->share('quickPostOneScreen', true);
		view()->share('quickPostPhotos', $this->sessionPhotos());
		
		return response()
			->view('front.post.createOrEdit.multiSteps.create.post', compact('postInput'))
			->withHeaders(config('larapen.core.noCacheHeaders'));
	}
	
	/**
	 * Publish from the one-screen review. The form is validated by the step 1 request and saved by
	 * the same code as the last step of the normal form. When a package or payment step is needed,
	 * the person continues to the photos step as usual.
	 */
	public function publish(PostRequest $request, QuickPostService $service): RedirectResponse
	{
		if (!$service->available(auth()->user())) {
			return redirect()->to(urlGen()->addPost());
		}
		
		// Save the step 1 input exactly as the form's first step does (including uploaded files)
		$postInput = $request->except($this->unwantedFields());
		$cfDir = $this->cfTmpUploadDir . (session()->has('cfUid') ? '/' . session('cfUid') : '');
		foreach (CategoryField::getFields($request->input('category_id')) as $field) {
			if ($field->type == 'file' && $request->hasFile('cf.' . $field->id) && $request->file('cf.' . $field->id)->isValid()) {
				$postInput['cf'][$field->id] = TmpUpload::file($request->file('cf.' . $field->id), $cfDir);
			}
		}
		session()->put('postInput', $postInput);
		
		if (config('settings.listing_form.picture_mandatory') && empty(array_filter((array)session('picturesInput', [])))) {
			flash(trans('ai.qp_photos_required'))->error();
			
			return redirect()->to(url('posts/create/quick/review'));
		}
		
		$needsPaymentStep = (
			isset($this->countPackages, $this->countPaymentMethods)
			&& $this->countPackages > 0
			&& $this->countPaymentMethods > 0
			&& doesNoPackageOrPremiumOneSelected()
		);
		if ($needsPaymentStep) {
			return redirect()->to(url('posts/create/photos'));
		}
		
		session()->forget(DraftToPostInput::MARKS_KEY);
		session()->flash('message', t('your_listing_is_created'));
		
		return $this->storeInputDataInDatabase($request);
	}
	
	/**
	 * Languages for the browser's speech recognition, as BCP 47 tags. The default is the site
	 * language in the current country (for example bn-BD or en-US), then the other site languages.
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
		}
		
		$default = $languages[$locale]['tag'] ?? $locale;
		if ($country !== '' && preg_match('/^[a-z]{2,3}$/', $locale)) {
			$default = $locale . '-' . $country;
		}
		
		$list = [['tag' => $default, 'label' => ($languages[$locale]['label'] ?? $locale) . ' (' . $country . ')']];
		foreach ($languages as $code => $language) {
			if ($language['tag'] !== $default) {
				$list[] = $language;
			}
		}
		
		return ['default' => $default, 'languages' => $list];
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
	
	/**
	 * A per-minute limit per account, or per IP address for guests. Null when the request may go on.
	 */
	private function throttled(string $prefix, int $perMinute, Request $request, ?array $headers): ?JsonResponse
	{
		$user = auth()->user();
		$rateKey = $prefix . ':' . ($user ? 'user:' . $user->getKey() : 'ip:' . $request->ip());
		
		if (RateLimiter::tooManyAttempts($rateKey, max(1, $perMinute))) {
			return response()->json([
				'ok'      => false,
				'message' => trans('ai.qp_rate', ['seconds' => RateLimiter::availableIn($rateKey)]),
			], 429, $headers ?? []);
		}
		RateLimiter::hit($rateKey, 60);
		
		return null;
	}
}
