<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat\ChatMessage;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatPresenter;
use App\Services\Chat\ChatUnread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * Deleting in the chat (adniro): a member removes a conversation from their own list, or takes back a message
 * they sent a short while ago. Neither removes anything from the database: see ChatMessages.
 */
class ChatDeleteController extends Controller
{
	/** Delete the conversation for me. The other person keeps it. */
	public function conversation(Request $request, int $thread): JsonResponse
	{
		$userId = (int)$request->user()->id;
		ChatMessages::hideFor(ChatAccess::threadFor($thread, $userId), $userId);

		return response()->json(['deleted' => true, 'unread_total' => ChatUnread::total($userId)]);
	}

	/** Take back a message I sent: both people see "Message deleted" in its place. */
	public function message(Request $request, int $thread, int $message): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId);
		$chatMessage = ChatMessage::query()->where('thread_id', $chatThread->id)->findOrFail($message);

		// Someone else's message is "not found", as everything outside a member's reach is in the chat
		abort_unless((int)$chatMessage->user_id === $userId, 404);
		if (!$chatMessage->isUnsent() && !ChatMessages::canUnsend($chatMessage, $userId)) {
			return response()->json(['message' => trans('chat.error_unsend_late')], 422);
		}

		$chatMessage = ChatMessages::unsend($chatThread, $chatMessage);
		if (!$chatMessage->isUnsent()) {
			// An offer that was answered at the same moment: the answer stands
			return response()->json(['message' => trans('chat.error_unsend_late')], 422);
		}

		return response()->json(['message' => ChatPresenter::message($chatMessage)]);
	}
}
