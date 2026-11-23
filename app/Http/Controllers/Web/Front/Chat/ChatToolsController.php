<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Chat\SaveChatToolsRequest;
use App\Models\Post;
use App\Services\Chat\ChatPresenter;
use App\Services\Chat\ChatTools;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * A member's own chat tools (adniro): the away message and the saved replies, and "Mark as sold" for a
 * listing of theirs, from the chat about it.
 */
class ChatToolsController extends Controller
{
	public function update(SaveChatToolsRequest $request): JsonResponse
	{
		$tools = ChatTools::save(
			(int)$request->user()->id,
			$request->boolean('away_on'),
			(string)$request->input('away_message'),
			(array)$request->input('saved_replies'),
		);

		return response()->json(['tools' => $tools]);
	}

	/**
	 * The seller takes their listing offline, the way "My listings" does (same service: the same checks and
	 * the same confirmation email). In every chat about it the listing then shows as no longer available.
	 */
	public function sold(Request $request, int $post, PostService $postService): JsonResponse
	{
		$listing = Post::query()->where('user_id', $request->user()->id)->findOrFail($post);

		$result = getServiceData($postService->offline($listing->id));
		if (!data_get($result, 'success')) {
			return response()->json(['message' => data_get($result, 'message') ?? trans('chat.js.error_generic')], 422);
		}

		return response()->json(['post' => ChatPresenter::post($listing->fresh(['picture', 'category', 'currency']))]);
	}
}
