<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Chat\MakeChatOfferRequest;
use App\Models\Chat\ChatMessage;
use App\Models\Chat\ChatThread;
use App\Models\Post;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatConversations;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatOffers;
use App\Services\Chat\ChatPresenter;
use App\Services\Chat\ChatSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * Offers in a chat (adniro): proposing a price for a listing, and accepting or declining one.
 */
class ChatOfferController extends Controller
{
	/**
	 * An offer on a listing. From anyone but its owner it goes to the owner, in the conversation the two have
	 * (made now if they have none). From the owner it is their own price for the person they are talking to,
	 * in the conversation named by "thread".
	 */
	public function make(MakeChatOfferRequest $request, int $post): JsonResponse
	{
		$user = $request->user();
		$userId = (int)$user->id;
		$listing = Post::query()->unarchived()->with(['category', 'currency', 'picture'])->findOrFail($post);

		if (!ChatOffers::canBeMadeOn($listing)) {
			return response()->json(['message' => trans('chat.error_offer_unavailable')], 422);
		}

		if ((int)$listing->user_id === $userId) {
			$chatThread = ChatAccess::threadFor((int)$request->input('thread'), $userId);
			if (!$this->canWriteIn($chatThread, $userId)) {
				return response()->json(['message' => trans('chat.error_cannot_message')], 403);
			}
			if (!ChatOffers::isTalkedAbout($chatThread, $listing)) {
				return response()->json(['message' => trans('chat.error_offer_unavailable')], 422);
			}
		} else {
			$reason = ChatConversations::cannotStartReason($listing, $user);
			if ($reason !== null) {
				return response()->json(['message' => $reason], 403);
			}
			$chatThread = ChatConversations::findOrCreate($listing, $user);
		}

		$offer = ChatOffers::make($chatThread, $userId, $listing, (string)$request->input('amount'), $request->input('client_uuid'));

		return $this->sent($chatThread, $offer, $userId);
	}

	/** The person who received the offer accepts or declines it. */
	public function answer(Request $request, int $thread, int $message): JsonResponse
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId);
		$validated = $request->validate(['answer' => ['required', 'in:accept,decline']]);
		$isAccepted = $validated['answer'] === 'accept';

		// An offer of this conversation, from the other person, that this member can still see
		$offer = ChatMessage::query()
			->where('thread_id', $chatThread->id)
			->where('type', ChatMessage::TYPE_OFFER)
			->where('user_id', '!=', $userId)
			->where('id', '>', ChatMessages::clearedUpTo($chatThread, $userId))
			->with(['post.category', 'post.currency'])
			->findOrFail($message);

		if (!ChatSettings::isEnabled() || !$this->canWriteIn($chatThread, $userId)) {
			return response()->json(['message' => trans('chat.error_cannot_message')], 403);
		}
		// A listing that is gone or sold can no longer be agreed on; declining is always possible
		if ($isAccepted && ($offer->post === null || !ChatPresenter::isAvailable($offer->post))) {
			return response()->json(['message' => trans('chat.js.listing_gone')], 422);
		}

		$answer = ChatOffers::answer($chatThread, $offer, $userId, $isAccepted);
		if ($answer === null) {
			return response()->json(['message' => trans('chat.error_offer_closed')], 409);
		}

		return $this->sent($chatThread, $answer, $userId);
	}

	/** The other person still has an account, and neither has blocked the other. */
	private function canWriteIn(ChatThread $chatThread, int $userId): bool
	{
		$otherId = (int)($chatThread->otherParticipant($userId)?->user_id ?? 0);

		return $otherId > 0 && !ChatAccess::isBlockedBetween($userId, $otherId);
	}

	private function sent(ChatThread $chatThread, ChatMessage $message, int $userId): JsonResponse
	{
		$chatThread->load(ChatPresenter::THREAD_RELATIONS);

		return response()->json([
			'message' => ChatPresenter::message($message),
			'thread'  => ChatPresenter::thread($chatThread, $userId),
		], $message->wasRecentlyCreated ? 201 : 200);
	}
}
