<?php

namespace App\Http\Controllers\Web\Front\Account;

use App\Http\Controllers\Controller;
use App\Services\SellerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowsController extends Controller
{
	/**
	 * Follow a seller, or stop following it (AJAX, logged users only)
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function toggle(Request $request): JsonResponse
	{
		$followerId = (int)auth()->id();
		
		$seller = SellerService::find($request->input('username'));
		if (empty($seller)) {
			return response()->json(['message' => t('user_not_found')], 404);
		}
		
		if (!empty($seller['user_id']) && $seller['user_id'] == $followerId) {
			return response()->json(['message' => t('cannot_follow_yourself')], 422);
		}
		
		$isFollowing = SellerService::toggleFollow($seller, $followerId);
		
		return response()->json([
			'isFollowing' => $isFollowing,
			'followers'   => SellerService::followersCount($seller),
			'message'     => $isFollowing
				? t('you_now_follow_x', ['name' => $seller['name']])
				: t('you_no_longer_follow_x', ['name' => $seller['name']]),
		]);
	}
}
