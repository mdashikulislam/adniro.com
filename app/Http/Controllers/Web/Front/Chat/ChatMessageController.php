<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Chat\SendChatMessageRequest;
use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatThread;
use App\Models\Post;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatConversations;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatPresenter;
use App\Services\Chat\ChatPrivacy;
use App\Services\Chat\ChatUnread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * The chat's data routes (adniro): the inbox, a conversation and its history, sending, and marking read.
 * Every route that takes a conversation loads it through ChatAccess, for one of its participants only.
 */
class ChatMessageController extends Controller
{
	public function threads(Request $request): JsonResponse
	{
		$filter = ChatConversations::filterFrom($request->query('filter'));
		$cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;
		$search = ChatConversations::searchFrom($request->query('q'));

		return response()->json(ChatConversations::inbox((int)$request->user()->id, $filter, $cursor, $search));
	}

	/** A conversation with its latest messages, to open it without loading a new page. */
	public function thread(Request $request, int $thread): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId)->load(ChatPresenter::THREAD_RELATIONS);

		return response()->json(['thread' => ChatPresenter::thread($chatThread, $userId)] + ChatMessages::history($chatThread, $userId));
	}

	/** Older messages (?before=id), or the ones the browser has missed (?after=id). */
	public function messages(Request $request, int $thread): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId);

		if ($request->filled('after')) {
			return response()->json(ChatMessages::after($chatThread, $userId, (int)$request->query('after')) + [
				'read_up_to' => ChatPrivacy::readUpTo($chatThread, $userId),
			]);
		}

		$before = $request->filled('before') ? (int)$request->query('before') : null;

		return response()->json(ChatMessages::history($chatThread, $userId, $before));
	}

	/** A message sent from a listing: finds or creates the conversation with its seller, then sends. */
	public function start(SendChatMessageRequest $request, int $post): JsonResponse
	{
		$user = $request->user();
		$listing = Post::query()->unarchived()->findOrFail($post);

		$reason = ChatConversations::cannotStartReason($listing, $user);
		if ($reason !== null) {
			return response()->json(['message' => $reason], 403);
		}

		return $this->sendIn(ChatConversations::findOrCreate($listing, $user), $request, $listing);
	}

	public function send(SendChatMessageRequest $request, int $thread): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId);

		$otherId = (int)($chatThread->otherParticipant($userId)?->user_id ?? 0);
		if ($otherId === 0 || ChatAccess::isBlockedBetween($userId, $otherId)) {
			return response()->json(['message' => trans('chat.error_cannot_message')], 403);
		}

		return $this->sendIn($chatThread, $request);
	}

	public function read(Request $request, int $thread): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId);
		$validated = $request->validate(['message_id' => ['required', 'integer', 'min:1']]);

		return response()->json([
			'read_up_to'   => ChatMessages::markRead($chatThread, $userId, (int)$validated['message_id']),
			'unread_total' => ChatUnread::total($userId),
		]);
	}

	public function unread(Request $request): JsonResponse
	{
		return response()->json(['unread_total' => ChatUnread::total((int)$request->user()->id)]);
	}

	/** The message being replied to: one of this conversation's, or none. */
	private function repliedMessage(ChatThread $chatThread, SendChatMessageRequest $request): ?ChatMessage
	{
		if (!$request->filled('reply_to')) {
			return null;
		}

		// Only a message this member can still see: not one taken back, nor one they deleted with the conversation
		return ChatMessage::query()
			->where('thread_id', $chatThread->id)
			->whereNull('unsent_at')
			->where('id', '>', ChatMessages::clearedUpTo($chatThread, (int)$request->user()->id))
			->find((int)$request->input('reply_to'));
	}

	private function sendIn(ChatThread $chatThread, SendChatMessageRequest $request, ?Post $about = null): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$message = ChatMessages::send(
			$chatThread,
			$userId,
			$request->input('body'),
			$request->file('photo'),
			$request->input('client_uuid'),
			$about,
			$this->repliedMessage($chatThread, $request),
		);

		$chatThread->load(ChatPresenter::THREAD_RELATIONS);

		return response()->json([
			'message' => ChatPresenter::message($message),
			'thread'  => ChatPresenter::thread($chatThread, $userId),
		], $message->wasRecentlyCreated ? 201 : 200);
	}
}
