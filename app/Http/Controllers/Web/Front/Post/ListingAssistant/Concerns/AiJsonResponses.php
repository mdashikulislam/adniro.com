<?php

namespace App\Http\Controllers\Web\Front\Post\ListingAssistant\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/*
 * Shared by the listing form's AI endpoints (adniro:ai): uncached JSON and a per-minute limit.
 */

trait AiJsonResponses
{
	protected function aiJson(array $payload, int $status = 200): JsonResponse
	{
		return response()->json($payload, $status, config('larapen.core.noCacheHeaders') ?? []);
	}

	/**
	 * A per-minute limit per account, or per IP address for guests. Null when the request may go on.
	 */
	protected function throttled(string $prefix, int $perMinute, Request $request): ?JsonResponse
	{
		$user = auth()->user();
		$rateKey = $prefix . ':' . ($user ? 'user:' . $user->getKey() : 'ip:' . $request->ip());

		if (RateLimiter::tooManyAttempts($rateKey, max(1, $perMinute))) {
			return $this->aiJson([
				'ok'      => false,
				'reason'  => 'rate',
				'message' => trans('ai.assistant_rate', ['seconds' => RateLimiter::availableIn($rateKey)]),
			], 429);
		}
		RateLimiter::hit($rateKey, 60);

		return null;
	}
}
