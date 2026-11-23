<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat\ChatThread;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatPresenter;
use App\Services\Chat\ChatTidy;
use App\Services\Chat\ChatUnread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * Tidying my own list of chats (adniro): archive, mute, mark as unread or read. The other person sees none
 * of it.
 */
class ChatTidyController extends Controller
{
	public function update(Request $request, int $thread): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId);
		$changes = $request->validate([
			'archived' => ['sometimes', 'boolean'],
			'muted'    => ['sometimes', 'boolean'],
			'unread'   => ['sometimes', 'boolean'],
		]);

		if (array_key_exists('archived', $changes)) {
			ChatTidy::archive($chatThread, $userId, (bool)$changes['archived']);
		}
		if (array_key_exists('muted', $changes)) {
			ChatTidy::mute($chatThread, $userId, (bool)$changes['muted']);
		}
		if (array_key_exists('unread', $changes)) {
			$this->setUnread($chatThread, $userId, (bool)$changes['unread']);
		}

		$chatThread = ChatAccess::threadFor($thread, $userId)->load(ChatPresenter::THREAD_RELATIONS);

		return response()->json([
			'thread'       => ChatPresenter::thread($chatThread, $userId),
			'unread_total' => ChatUnread::total($userId),
		]);
	}

	private function setUnread(ChatThread $chatThread, int $userId, bool $isUnread): void
	{
		if ($isUnread) {
			ChatTidy::markUnread($chatThread, $userId);

			return;
		}
		if (!empty($chatThread->last_message_id)) {
			ChatMessages::markRead($chatThread, $userId, (int)$chatThread->last_message_id);
		}
	}
}
