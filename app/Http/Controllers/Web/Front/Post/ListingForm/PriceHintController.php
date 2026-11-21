<?php

namespace App\Http\Controllers\Web\Front\Post\ListingForm;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\ListingForm\PriceHint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/*
 * The price hint under the listing form's Price (adniro): statistics only, no AI, for guests too.
 */

class PriceHintController extends Controller
{
	public function show(Request $request, PriceHint $priceHint): JsonResponse
	{
		$headers = config('larapen.core.noCacheHeaders') ?? [];
		if (!$priceHint->enabled()) {
			return response()->json(['ok' => false, 'reason' => 'off'], 200, $headers);
		}
		
		$data = $request->validate([
			// Only real categories and cities of this country, so made-up ids cannot fill the cache
			'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
			'city_id'     => ['nullable', 'integer', Rule::exists('cities', 'id')->where('country_code', (string)config('country.code'))],
			'post_id'     => ['nullable', 'integer', 'min:1'],
		]);
		
		$hint = $priceHint->for(
			(int)$data['category_id'],
			!empty($data['city_id']) ? (int)$data['city_id'] : null,
			$this->ownListing($data['post_id'] ?? null),
		);
		if (!$hint) {
			return response()->json(['ok' => false, 'reason' => 'none'], 200, $headers);
		}
		
		return response()->json([
			'ok'   => true,
			'hint' => [
				'text'  => $hint['text'],
				'low'   => $hint['low'],
				'high'  => $hint['high'],
				'count' => $hint['count'],
				'scope' => $hint['scope'],
			],
		], 200, $headers);
	}
	
	/** The listing being edited, only when it is the signed-in member's own */
	private function ownListing(mixed $postId): ?int
	{
		$user = auth()->user();
		if (empty($postId) || !$user) {
			return null;
		}
		$owns = Post::query()->withoutGlobalScopes()->whereKey((int)$postId)->where('user_id', $user->getKey())->exists();
		
		return $owns ? (int)$postId : null;
	}
}
