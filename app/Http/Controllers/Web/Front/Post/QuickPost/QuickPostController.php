<?php

namespace App\Http\Controllers\Web\Front\Post\QuickPost;

use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\BaseController;
use App\Services\QuickPost\LimitStatus;
use App\Services\QuickPost\QuickPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
			'text'       => ['required', 'string', 'max:' . (int)config('ai_features.quickpost.max_text', 2000)],
			'lat'        => ['nullable', 'numeric', 'between:-90,90'],
			'lon'        => ['nullable', 'numeric', 'between:-180,180'],
			'redraft_of' => ['nullable', 'string', 'max:40'],
		]);
		
		$outcome = $service->draft($data['text'], $user, $ip, app()->getLocale(), [
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
	
	public function apply(Request $request, string $token, QuickPostService $service): RedirectResponse
	{
		$applied = $service->apply($token, auth()->user(), $request->session()->getId());
		
		if (!$applied) {
			flash(trans('ai.qp_draft_expired'))->warning();
			
			return redirect()->to(url('posts/create/quick'))->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		return redirect()->to(urlGen()->addPost())->withHeaders(config('larapen.core.noCacheHeaders'));
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
