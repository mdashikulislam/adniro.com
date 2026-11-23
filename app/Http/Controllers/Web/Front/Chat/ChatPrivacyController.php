<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatBroadcaster;
use App\Services\Chat\ChatPrivacy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * My chat privacy (adniro): whether others see me online and what I have read, and the people I blocked.
 * Unblocking uses the chat's own unblock route (ChatSafetyController).
 */
class ChatPrivacyController extends Controller
{
	public function show(Request $request): JsonResponse
	{
		$userId = (int)$request->user()->id;

		return response()->json(['privacy' => ChatPrivacy::of($userId), 'blocked' => ChatPrivacy::blockedPeople($userId)]);
	}

	public function update(Request $request): JsonResponse
	{
		$choices = $request->validate([
			'online' => ['required', 'boolean'],
			'seen'   => ['required', 'boolean'],
		]);

		$userId = (int)$request->user()->id;
		$privacy = ChatPrivacy::save($userId, (bool)$choices['online'], (bool)$choices['seen']);
		ChatBroadcaster::privacyChanged($userId, $privacy);

		return response()->json(['privacy' => $privacy]);
	}
}
